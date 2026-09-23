<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlockUser;
use App\Models\Chat;
use App\Models\Item;
use App\Models\ItemOffer;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\CachingService;
use App\Services\CurrencyFormatterService;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class AdminChatController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['admin-chat-manage']);
        
        $adminUser = $this->getAdminUser();
        
        if (!$adminUser) {
            return redirect()->back()->with('error', __('Super Admin user not found. Please ensure a user with the Super Admin role exists.'));
        }
        
        $firebaseProjectId = $this->getSetting('firebase_project_id');
        $serviceFile = $this->getSetting('service_file');
        
        $firebaseServerConfigured = !empty($firebaseProjectId) && !empty($serviceFile);
        
        $firebaseWebConfig = $this->getSetting([
            'apiKey', 
            'authDomain', 
            'projectId', 
            'storageBucket', 
            'messagingSenderId', 
            'appId',
            'vapidKey'
        ]);
        
        $firebaseWebConfigured = !empty($firebaseWebConfig['apiKey']) && 
                                 !empty($firebaseWebConfig['projectId']) && 
                                 !empty($firebaseWebConfig['messagingSenderId']) &&
                                 !empty($firebaseWebConfig['authDomain']) &&
                                 !empty($firebaseWebConfig['appId']);
        
        $placeholderImage = $this->getSetting('placeholder_image') ?: asset('assets/images/logo/placeholder.png');
        
        return view('admin-chat.index', compact(
            'firebaseProjectId', 
            'firebaseServerConfigured', 
            'firebaseWebConfig', 
            'firebaseWebConfigured', 
            'placeholderImage', 
            'adminUser'
        ));
    }

    /**
     */
    private function getAdminUser()
    {
        return User::role('Super Admin')->first();
    }

    /**
     * 
     * @param string|array $key
     * @return mixed
     */
    private function getSetting($key)
    {
        $cachedValue = CachingService::getSystemSettings($key);
        
        if (is_string($key)) {
            if (empty($cachedValue)) {
                $dbValue = Setting::where('name', $key)->value('value');
                if (!empty($dbValue)) {
                    CachingService::removeCache(config('constants.CACHE.SETTINGS'));
                    return $dbValue;
                }
            }
            return $cachedValue;
        }
        
        if (is_array($key)) {
            $result = [];
            $needsCacheClear = false;
            
            if (!is_array($cachedValue)) {
                $cachedValue = [];
            }
            
            foreach ($key as $settingKey) {
                $value = $cachedValue[$settingKey] ?? '';
                if (empty($value)) {
                    $dbValue = Setting::where('name', $settingKey)->value('value');
                    if (!empty($dbValue)) {
                        $result[$settingKey] = $dbValue;
                        $needsCacheClear = true;
                    } else {
                        $result[$settingKey] = '';
                    }
                } else {
                    $result[$settingKey] = $value;
                }
            }
            
            if ($needsCacheClear) {
                CachingService::removeCache(config('constants.CACHE.SETTINGS'));
            }
            
            return $result;
        }
        
        return $cachedValue;
    }

    public function getProducts(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');
            
            $adminUser = $this->getAdminUser();
            
            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found. Please ensure a user with the Super Admin role exists.'));
            }
                            
            $query = Item::where('user_id', $adminUser->id)
                ->where('status', 'approved')
                ->with('currency')
                ->whereHas('item_offers', function ($q) {
                    $q->whereNull('deleted_by_seller_at')
                      ->whereHas('buyer', fn($b) => $b->whereNull('deleted_at'));
                })
                ->with(['item_offers' => function ($q) use ($adminUser) {
                    $q->whereNull('deleted_by_seller_at')
                      ->whereHas('buyer', fn($b) => $b->whereNull('deleted_at'))
                      ->with(['buyer:id,name,profile', 'sellerChat' => function ($q) {
                          $q->whereNull('deleted_by_sender_at')
                            ->latest('created_at')
                            ->select('id', 'item_offer_id', 'created_at');
                      }])
                      ->withCount([
                          'sellerChat as unread_chat_count' => function ($query) use ($adminUser) {
                              $query->where('is_read', 0)
                                  ->where('sender_id', '!=', $adminUser->id)
                                  ->whereNull('deleted_by_sender_at');
                          },
                    ]);
                }]);

            // Search by item name or description
            $searchQuery = $request->input('search', '');
            if (!empty($searchQuery)) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('description', 'like', '%' . $searchQuery . '%')
                      ->orWhere('id', 'like', '%' . $searchQuery . '%');
                });
            }

            $items = $query->addSelect(['last_chat_time' => \App\Models\Chat::select('chats.created_at')
                    ->whereIn('chats.item_offer_id', function ($q) {
                        $q->select('id')->from('item_offers')->whereColumn('item_offers.item_id', 'items.id');
                    })
                    ->orderByDesc('chats.created_at')
                    ->limit(1)
                ])
                ->orderByDesc('last_chat_time')
                ->orderByDesc('created_at')
                ->paginate(20);

            // Get placeholder image from settings (with database fallback)
            $placeholderImage = $this->getSetting('placeholder_image') ?: asset('assets/images/logo/placeholder.png');
            
            // Format products with currency formatting
            $formatter = app(CurrencyFormatterService::class);
            $products = $items->getCollection()->map(function ($item) use ($formatter, $placeholderImage) {
                $currency = $item->currency ?? null;
                $formattedPrice = '';
                
                // Only format if price exists and is greater than 0
                if ($item->price && $item->price > 0) {
                    try {
                        $formattedPrice = $formatter->formatPrice($item->price, $currency);
                    } catch (\Throwable $e) {
                        // Fallback if formatting fails
                        $formattedPrice = '$ ' . number_format($item->price, 2);
                    }
                }
                // Calculate total unread chats for this product
                $unreadCount = 0;
                $lastMessageTime = null;
                $chatters = [];
                
                if ($item->relationLoaded('item_offers')) {
                    $unreadCount = $item->item_offers->sum('unread_chat_count');

                    // Compute max non-deleted message time per offer from the eagerly loaded sellerChat collection
                    $validOffers = $item->item_offers->map(function ($offer) {
                        $latestMsg = $offer->sellerChat->first(); // already sorted latest first, deleted excluded
                        $offer->_computed_last_msg_at = $latestMsg ? $latestMsg->created_at : null;
                        return $offer;
                    })->filter(fn($offer) => $offer->_computed_last_msg_at !== null)
                      ->sortByDesc('_computed_last_msg_at');

                    if ($validOffers->isNotEmpty()) {
                        $lastMessageTime = $validOffers->first()->_computed_last_msg_at;
                    }

                    foreach ($validOffers as $offer) {
                        if ($offer->buyer) {
                            $chatters[] = $offer->buyer->profile ?: $placeholderImage;
                        }
                    }

                    $chatters = array_values(array_unique($chatters));
                }
                
                return [
                    'id' => (string) $item->id,
                    'name' => $item->name ?? '',
                    'image' => $item->image ?: $placeholderImage,
                    'price' => $item->price ?? 0,
                    'formatted_price' => $formattedPrice,
                    'description' => $item->description ?? '',
                    'unread_chat_count' => $unreadCount,
                    'last_message_time' => $lastMessageTime ? Carbon::parse($lastMessageTime) : null,
                    'chatters' => $chatters,
                ];
            })->toArray();
            
            return ResponseService::successResponse(__('Products fetched successfully'), [
                'data' => $products,
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'has_more' => $items->hasMorePages(),
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> getProducts');
            return ResponseService::errorResponse(__('Failed to fetch products'));
        }
    }

    public function getChatList(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');
            
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $productId = $request->input('product_id');
            
            // Get admin user
            $adminUser = $this->getAdminUser();
            
            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found. Please ensure a user with the Super Admin role exists.'));
            }

            $adminUserId = $adminUser->id;
            
            // Verify product (item) exists and belongs to admin user
            $product = Item::where('id', $productId)
                ->where('user_id', $adminUserId)
                ->first();
            
            if (!$product) {
                return ResponseService::errorResponse(__('Product not found or does not belong to admin user'));
            }

            // Search by buyer name, item name, or message content
            $searchQuery = $request->input('search', '');
            
            // Get all item offers where admin user is the seller (as per API - seller type)
            // Filter by item_id matching the selected product
            // One item offer = one chat
            $query = ItemOffer::with([
                'seller:id,name,profile',
                'buyer:id,name,profile',
                'item' => function ($q) {
                    $q->with(['currency:id,iso_code,symbol,symbol_position', 'category:id,name,image,is_job_category,price_optional']);
                },
                'sellerChat' => function ($q) {
                    $q->whereNull('deleted_by_sender_at')
                        ->latest('updated_at')
                        ->select('id', 'created_at', 'updated_at', 'item_offer_id', 'is_read', 'sender_id', 'message', 'audio', 'file', 'is_offer', 'amount');
                }
            ])
                ->where('seller_id', $adminUserId) // Admin is the seller
                ->where('item_id', $productId) // Filter by selected product
                ->whereHas('buyer', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->whereNull('deleted_by_seller_at') // Exclude chats deleted by seller
                ->withCount([
                    'sellerChat as unread_chat_count' => function ($query) use ($adminUserId) {
                        $query->where('is_read', 0)
                            ->where('sender_id', '!=', $adminUserId)
                            ->whereNull('deleted_by_sender_at');
                    },
                ]);

            // Apply search filter
            if (!empty($searchQuery)) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('buyer', function ($subQ) use ($searchQuery) {
                        $subQ->where('name', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhereHas('item', function ($subQ) use ($searchQuery) {
                        $subQ->where('name', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhereHas('sellerChat', function ($subQ) use ($searchQuery) {
                        $subQ->where('message', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhere('id', 'like', '%' . $searchQuery . '%');
                });
            }

            $itemOffers = $query->orderByDesc('unread_chat_count')
                ->orderByDesc(function ($q) {
                    $q->select('created_at')
                        ->from('chats')
                        ->whereColumn('item_offer_id', 'item_offers.id')
                        ->whereNull('deleted_by_sender_at')
                        ->orderByDesc('created_at')
                        ->limit(1);
                })
                ->orderBy('id', 'DESC')
                ->paginate(20);

            // Get placeholder image from settings (with database fallback)
            $placeholderImage = $this->getSetting('placeholder_image') ?: asset('assets/images/logo/placeholder.png');
            
            // Format the response
            $currencyFormatter = new CurrencyFormatterService();
            $itemOffers->getCollection()->transform(function ($offer) use ($adminUserId, $placeholderImage, $currencyFormatter) {
                $latestChat = $offer->sellerChat->first();
                $offer->last_message_time = $latestChat ? $latestChat->created_at : null;

                // Determine last message text
                if ($latestChat) {
                    if (!empty($latestChat->is_offer)) {
                        $offer->last_message = '💰 ' . __('Offer') . ': ' . $currencyFormatter->formatPrice($latestChat->amount, $offer->item?->currency);
                    } elseif (!empty($latestChat->message)) {
                        $offer->last_message = $latestChat->message;
                    } elseif (!empty($latestChat->file)) {
                        $offer->last_message = '📷 Image';
                    } elseif (!empty($latestChat->audio)) {
                        $offer->last_message = '🎤 Audio';
                    } else {
                        $offer->last_message = '';
                    }
                } else {
                    $offer->last_message = '';
                }
                
                // Determine the other user (buyer, since admin is seller)
                $otherUser = $offer->buyer;
                if ($otherUser && empty($otherUser->profile)) {
                    $otherUser->profile = $placeholderImage;
                }
                $offer->other_user = $otherUser;
                
                return $offer;
            });

            return ResponseService::successResponse(
                __('Chat List Fetched Successfully'),
                [
                    'data' => $itemOffers->items(),
                    'current_page' => $itemOffers->currentPage(),
                    'last_page' => $itemOffers->lastPage(),
                    'per_page' => $itemOffers->perPage(),
                    'total' => $itemOffers->total(),
                    'has_more' => $itemOffers->hasMorePages(),
                ]
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> getChatList');
            return ResponseService::errorResponse(__('Failed to fetch chat list'));
        }
    }

    public function getChatMessages(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');
            
            $validator = Validator::make($request->all(), [
                'item_offer_id' => 'required|integer|exists:item_offers,id',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $adminUser = $this->getAdminUser();
            
            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found. Please ensure a user with the Super Admin role exists.'));
            }

            $adminUserId = $adminUser->id;
            $itemOffer = ItemOffer::with(['item.currency', 'item.category:id,is_job_category'])->findOrFail($request->item_offer_id);

            // Verify that admin user is the seller in this item offer
            if ($itemOffer->seller_id != $adminUser->id) {
                return ResponseService::errorResponse(__('You do not have permission to send messages in this chat'));
            }

            // Check if other user has blocked admin
            $authUserBlockList = BlockUser::where(['blocked_user_id' => $adminUser->id, 'user_id' => $itemOffer->buyer_id])->get();
            if (count($authUserBlockList) > 0) {
                return ResponseService::errorResponse(__('You Cannot send message because other user has blocked you.'));
            }
            
            // Note: We allow sending messages even when admin has blocked the user.
            // The UI will hide the input but backend allows it for flexibility.
            
            // Mark ALL unread messages as read FIRST (before pagination)
            // Mark messages where admin user is the receiver (messages from buyer, where admin is seller)
            // Since admin is seller, messages from buyer (sender_id != adminUserId) are messages where admin is receiver
            Chat::where('item_offer_id', $itemOffer->id)
                ->where('sender_id', '!=', $adminUserId) // Messages from buyer (admin is receiver)
                ->where('is_read', 0) // Only unread messages
                ->whereNull('deleted_by_sender_at') // Not deleted by sender
                ->update(['is_read' => 1]);
            
            // Filter messages: exclude messages deleted by sender, but only if admin user is the sender
            // If admin user is receiver, show all messages (including deleted ones)
            // Also exclude messages created before admin cleared the chat
            $clearedAt = $itemOffer->cleared_by_seller_at;

            $chat = Chat::where('item_offer_id', $itemOffer->id)
                ->where(function ($query) use ($adminUserId) {
                    $query->where('sender_id', '!=', $adminUserId) // Messages from buyer (always show)
                        ->orWhere(function ($q) use ($adminUserId) {
                            $q->where('sender_id', $adminUserId) // Messages from admin user
                                ->whereNull('deleted_by_sender_at'); // Only show if not deleted by sender
                        });
                })
                ->when($clearedAt, function ($query) use ($clearedAt) {
                    $query->where('created_at', '>', $clearedAt);
                })
                ->orderBy('created_at', 'DESC')
                ->paginate();

            // Check if admin has blocked the other user
            $isBlocked = BlockUser::where([
                'user_id' => $adminUserId,
                'blocked_user_id' => $itemOffer->buyer_id,
            ])->exists();

            // Build offer info (buyer's offer + item price) for the chat header/bubble
            $currencyFormatter = new CurrencyFormatterService();
            $item = $itemOffer->item;
            $itemCurrency = $item?->currency;

            // Add is_blocked and formatted amount to each item
            $chat->getCollection()->transform(function ($message) use ($isBlocked, $currencyFormatter, $itemCurrency) {
                $message->is_blocked = $isBlocked;
                if ($message->is_offer) {
                    $message->amount_formatted = $currencyFormatter->formatPrice($message->amount, $itemCurrency);
                }
                return $message;
            });

            $responseData = $chat->toArray();
            $responseData['offer'] = [
                'amount'               => $itemOffer->amount,
                'amount_formatted'     => $currencyFormatter->formatPrice($itemOffer->amount, $itemCurrency),
                'item_name'            => $item?->name,
                'item_price'           => $item?->price,
                'item_price_formatted' => $currencyFormatter->formatPrice($item?->price, $itemCurrency),
                'is_job_category'      => (bool) ($item?->category?->is_job_category),
            ];

            return ResponseService::successResponse(__('Messages Fetched Successfully'), $responseData);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> getChatMessages');
            return ResponseService::errorResponse(__('Failed to fetch messages'));
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');
            
            $validator = Validator::make($request->all(), [
                'item_offer_id' => 'required|integer|exists:item_offers,id',
                'message' => (! $request->file('file') && ! $request->file('audio')) ? 'required|string|max:5000' : 'nullable|string|max:5000',
                'file' => 'nullable|mimes:jpg,jpeg,png|max:7168',
                'audio' => 'nullable|mimetypes:audio/mpeg,video/webm,audio/ogg,video/mp4,audio/x-wav,text/plain|max:7168',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $adminUser = $this->getAdminUser();

            // Get Item Offer
            $itemOffer = ItemOffer::with('item')->findOrFail($request->item_offer_id);
            
            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found. Please ensure a user with the Super Admin role exists.'));
            }

            $authUserBlockList = BlockUser::where(['blocked_user_id' => $adminUser->id, 'user_id' => $itemOffer->buyer_id])->get();
            if(count($authUserBlockList) > 0) {
                return ResponseService::errorResponse(__('You Cannot send message because other user has blocked you.'));
            }

            DB::beginTransaction();
            
            // Verify that admin user is the seller in this item offer
            if ($itemOffer->seller_id != $adminUser->id) {
                DB::rollBack();
                return ResponseService::errorResponse(__('You do not have permission to send messages in this chat'));
            }

            // Clear delete flags so chat reappears for both parties on new message
            if ($itemOffer->deleted_by_seller_at || $itemOffer->deleted_by_buyer_at) {
                $itemOffer->update([
                    'deleted_by_seller_at' => null,
                    'deleted_by_buyer_at' => null,
                ]);
            }

            // Create chat message - admin user is always the sender
            $chat = Chat::create([
                'sender_id' => $adminUser->id,
                'item_offer_id' => $request->item_offer_id,
                'message' => $request->message ?? '',
                'file' => $request->hasFile('file') ? FileService::compressAndUpload($request->file('file'), 'chat') : '',
                'audio' => $request->hasFile('audio') ? FileService::compressAndUpload($request->file('audio'), 'chat') : '',
                'is_read' => 0,
            ]);

            // Admin user is always the seller, so receiver is always the buyer
            $receiverId = $itemOffer->buyer_id;
            $userType = 'Seller';

            $notificationPayload = $chat->toArray();

            $unreadMessagesCount = Chat::where('item_offer_id', $itemOffer->id)
                ->where('is_read', 0)
                ->count();

            $fcmMsg = [
                ...$notificationPayload,
                'user_id' => $adminUser->id,
                'user_name' => $adminUser->name,
                'user_profile' => $adminUser->profile,
                'user_type' => $userType,
                'item_id' => $itemOffer->item->id ?? null,
                'item_name' => $itemOffer->item->name ?? '',
                'item_image' => $itemOffer->item->image ?? '',
                'item_price' => $itemOffer->item->price ?? 0,
                'item_offer_id' => $itemOffer->id,
                'item_offer_amount' => $itemOffer->amount,
                'type' => $notificationPayload['message_type'] ?? 'text', // Keep original message type (text, file, audio, etc.)
                'message_type_temp' => $notificationPayload['message_type'] ?? 'text',
                'unread_count' => $unreadMessagesCount,
            ];

            unset($fcmMsg['message_type']);

            $displayMessage = $request->message;
            if (empty($displayMessage)) {
                if ($request->hasFile('file')) {
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

            // Send notification
            if ($receiverId) {
                NotificationService::dispatchChunkedNotifications(
                    $request->title ?? __('New Message'),
                    $displayMessage,
                    'chat',
                    $fcmMsg,
                    false,
                    [$receiverId],
                    true
                );
            }

            return ResponseService::successResponse(__('Message sent successfully'), $chat);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> sendMessage');
            return ResponseService::errorResponse(__('Failed to send message'));
        }
    }

    public function sendOffer(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');

            $validator = Validator::make($request->all(), [
                'item_offer_id' => 'required|integer|exists:item_offers,id',
                'amount' => 'required|numeric|min:0.01',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $adminUser = $this->getAdminUser();

            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found. Please ensure a user with the Super Admin role exists.'));
            }

            $itemOffer = ItemOffer::with(['item.currency', 'item.category:id,is_job_category'])->findOrFail($request->item_offer_id);

            if ($itemOffer->seller_id != $adminUser->id) {
                return ResponseService::errorResponse(__('You do not have permission to send offers in this chat'));
            }

            if ($itemOffer->item?->category?->is_job_category) {
                return ResponseService::errorResponse(__('Offers are not allowed on job listings'));
            }

            $authUserBlockList = BlockUser::where(['blocked_user_id' => $adminUser->id, 'user_id' => $itemOffer->buyer_id])->get();
            if (count($authUserBlockList) > 0) {
                return ResponseService::errorResponse(__('You Cannot send message because other user has blocked you.'));
            }

            if ($itemOffer->item->price <= $request->amount) {
                return ResponseService::errorResponse(__('Offer must be less than seller price'));
            }

            DB::beginTransaction();

            if ($itemOffer->deleted_by_seller_at || $itemOffer->deleted_by_buyer_at) {
                $itemOffer->update([
                    'deleted_by_seller_at' => null,
                    'deleted_by_buyer_at' => null,
                ]);
            }

            $itemOffer->amount = $request->amount;
            $itemOffer->save();

            $chat = Chat::create([
                'sender_id' => $adminUser->id,
                'item_offer_id' => $itemOffer->id,
                'message' => '',
                'is_offer' => true,
                'amount' => $request->amount,
                'is_read' => 0,
            ]);

            $receiverId = $itemOffer->buyer_id;

            $unreadMessagesCount = Chat::where('item_offer_id', $itemOffer->id)
                ->where('is_read', 0)
                ->count();

            $currencyFormatter = new CurrencyFormatterService();
            $offerCurrency = $itemOffer->item?->currency;
            $formattedOfferAmount = $currencyFormatter->formatPrice($itemOffer->amount, $offerCurrency);

            $fcmMsg = [
                ...$chat->toArray(),
                'user_id' => $adminUser->id,
                'user_name' => $adminUser->name,
                'user_profile' => $adminUser->profile,
                'user_type' => 'Seller',
                'item_id' => $itemOffer->item->id ?? null,
                'item_name' => $itemOffer->item->name ?? '',
                'item_image' => $itemOffer->item->image ?? '',
                'item_price' => $itemOffer->item->price ?? 0,
                'item_formatted_amount' => $formattedOfferAmount,
                'item_offer_id' => $itemOffer->id,
                'item_offer_amount' => $itemOffer->amount,
                'type' => 'offer',
                'message_type_temp' => 'offer',
                'unread_count' => $unreadMessagesCount,
            ];
            unset($fcmMsg['message_type']);

            DB::commit();

            if ($receiverId) {
                NotificationService::dispatchChunkedNotifications(
                    $request->title ?? __('New Message'),
                    '💰 ' . __('Sent an offer of') . ' ' . $formattedOfferAmount,
                    'chat',
                    $fcmMsg,
                    false,
                    [$receiverId],
                    true
                );
            }

            return ResponseService::successResponse(__('Offer sent successfully'), [
                'chat' => $chat,
                'amount' => $itemOffer->amount,
                'amount_formatted' => $formattedOfferAmount,
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> sendOffer');
            return ResponseService::errorResponse(__('Failed to send offer'));
        }
    }

    public function deleteChat(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');

            // Support both single ID (integer) and multiple IDs (array)
            $ids = $request->item_offer_id;
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            $request->merge(['item_offer_id' => $ids]);

            $validator = Validator::make($request->all(), [
                'item_offer_id' => 'required|array|min:1',
                'item_offer_id.*' => 'integer|exists:item_offers,id',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $adminUser = $this->getAdminUser();

            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found. Please ensure a user with the Super Admin role exists.'));
            }

            DB::beginTransaction();

            $deletedCount = ItemOffer::whereIn('id', $ids)
                ->where('seller_id', $adminUser->id)
                ->update([
                    'deleted_by_seller_at' => now(),
                    'cleared_by_seller_at' => now(),
                ]);

            if ($deletedCount === 0) {
                DB::rollBack();
                return ResponseService::errorResponse(__('Chat not found or you do not have permission to delete it'));
            }

            DB::commit();

            return ResponseService::successResponse(__('Chat Deleted Successfully'), [
                'deleted_count' => $deletedCount,
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> deleteChat');
            return ResponseService::errorResponse(__('Failed to delete chat'));
        }
    }

    public function deleteChatMessages(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');

            $messageIds = $request->message_ids;

            if (is_string($messageIds)) {
                $decoded = json_decode($messageIds, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge(['message_ids' => $decoded]);
                }
            }

            $validator = Validator::make($request->all(), [
                'message_ids'   => 'required|array|min:1',
                'message_ids.*' => 'integer|exists:chats,id',
                'item_offer_id' => 'required|integer|exists:item_offers,id',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $adminUser = $this->getAdminUser();

            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found. Please ensure a user with the Super Admin role exists.'));
            }

            DB::beginTransaction();

            $itemOffer = ItemOffer::where('id', $request->item_offer_id)
                ->where('seller_id', $adminUser->id)
                ->first();

            if (!$itemOffer) {
                DB::rollBack();
                return ResponseService::errorResponse(__('Invalid item offer'));
            }

            $messages = Chat::where('item_offer_id', $itemOffer->id)
                ->whereIn('id', $request->message_ids)
                ->where('sender_id', $adminUser->id)
                ->get();

            if ($messages->isEmpty()) {
                DB::rollBack();
                return ResponseService::errorResponse(__('You can only delete your own messages'));
            }

            $deletedCount = Chat::where('item_offer_id', $itemOffer->id)
                ->whereIn('id', $request->message_ids)
                ->where('sender_id', $adminUser->id)
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
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> deleteChatMessages');
            return ResponseService::errorResponse(__('Something went wrong'));
        }
    }

    public function registerFcmToken(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');
            
            $validator = Validator::make($request->all(), [
                'fcm_token' => 'required|string',
                'platform_type' => 'nullable|string|in:web,android,ios',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $adminUser = $this->getAdminUser();
            
            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found. Please ensure a user with the Super Admin role exists.'));
            }

            // Enable notifications for admin user (required to receive FCM notifications)
            // This ensures admin user can receive chat notifications
            if ($adminUser->notification != 1) {
                $adminUser->update(['notification' => 1]);
            }
            
            // Register or update FCM token for admin user
            UserFcmToken::updateOrCreate(
                ['fcm_token' => $request->fcm_token],
                [
                    'user_id' => $adminUser->id,
                    'platform_type' => $request->platform_type ?? 'web',
                ]
            );

            return ResponseService::successResponse(__('FCM token registered successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> registerFcmToken');
            return ResponseService::errorResponse(__('Failed to register FCM token'));
        }
    }

    /**
     * Block a user from chat
     */
    public function blockUser(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');

            $validator = Validator::make($request->all(), [
                'item_offer_id' => 'required|integer|exists:item_offers,id',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $adminUser = $this->getAdminUser();

            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found.'));
            }

            // Get the other user from item offer (buyer)
            $itemOffer = ItemOffer::findOrFail($request->item_offer_id);
            $blockedUserId = $itemOffer->buyer_id;

            // Check if already blocked
            $existingBlock = BlockUser::where([
                'user_id' => $adminUser->id,
                'blocked_user_id' => $blockedUserId,
            ])->first();

            if ($existingBlock) {
                return ResponseService::errorResponse(__('User is already blocked'));
            }

            // Create block record
            BlockUser::create([
                'user_id' => $adminUser->id,
                'blocked_user_id' => $blockedUserId,
            ]);

            return ResponseService::successResponse(__('User blocked successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> blockUser');
            return ResponseService::errorResponse(__('Failed to block user'));
        }
    }

    /**
     * Unblock a user from chat
     */
    public function unblockUser(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');

            $validator = Validator::make($request->all(), [
                'item_offer_id' => 'required|integer|exists:item_offers,id',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $adminUser = $this->getAdminUser();

            if (!$adminUser) {
                return ResponseService::errorResponse(__('Super Admin user not found.'));
            }

            // Get the other user from item offer (buyer)
            $itemOffer = ItemOffer::findOrFail($request->item_offer_id);
            $blockedUserId = $itemOffer->buyer_id;

            // Remove block record
            BlockUser::where([
                'user_id' => $adminUser->id,
                'blocked_user_id' => $blockedUserId,
            ])->delete();

            return ResponseService::successResponse(__('User unblocked successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> unblockUser');
            return ResponseService::errorResponse(__('Failed to unblock user'));
        }
    }

    public function linkPreview(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('admin-chat-manage');

            $validator = Validator::make($request->all(), [
                'url' => 'required|string|max:2048',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $url = trim($request->url);

            $cacheKey = 'admin_chat_link_preview_' . md5($url);

            $preview = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDay(), function () use ($url) {
                return $this->fetchLinkPreview($url);
            });

            if (!$preview) {
                return ResponseService::errorResponse(__('Unable to fetch link preview'));
            }

            return ResponseService::successResponse(__('Link preview fetched successfully'), $preview);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Admin Chat Controller -> linkPreview');
            return ResponseService::errorResponse(__('Failed to fetch link preview'));
        }
    }

    /**
     * Fetches OpenGraph/meta preview data for a URL, guarding against SSRF
     * (only http/https, resolved IP must be a public address) and against
     * abusive responses (size cap, short timeout, no following redirects
     * into disallowed hosts).
     */
    private function fetchLinkPreview(string $url): ?array
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        if (!$this->isPublicHost($parts['host'])) {
            return null;
        }

        try {
            $client = new \GuzzleHttp\Client([
                'timeout'         => 5,
                'connect_timeout' => 3,
                'allow_redirects' => ['max' => 3, 'strict' => true, 'referer' => false],
                'headers'         => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; LinkPreviewBot/1.0)',
                ],
            ]);

            $response = $client->get($url, ['stream' => true]);

            $contentType = $response->getHeaderLine('Content-Type');
            if ($contentType !== '' && stripos($contentType, 'text/html') === false) {
                return null;
            }

            // Read only the first ~200KB — enough for <head> meta tags, caps memory/time.
            $body = $response->getBody();
            $html = '';
            while (!$body->eof() && strlen($html) < 200000) {
                $html .= $body->read(8192);
            }

            return $this->parseOpenGraph($html, $url);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function isPublicHost(string $host): bool
    {
        // Reject literal IPs that aren't public.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        if (strtolower($host) === 'localhost') {
            return false;
        }

        $ips = @dns_get_record($host, DNS_A + DNS_AAAA);
        if ($ips === false || empty($ips)) {
            // Fall back to gethostbyname for A records if dns_get_record is unavailable/blocked.
            $resolved = @gethostbyname($host);
            if ($resolved === $host) {
                return false;
            }
            $ips = [['ip' => $resolved]];
        }

        foreach ($ips as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (!$ip || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    private function parseOpenGraph(string $html, string $url): array
    {
        $title = null;
        $description = null;
        $image = null;
        $siteName = parse_url($url, PHP_URL_HOST) ?: '';

        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $title = html_entity_decode($m[1], ENT_QUOTES);
        } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $title = html_entity_decode(trim($m[1]), ENT_QUOTES);
        }

        if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $description = html_entity_decode($m[1], ENT_QUOTES);
        } elseif (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $description = html_entity_decode($m[1], ENT_QUOTES);
        }

        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $image = $m[1];
        }

        if ($image && !preg_match('/^https?:\/\//i', $image)) {
            $base = parse_url($url);
            $scheme = $base['scheme'] ?? 'https';
            $host = $base['host'] ?? '';
            $image = $scheme . '://' . $host . (str_starts_with($image, '/') ? '' : '/') . $image;
        }

        // Only allow public http(s) image URLs through to the frontend.
        if ($image) {
            $imgParts = parse_url($image);
            if (!$imgParts || empty($imgParts['host']) || !$this->isPublicHost($imgParts['host'])) {
                $image = null;
            }
        }

        return [
            'url'         => $url,
            'title'       => $title ? mb_substr($title, 0, 200) : null,
            'description' => $description ? mb_substr($description, 0, 300) : null,
            'image'       => $image,
            'site_name'   => $siteName,
        ];
    }
}
