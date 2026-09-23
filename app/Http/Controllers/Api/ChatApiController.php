<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ItemApiResource;
use App\Models\BlockUser;
use App\Models\Category;
use App\Models\Chat;
use App\Models\ChatTemplate;
use App\Models\Item;
use App\Models\ItemOffer;
use App\Models\Setting;
use App\Services\CurrencyFormatterService;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Chat */
class ChatApiController extends BaseApiController
{
    private function getOtherUserRelation(string $type): string
    {
        return $type === 'seller' ? 'buyer' : 'seller';
    }

    /** Create Item Offer */
    public function createItemOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
            'amount' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $item = Item::approved()->notOwner()->with('category:id,is_job_category')->find($request->item_id);
            if(empty($item)){
                ResponseService::validationError(__('No Item Found'));
            }
            $itemOffer = ItemOffer::firstOrNew([
                'item_id' => $request->item_id,
                'buyer_id' => Auth::user()->id,
                'seller_id' => $item->user_id,
            ]);

            $itemOffer->deleted_by_seller_at = null;
            $itemOffer->deleted_by_buyer_at = null;

            $shouldUpdateAmount = $request->has('amount');
            $isNewOfferAmount = $shouldUpdateAmount && $request->amount != 0;

            if ($shouldUpdateAmount) {
                if($item->price <= $request->amount){
                    ResponseService::errorResponse(trans("Offer must be less than seller price"));
                }
                $itemOffer->amount = $request->amount;
            }

            $itemOffer->save();

            $itemOffer = $itemOffer->load(
                'seller:id,name,profile',
                'buyer:id,name,profile',
                'item:id,name,slug,description,price,min_salary,max_salary,user_id,category_id,currency_id,address,city,state,country,area_id,status,latitude,longitude,published_at,region_code,country_code,item_type,sold_to,rejected_reason,admin_edit_reason,is_edited_by_admin,currency_id',
                'item.currency',
                'item.category:id,name,is_job_category',
                'item.gallery_images:id,item_id,image,is_default',
                'item.user:id,name,profile',
                'item.area:id,name',
                'item.translations',
                'item.currency'
            );
            $formatter = app(currencyFormatterService::class);
            $offerCurrency = $itemOffer->item?->currency;
            $formattedOfferAmount = $formatter->formatPrice($itemOffer->amount, $offerCurrency);
            $formattedItemPrice = $formatter->formatPrice($item->price, $offerCurrency);

            if ($isNewOfferAmount) {
                $chat = Chat::create([
                    'sender_id' => $itemOffer->buyer->id,
                    'item_offer_id' => $itemOffer->id,
                    'message' => null,
                    'is_offer' => true,
                    'amount' => $request->amount,
                    'is_read' => 0,
                ]);

                $unreadMessagesCount = Chat::where('item_offer_id', $itemOffer->id)
                    ->where('is_read', 0)
                    ->count();

                $fcmMsg = [
                    ...$chat->toArray(),
                    'user_id' => $itemOffer->buyer->id,
                    'user_name' => $itemOffer->buyer->name,
                    'user_profile' => $itemOffer->buyer->profile,
                    'user_type' => 'Buyer',
                    'item_id' => $itemOffer->item->id,
                    'item_name' => $itemOffer->item->name,
                    'item_image' => $itemOffer->item->image,
                    'item_price' => $itemOffer->item->price,
                    'item_offer_id' => $itemOffer->id,
                    'item_offer_amount' => $itemOffer->amount,
                    'type' => $chat->message_type,
                    'message_type_temp' => $chat->message_type,
                    'unread_count' => $unreadMessagesCount,
                    'item_formatted_amount' => $formattedOfferAmount,
                    'item_formatted_price' => $formattedItemPrice
                ];
                unset($fcmMsg['message_type']);

                $notificationTitle = ($itemOffer->buyer->name ?? 'User') . ' • ' . ($itemOffer->item->name ?? 'Item');
                $displayMessage = '💰 Sent an offer of ' . $formattedOfferAmount;

                NotificationService::dispatchChunkedNotifications(
                    $notificationTitle,
                    $displayMessage,
                    'chat',
                    $fcmMsg,
                    false,
                    array($item->user->id),
                    true
                );
            }

            // Add Formatted Item offer amount and item price
            $itemOffer->item_offer_formatted_amount = $formattedOfferAmount;
            $itemOffer->item_formatted_price = $formattedItemPrice;
            $currencyData = $itemOffer->item->currency;
            $formattedItem = (new ItemApiResource(collect([$item->fresh(['translations'])])))->asSingle()->resolve($request);
            $itemOffer->item->unsetRelation('currency');
            $itemOffer->unsetRelation('item');
            $formattedItem['currency'] = $currencyData ?: [
                'iso_code' => Setting::getValue('currency_code'),
                'name' => Setting::getValue('currency_name'),
                'symbol' => Setting::getValue('currency_symbol'),
                'symbol_position' => Setting::getValue('currency_symbol_position'),
                'decimal_places' => Setting::getValue('decimal_places'),
                'thousand_separator' => Setting::getValue('thousand_separator'),
                'decimal_separator' => Setting::getValue('decimal_separator'),
            ];
            $itemOffer->setAttribute('item', $formattedItem);
            ResponseService::successResponse(__('Advertisement Offer Created Successfully'), $itemOffer);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> createItemOffer');
            ResponseService::errorResponse();
        }
    }

    /** Get Item Offer List */
    public function getItemOfferList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:seller,buyer',
            'search' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $authId = Auth::id();

            $baseQuery = Item::query();

            // Apply search filter
            $searchQuery = $request->input('search', '');
            if (!empty($searchQuery)) {
                $baseQuery->where(function ($q) use ($searchQuery, $request) {
                    $q->whereHas('item_offers', function ($subQ) use ($searchQuery, $request) {
                        $subQ->whereHas($this->getOtherUserRelation($request->type), function ($subQ) use ($searchQuery) {
                            $subQ->where('name', 'like', '%' . $searchQuery . '%');
                        });
                    })
                    ->orWhere('id', 'like', '%' . $searchQuery . '%')->orWhere('name', 'like', '%' . $searchQuery . '%');;
                });
            }

            $itemData = $baseQuery->whereHas('item_offers', function($subQuery) use($request, $authId){
                $subQuery->when($request->type === 'seller',
                    fn($q) => $q->where('seller_id', $authId)
                                ->whereNull('deleted_by_seller_at')
                                ->whereHas('buyer'),
                    fn($q) => $q->where('buyer_id', $authId)
                                ->whereNull('deleted_by_buyer_at')
                                ->whereHas('seller')
                );
            })->with(['item_offers' => function($subQuery) use($request, $authId) {
                $subQuery->when($request->type === 'seller',
                    fn($q) => $q->where('seller_id', $authId)
                                ->whereNull('deleted_by_seller_at')
                                ->whereHas('buyer')
                                ->with('buyer:id,name,profile'),
                    fn($q) => $q->where('buyer_id', $authId)
                                ->whereNull('deleted_by_buyer_at')
                                ->whereHas('seller')
                                ->with('seller:id,name,profile')
                )->when($request->type === 'seller',
                    fn($q) => $q->withCount(['sellerChat as unread_chat_count' => function($q) use($authId) {
                                    $q->where('is_read', 0)->where('sender_id', '!=', $authId);
                                }]),
                    fn($q) => $q->withCount(['buyerChat as unread_chat_count' => function($q) use($authId) {
                                    $q->where('is_read', 0)->where('sender_id', '!=', $authId);
                                }])
                )->withMax('chat', 'created_at');
            }])->select('id', 'name', 'price')
                ->orderByDesc(
                    Chat::select('created_at')
                        ->whereIn('item_offer_id',
                            ItemOffer::select('id')
                                ->whereColumn('item_id', 'items.id')
                                ->when($request->type === 'seller',
                                    fn($q) => $q->where('seller_id', $authId)->whereNull('deleted_by_seller_at'),
                                    fn($q) => $q->where('buyer_id', $authId)->whereNull('deleted_by_buyer_at')
                                )
                        )
                        ->latest('created_at')
                        ->limit(1)
                )
                ->paginate();

            $itemData->getCollection()->transform(function($data) use($request) {
                $totalOtherUsers = 0;
                if ($request->type === 'seller') {
                    $otherUserQuery = $data->item_offers->filter(fn($offer) => $offer->buyer !== null);
                    $totalOtherUsers = $otherUserQuery->count();
                    $otherUser = $otherUserQuery
                        ->take(6)
                        ->map(function($offer) {
                            $buyerData = $offer->buyer->toArray();
                            $buyerData['offer_id'] = $offer->id;
                            return $buyerData;
                        })->values();
                } else {
                    $otherUserQuery = $data->item_offers->filter(fn($offer) => $offer->seller !== null);
                    $totalOtherUsers = $otherUserQuery->count();
                    $otherUser = $otherUserQuery
                        ->take(6)
                        ->map(function($offer) {
                            $sellerData = $offer->seller->toArray();
                            $sellerData['offer_id'] = $offer->id;
                            return $sellerData;
                        })->values();
                }
                return [
                    'id'                 => $data->id,
                    'name'               => $data->name,
                    'price'              => $data->price,
                    'image'              => $data->image,
                    'last_offer_updated' => optional($data->item_offers->max('chat_max_created_at') ? Carbon::parse($data->item_offers->max('chat_max_created_at'), 'UTC') : $data->item_offers->max('updated_at'))?->toIso8601String(),
                    'unread_chat_count'  => $data->item_offers->sum('unread_chat_count'),
                    'other_users'        => $otherUser,
                    'total_other_users'  => $totalOtherUsers ?? 0,
                ];
            });

            $filtered = $itemData->getCollection()->filter(fn($item) => $item['other_users']->isNotEmpty())->values();
            $itemData->setCollection($filtered);

            return ResponseService::successResponse(
                __('Item Chat List Fetched Successfully'),
                $itemData
            );

        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, 'API Controller -> getItemChatList');
            return ResponseService::errorResponse('Something went wrong');
        }
    }

    /** Build a short, type-aware preview string for a chat list's last message */
    private function buildChatPreview(?Chat $chat, CurrencyFormatterService $formatter, $currency): ?string
    {
        if (!$chat) {
            return null;
        }

        return match ($chat->message_type) {
            'offer' => '💰 ' . __('Offered') . ': ' . $formatter->formatPrice($chat->amount, $currency),
            'audio' => '🎤 ' . __('Voice message'),
            'file' => '📷 ' . __('Photo'),
            'file_and_text' => '📷 ' . $chat->message,
            default => $chat->message,
        };
    }

    /** Get Chat List */
    public function getChatList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:seller,buyer',
            'item_id' => 'nullable|required_if:type,seller|exists:items,id',
            'search' => 'nullable|string|max:255',
            'item_offer_id' => 'nullable|exists:item_offers,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $authId = Auth::id();

            // Blocked By Current User
            $authUserBlockList  = BlockUser::where('user_id', $authId)->pluck('blocked_user_id');
            $otherUserBlockList = BlockUser::where('blocked_user_id', $authId)->pluck('user_id');

            if($request->item_id){
                $baseQuery = ItemOffer::where('item_id', $request->item_id);
            }else{
                $baseQuery = ItemOffer::query();
            }

            // Apply item_offer_id filter if provided
            $baseQuery->when($request->item_offer_id, fn($q) => $q->where('id', $request->item_offer_id));
            
            // Apply search filter
            $searchQuery = $request->input('search', '');
            if (!empty($searchQuery)) {
                $baseQuery->where(function ($q) use ($searchQuery, $request) {
                    $q->whereHas($this->getOtherUserRelation($request->type), function ($subQ) use ($searchQuery) {
                        $subQ->where('name', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhereHas('item', function ($subQ) use ($searchQuery) {
                        $subQ->where('name', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhereHas('chat', function ($subQ) use ($searchQuery) {
                        $subQ->where('message', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhere('id', 'like', '%' . $searchQuery . '%');
                });
            }

            $query = $baseQuery->with([
                'seller:id,name,profile',
                'buyer:id,name,profile',
                'item' => function ($q) {
                    $q->with([
                        'currency:id,iso_code,symbol,symbol_position,decimal_places,thousand_separator,decimal_separator',
                        'category:id,name,image,is_job_category,price_optional',
                        'gallery_images:id,item_id,image,is_default',
                        'user:id,name,profile',
                        'area:id,name',
                        'translations',
                    ]);
                },
                'item.review' => function ($q) use ($authId) {
                    $q->where('buyer_id', $authId);
                },
            ])
            ->whereHas('buyer', fn($q) => $q->whereNull('deleted_at'))
            ->whereHas('seller', fn($q) => $q->whereNull('deleted_at'))
            ->when($request->type === 'seller',
                fn($q) => $q->whereNull('deleted_by_seller_at'),
                fn($q) => $q->whereNull('deleted_by_buyer_at')
            )
            ->when($request->type === 'seller',
                fn($q) => $q->withCount([
                    'sellerChat as unread_chat_count' => function ($q) use ($authId) {
                        $q->where('is_read', 0)
                          ->where('sender_id', '!=', $authId);
                    },
                ]),
                fn($q) => $q->withCount([
                    'buyerChat as unread_chat_count' => function ($q) use ($authId) {
                        $q->where('is_read', 0)
                          ->where('sender_id', '!=', $authId);
                    },
                ])
            );

            if ($request->type === 'seller') {
                $query->where('seller_id', $authId);
            } else {
                $query->where('buyer_id', $authId);
            }

            $clearedColumn = $request->type === 'seller' ? 'cleared_by_seller_at' : 'cleared_by_buyer_at';

            $totalUnreadChatCount = (clone $query)->get()->sum('unread_chat_count');

            $itemOffers = $query
                ->addSelect([
                    'last_chat_time' => Chat::selectRaw('COALESCE(MAX(chats.created_at), item_offers.updated_at)')
                        ->whereColumn('item_offer_id', 'item_offers.id')
                        ->where(function ($q) use ($clearedColumn) {
                            $q->whereColumn('chats.created_at', '>', 'item_offers.' . $clearedColumn)
                              ->orWhereNull('item_offers.' . $clearedColumn);
                        })
                        ->limit(1)
                ])
                ->orderByRaw('CASE WHEN unread_chat_count > 0 THEN 0 ELSE 1 END')
                ->orderByDesc('last_chat_time')
                ->orderByDesc('id')
                ->paginate();

            $formatter = app(CurrencyFormatterService::class);

            $itemOffers->getCollection()->transform(function ($offer) use (
                $request,
                $authId,
                $authUserBlockList,
                $otherUserBlockList,
                $formatter,
                $clearedColumn
            ) {
                $userBlocked = $request->type === 'seller'
                    ? $authUserBlockList->contains($offer->buyer_id) || $otherUserBlockList->contains($offer->seller_id)
                    : $authUserBlockList->contains($offer->seller_id) || $otherUserBlockList->contains($offer->buyer_id);

                $isMyUserBlockedByOthers = $request->type === 'seller'
                    ? $otherUserBlockList->contains($offer->buyer_id) || $authUserBlockList->contains($offer->seller_id)
                    : $otherUserBlockList->contains($offer->seller_id) || $authUserBlockList->contains($offer->buyer_id);

                $offer->user_blocked = $userBlocked;
                $offer->is_my_user_blocked = $isMyUserBlockedByOthers;

                $lastChatTime = $offer->last_chat_time ?? $offer->updated_at;
                $offer->last_message_time = $lastChatTime ? Carbon::parse($lastChatTime) : null;
                unset($offer->last_chat_time);

                $offerCurrency = $offer->item?->currency;
                $offer->formatted_amount = $formatter->formatPrice($offer->amount, $offerCurrency);

                $lastChat = Chat::where('item_offer_id', $offer->id)
                    ->when($offer->{$clearedColumn}, function ($q) use ($offer, $clearedColumn) {
                        $q->where('created_at', '>', $offer->{$clearedColumn});
                    })
                    ->latest()
                    ->first();

                $offer->last_chat_message = $this->buildChatPreview($lastChat, $formatter, $offerCurrency);

                if ($offer->item) {
                    $review = optional($offer->item->review)->first();
                    $formattedItem = (new ItemApiResource(collect([$offer->item])))->asSingle()->resolve($request);
                    $formattedItem['status'] = $offer->item->status;
                    $formattedItem['review'] = $review;
                    $formattedItem['currency'] = $offer->item->currency ?: [
                        'iso_code' => Setting::getValue('currency_code'),
                        'name' => Setting::getValue('currency_name'),
                        'symbol' => Setting::getValue('currency_symbol'),
                        'symbol_position' => Setting::getValue('currency_symbol_position'),
                        'decimal_places' => Setting::getValue('decimal_places'),
                        'thousand_separator' => Setting::getValue('thousand_separator'),
                        'decimal_separator' => Setting::getValue('decimal_separator'),
                    ];
                    $offer->unsetRelation('item');
                    $offer->setAttribute('item', $formattedItem);
                }

                unset($offer->chat);

                return $offer;
            });

            return ResponseService::successResponse(
                __('Chat List Fetched Successfully'),
                $itemOffers,
                ['total_unread_chat_count' => $totalUnreadChatCount]
            );

        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getChatList');
            return ResponseService::errorResponse('Something went wrong');
        }
    }

    /** Send Message */
    public function sendMessage(Request $request)
    {
        /** 
         * Don't Remove Video/mp4 in audio validation required for IOS
         * Don't Remove Video/webm in audio validation required for Web
         * */
        $validator = Validator::make($request->all(), [
            'item_offer_id' => 'required|integer',
            'message' => (! $request->file('file') && ! $request->file('audio') && ! $request->has('amount')) ? 'required|string|max:5000' : 'nullable|string|max:5000',
            'file' => 'nullable|mimes:jpg,jpeg,png|max:7168',
            'audio' => 'nullable|mimetypes:audio/mpeg,audio/ogg,audio/mp4,audio/x-wav,text/plain,video/mp4,audio/x-m4a,video/webm|max:7168',
            'amount' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $authUserBlockList = BlockUser::where('user_id', $user->id)->get();
            $otherUserBlockList = BlockUser::where('blocked_user_id', $user->id)->get();

            $itemOffer = ItemOffer::with('item')->findOrFail($request->item_offer_id);
            if($itemOffer?->item?->status !== 'approved'){
                ResponseService::errorResponse(__('Item is not approved'));
            }
            if ($itemOffer->seller_id == $user->id) {
                $blockStatus = $authUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->seller_id && $data->blocked_user_id == $itemOffer->buyer_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because You have blocked this user'),array('key' => 'blocked_by_user'));
                }

                $blockStatus = $otherUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->buyer_id && $data->blocked_user_id == $itemOffer->seller_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because other user has blocked you.'),array('key' => 'blocked_by_other_user'));
                }
            } else {
                $blockStatus = $authUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->buyer_id && $data->blocked_user_id == $itemOffer->seller_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because You have blocked this user'),array('key' => 'blocked_by_user'));
                }

                $blockStatus = $otherUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->seller_id && $data->blocked_user_id == $itemOffer->buyer_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because other user has blocked you.'),array('key' => 'blocked_by_other_user'));
                }
            }
            if ($itemOffer->deleted_by_seller_at || $itemOffer->deleted_by_buyer_at) {
                $itemOffer->update([
                    'deleted_by_seller_at' => null,
                    'deleted_by_buyer_at' => null,
                ]);
            }

            $isOffer = $request->has('amount') && $request->amount !== null;
            if ($isOffer) {
                if ($itemOffer->item->price <= $request->amount) {
                    ResponseService::errorResponse(trans("Offer must be less than seller price"));
                }
                $itemOffer->amount = $request->amount;
                $itemOffer->save();
            }

            $chat = Chat::create([
                'sender_id' => Auth::user()->id,
                'item_offer_id' => $request->item_offer_id,
                'message' => $request->message,
                'file' => $request->hasFile('file') ? FileService::compressAndUpload($request->file('file'), 'chat') : '',
                'audio' => $request->hasFile('audio') ? FileService::compressAndUpload($request->file('audio'), 'chat') : '',
                'is_offer' => $isOffer,
                'amount' => $isOffer ? $request->amount : null,
                'is_read' => 0,
            ]);

            if ($itemOffer->seller_id == $user->id) {
                $receiver_id = $itemOffer->buyer_id;
                $userType = 'Seller';
            } else {
                $receiver_id = $itemOffer->seller_id;
                $userType = 'Buyer';
            }
            $notificationPayload = $chat->toArray();

            $unreadMessagesCount = Chat::where('item_offer_id', $itemOffer->id)
                ->where('is_read', 0)
                ->count();
            $formatter = app(CurrencyFormatterService::class);
            $offerCurrency = $itemOffer->item?->currency;
            $formattedOfferAmount = $formatter->formatPrice($itemOffer->amount, $offerCurrency);
            $formattedItemPrice = $formatter->formatPrice($itemOffer->item->price, $offerCurrency);

            $fcmMsg = [
                ...$notificationPayload,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_profile' => $user->profile,
                'user_type' => $userType,
                'item_id' => $itemOffer->item->id,
                'item_name' => $itemOffer->item->name,
                'item_image' => $itemOffer->item->image,
                'item_price' => $itemOffer->item->price,
                'item_offer_id' => $itemOffer->id,
                'item_offer_amount' => $itemOffer->amount,
                'type' => $notificationPayload['message_type'],
                'message_type_temp' => $notificationPayload['message_type'],
                'unread_count' => $unreadMessagesCount,
                'item_formatted_amount' => $formattedOfferAmount,
                'item_formatted_price' => $formattedItemPrice
            ];
            unset($fcmMsg['message_type']);
            $displayMessage = $request->message;
            if (empty($displayMessage)) {
                if ($isOffer) {
                    $displayMessage = '💰 Offered: ' . $formattedOfferAmount;
                } elseif ($request->hasFile('file')) {
                    $mime = $request->file('file')->getMimeType();

                    if (str_contains($mime, 'image')) {
                        $displayMessage = '📷 Sent you an image';
                    } elseif (str_contains($mime, 'pdf')) {
                        $displayMessage = '📄 Sent you a PDF file';
                    } elseif (str_contains($mime, 'word')) {
                        $displayMessage = '📘 Sent you a document';
                    } elseif (str_contains($mime, 'text')) {
                        $displayMessage = '📄 Sent you a text file';
                    } else {
                        $displayMessage = '📎 Sent you a file';
                    }
                } elseif ($request->hasFile('audio')) {
                    $displayMessage = '🎤 Sent you an audio message';
                } else {
                    $displayMessage = '💬 Sent you a message';
                }
            }
            DB::commit();

            $notificationTitle = ($user->name ?? 'User') . ' • ' . ($itemOffer->item->name ?? 'Item');
            NotificationService::dispatchChunkedNotifications(
                $notificationTitle,
                $displayMessage,
                'chat',
                $fcmMsg,
                false,
                array($receiver_id),
                true
            );

            // APP side Request to return its custom client id passed in payload
            $chat->client_id = $request->client_id ?? null;
            $chat->formatted_amount = $isOffer ? $formattedOfferAmount : null;
            $responseDisplayMessage = $request->message;
            if (empty($responseDisplayMessage)) {
                if ($isOffer) {
                    $responseDisplayMessage = '💰 ' . __('Offered:') . ' ' . $formattedOfferAmount;
                } elseif ($request->hasFile('file')) {
                    $mime = $request->file('file')->getMimeType();

                    if (str_contains($mime, 'image')) {
                        $responseDisplayMessage = '📷 ' . __('Sent you an image');
                    } elseif (str_contains($mime, 'pdf')) {
                        $responseDisplayMessage = '📄 ' . __('Sent you a PDF file');
                    } elseif (str_contains($mime, 'word')) {
                        $responseDisplayMessage = '📘 ' . __('Sent you a document');
                    } elseif (str_contains($mime, 'text')) {
                        $responseDisplayMessage = '📄 ' . __('Sent you a text file');
                    } else {
                        $responseDisplayMessage = '📎 ' . __('Sent you a file');
                    }
                } elseif ($request->hasFile('audio')) {
                    $responseDisplayMessage = '🎤 ' . __('Sent you an audio message');
                } else {
                    $responseDisplayMessage = '💬 ' . __('Sent you a message');
                }
            }
            $chat->last_chat_message = $responseDisplayMessage;
            ResponseService::successResponse(__('Message Fetched Successfully'), $chat);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> sendMessage');
            ResponseService::errorResponse();
        }
    }

    /** Get Chat Messages */
    public function getChatMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_offer_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $itemOffer = ItemOffer::owner()->with('item.currency')->findOrFail($request->item_offer_id);
            $authUserId = Auth::user()->id;
            $offerCurrency = $itemOffer->item?->currency;
            $formatter = app(CurrencyFormatterService::class);

            $clearedAt = null;
            if ($itemOffer->seller_id == $authUserId) {
                $clearedAt = $itemOffer->cleared_by_seller_at;
            } elseif ($itemOffer->buyer_id == $authUserId) {
                $clearedAt = $itemOffer->cleared_by_buyer_at;
            }

            $chat = Chat::where('item_offer_id', $itemOffer->id)
                ->where(function ($query) use ($authUserId) {
                    $query->where('sender_id', '!=', $authUserId)
                        ->orWhere(function ($q) use ($authUserId) {
                            $q->where('sender_id', $authUserId)
                                ->whereNull('deleted_by_sender_at');
                        });
                })
                ->when($clearedAt, function ($query) use ($clearedAt) {
                    $query->where('created_at', '>', $clearedAt);
                })
                ->orderBy('created_at', 'DESC')
                ->paginate();

            Chat::where('item_offer_id', $itemOffer->id)
                ->where('sender_id', '!=', $authUserId)
                ->whereIn('id', $chat->pluck('id'))
                ->update(['is_read' => '1']);

            $chat->getCollection()->transform(function ($message) use ($formatter, $offerCurrency) {
                $message->formatted_amount = $message->is_offer ? $formatter->formatPrice($message->amount, $offerCurrency) : null;
                return $message;
            });

            ResponseService::successResponse(__('Messages Fetched Successfully'), $chat);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getChatMessages');
            ResponseService::errorResponse();
        }
    }

    /**
     * Walk parent_category_id up from the item's category, returning it plus all its ancestors.
     * A chat template is stored against the topmost category the admin selected, so matching an
     * item to a template means checking whether any ancestor (not descendant) was chosen.
     */
    private function categoryAncestorIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $currentId = $categoryId;

        while ($currentId) {
            $parentId = Category::without('translations')->where('id', $currentId)->value('parent_category_id');
            if (!$parentId) {
                break;
            }
            $ids[] = $parentId;
            $currentId = $parentId;
        }

        return $ids;
    }

    /** Get Chat Template Predefined Questions */
    public function getChatTemplateQuestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $item = Item::without('translations')->find($request->item_id);
            if (!$item) {
                return ResponseService::errorResponse(__('Item Not Found'));
            }
            $role = Auth::id() == $item->user_id ? 'seller' : 'customer';

            $categoryIds = $item->category_id ? $this->categoryAncestorIds($item->category_id) : [];

            $templates = ChatTemplate::where('status', 1)
                ->where(function ($query) use ($categoryIds) {
                    $query->where('is_global', 1);
                    if (!empty($categoryIds)) {
                        $query->orWhereHas('categories', function ($subQuery) use ($categoryIds) {
                            $subQuery->whereIn('categories.id', $categoryIds);
                        });
                    }
                })
                ->with(['questions' => function ($query) use ($role) {
                    $query->where('role', $role)->orderBy('sequence')->with('translations');
                }])
                ->get();

            $questions = $templates
                ->flatMap(fn ($template) => $template->questions)
                ->map(fn ($question) => $question->translated_question)
                ->filter()
                ->unique()
                ->values();

            return ResponseService::successResponse(__('Chat Template Questions Fetched Successfully'), $questions);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getChatTemplateQuestions');

            return ResponseService::errorResponse();
        }
    }

    /** Delete Chat */
    public function deleteChat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_offer_id' => 'required|array',
            'item_offer_id.*' => 'exists:item_offers,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $authUserId = Auth::id();
            $ids = $request->item_offer_id;

            $baseQuery = ItemOffer::owner()->whereIn('id', $ids);

            if (!$baseQuery->exists()) {
                return ResponseService::errorResponse(__('No chat found'));
            }

            // Update seller records
            $baseQuery->clone()
                ->where('seller_id', $authUserId)
                ->update([
                    'deleted_by_seller_at' => now(),
                    'cleared_by_seller_at' => now(),
                ]);

            // Update buyer records
            $baseQuery->clone()
                ->where('buyer_id', $authUserId)
                ->update([
                    'deleted_by_buyer_at' => now(),
                    'cleared_by_buyer_at' => now(),
                ]);

            DB::commit();

            return ResponseService::successResponse(__('Chat Deleted Successfully'));

        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> deleteChat');
            return ResponseService::errorResponse();
        }
    }

    /** Delete Chat Messages */
    public function deleteChatMessages(Request $request)
    {
        try {

            // $messageIds = $request->message_ids;

            // if (is_string($messageIds)) {
            //     $decoded = json_decode($messageIds, true);

            //     if (json_last_error() === JSON_ERROR_NONE) {
            //         $request->merge([
            //             'message_ids' => $decoded
            //         ]);
            //     }
            // }

            $validator = Validator::make($request->all(), [
                'message_ids'   => 'required|array|min:1',
                'message_ids.*' => 'integer|exists:chats,id',
                'item_offer_id' => 'required|integer|exists:item_offers,id',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            DB::beginTransaction();

            $userId = Auth::id();

            $itemOffer = ItemOffer::where('id', $request->item_offer_id)
                ->where(function ($q) use ($userId) {
                    $q->where('seller_id', $userId)
                        ->orWhere('buyer_id', $userId);
                })
                ->first();

            if (!$itemOffer) {
                return ResponseService::errorResponse(__('Invalid item offer'));
            }

            $messages = Chat::where('item_offer_id', $itemOffer->id)
                ->whereIn('id', $request->message_ids)
                ->where('sender_id', $userId)
                ->get();

            if ($messages->isEmpty()) {
                return ResponseService::errorResponse(__('You can only delete your own messages'));
            }

            $deletedCount = Chat::where('item_offer_id', $itemOffer->id)
                ->whereIn('id', $request->message_ids)
                ->where('sender_id', $userId)
                ->whereNull('deleted_by_sender_at')
                ->update(['deleted_by_sender_at' => now()]);

            DB::commit();

            return ResponseService::successResponse(
                __('Messages Deleted Successfully'),
                [
                    'deleted_count' => $deletedCount,
                    'deleted_ids'   => $messages->pluck('id')->toArray()
                ]
            );

        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> deleteChatMessages');
            return ResponseService::errorResponse(__('Something went wrong'));
        }
    }
}
