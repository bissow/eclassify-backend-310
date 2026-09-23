<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\SellerRating;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Review */
class ReviewApiController extends BaseApiController
{
    /** Add Item Review */
    public function addItemReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'review' => 'nullable|string',
            'ratings' => 'required|numeric|between:0,5',
            'item_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $loggedInUserId = Auth::user()->id;
            $item = Item::with('user')->notOwner()->findOrFail($request->item_id);
            if ($item->sold_to !== $loggedInUserId) {
                ResponseService::errorResponse(__('You can only review items that you have purchased.'));
            }
            if ($item->status !== 'sold out') {
                ResponseService::errorResponse(__("The item must be marked as 'sold out' before you can review it."));
            }

            $existingReview = SellerRating::where(['item_id' => $request->item_id, 'buyer_id' => $loggedInUserId])->first();
            if ($existingReview) {
                ResponseService::errorResponse(__('You have already reviewed this item.'));
            }
            $review = SellerRating::create([
                'item_id' => $request->item_id,
                'buyer_id' => $loggedInUserId,
                'seller_id' => $item->user_id,
                'ratings' => $request->ratings,
                'review' => $request->review ?? '',
            ]);
            DB::commit();
            if (! empty($item->user_id)) {
                NotificationService::dispatchChunkedNotifications(
                    $item->title ?? __('Item'),
                    'A new review has been added to your advertisement: ' . $item->name,
                    'item-review',
                    ['item_id' => $item->id],
                    false,
                    array($item->user_id),
                    true
                );
            }

            ResponseService::successResponse(__('Your review has been submitted successfully.'), $review);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> storeContactUs');
            ResponseService::errorResponse();
        }
    }

    /** Get My Reviews */
    public function getMyReview(Request $request)
    {
        try {
            $ratingsQuery = SellerRating::where('seller_id', Auth::user()->id)->with('seller:id,name,profile', 'buyer:id,name,profile', 'item:id,name,price,description');
            $totalOneRatings = $ratingsQuery->clone()->where('ratings', 1)->count();
            $totalTwoRatings = $ratingsQuery->clone()->where('ratings', 2)->count();
            $totalThreeRatings = $ratingsQuery->clone()->where('ratings', 3)->count();
            $totalFourRatings = $ratingsQuery->clone()->where('ratings', 4)->count();
            $totalFiveRatings = $ratingsQuery->clone()->where('ratings', 5)->count();
            $ratings = $ratingsQuery->paginate(10);
            $averageRating = $ratings->avg('ratings');
            $response = [
                'average_rating' => $averageRating,
                'ratings' => $ratings,
                'ratings_count' => [
                    1 => $totalOneRatings ?? 0,
                    2 => $totalTwoRatings ?? 0,
                    3 => $totalThreeRatings ?? 0,
                    4 => $totalFourRatings ?? 0,
                    5 => $totalFiveRatings ?? 0,
                ],
            ];

            ResponseService::successResponse(__('Seller Details Fetched Successfully'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getSeller');
            ResponseService::errorResponse();
        }
    }

    /** Add Review Report */
    public function addReviewReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_reason' => 'required|string',
            'seller_review_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $ratings = SellerRating::where('seller_id', Auth::user()->id)->findOrFail($request->seller_review_id);
            $ratings->update([
                'report_status' => 'reported',
                'report_reason' => $request->report_reason,
            ]);

            ResponseService::successResponse(__('Your report has been submitted successfully.'), $ratings);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> addReviewReport');
            ResponseService::errorResponse();
        }
    }
}
