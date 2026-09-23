<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EditedImage;
use App\Models\Item;
use App\Services\ContentFormatterService;
use App\Services\ImageEditorService;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ImageEditorApiController extends Controller
{
    /**
     * Edit and transform an uploaded or existing image.
     * Supports crop, rotate, flip, filters, text overlays with custom styling, and badges/stickers.
     *
     * @param Request $request
     */
    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:12288',
            'image_url' => 'nullable|string|url',
            'base64_image' => 'nullable|string',
            'operations' => 'nullable',
            'item_id' => 'nullable|integer|exists:items,id',
            'folder' => 'nullable|string|max:64',
            'add_watermark' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            // Determine the image source
            $source = null;
            if ($request->hasFile('image')) {
                $source = $request->file('image');
            } elseif (!empty($request->base64_image)) {
                $source = $request->base64_image;
            } elseif (!empty($request->image_url)) {
                $source = $request->image_url;
            } else {
                ResponseService::errorResponse('Please provide an image file, base64 data, or valid image URL.');
            }

            // Decode operations if sent as JSON string
            $operations = $request->operations;
            if (is_string($operations)) {
                $decoded = json_decode($operations, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $operations = $decoded;
                } else {
                    $operations = [];
                }
            } elseif (!is_array($operations)) {
                $operations = [];
            }

            $folder = $request->input('folder', 'item_images');
            $userId = Auth::check() ? Auth::id() : null;
            $itemId = $request->input('item_id');
            $addWatermark = $request->boolean('add_watermark', false);

            $result = ImageEditorService::processAndSave(
                $source,
                $operations,
                $folder,
                $userId,
                $itemId,
                $addWatermark
            );

            if (!$result['success']) {
                ResponseService::errorResponse($result['message'] ?? 'Failed to edit image.');
            }

            ResponseService::successResponse('Image processed successfully.', [
                'id' => $result['data'] ? $result['data']->id : null,
                'path' => $result['path'],
                'url' => $result['url'],
                'width' => $result['width'],
                'height' => $result['height'],
                'transformations' => $operations,
            ]);
        } catch (Exception $e) {
            Log::error('ImageEditorApiController::edit error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            ResponseService::errorResponse('An unexpected error occurred while editing the image.', null, null, $e);
        }
    }

    /**
     * Preview image edits on-the-fly without saving permanently to database.
     * Returns base64 data URL or processed response.
     *
     * @param Request $request
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:10240',
            'image_url' => 'nullable|string',
            'base64_image' => 'nullable|string',
            'operations' => 'nullable',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $source = null;
            if ($request->hasFile('image')) {
                $source = $request->file('image');
            } elseif (!empty($request->base64_image)) {
                $source = $request->base64_image;
            } elseif (!empty($request->image_url)) {
                $source = $request->image_url;
            } else {
                ResponseService::errorResponse('Please provide an image for preview.');
            }

            $operations = $request->operations;
            if (is_string($operations)) {
                $decoded = json_decode($operations, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $operations = $decoded;
                } else {
                    $operations = [];
                }
            } elseif (!is_array($operations)) {
                $operations = [];
            }

            // Process with temp folder and no persistent DB record
            $result = ImageEditorService::processAndSave(
                $source,
                $operations,
                'temp_edits',
                null,
                null,
                false
            );

            if (!$result['success']) {
                ResponseService::errorResponse($result['message'] ?? 'Failed to preview image.');
            }

            ResponseService::successResponse('Image preview generated successfully.', [
                'preview_url' => $result['url'],
                'width' => $result['width'],
                'height' => $result['height'],
            ]);
        } catch (Exception $e) {
            Log::error('ImageEditorApiController::preview error: ' . $e->getMessage());
            ResponseService::errorResponse('Failed to generate preview.', null, null, $e);
        }
    }

    /**
     * Format rich text content: converts raw URLs and 10-digit Indian phone numbers
     * into clickable links, sanitizes HTML, and extracts contact numbers & URLs.
     *
     * @param Request $request
     */
    public function formatContent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $rawContent = $request->input('content');
            $formattedHtml = ContentFormatterService::formatItemDescription($rawContent);
            $contacts = ContentFormatterService::extractIndianMobileNumbers($rawContent);
            $urls = ContentFormatterService::extractUrls($rawContent);

            ResponseService::successResponse('Content formatted successfully.', [
                'original' => $rawContent,
                'formatted' => $formattedHtml,
                'extracted_contacts' => $contacts,
                'extracted_urls' => $urls,
            ]);
        } catch (Exception $e) {
            Log::error('ImageEditorApiController::formatContent error: ' . $e->getMessage());
            ResponseService::errorResponse('Failed to format content.', null, null, $e);
        }
    }

    /**
     * Fetch user's edited images history with pagination.
     *
     * @param Request $request
     */
    public function history(Request $request)
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                ResponseService::errorResponse('Authentication required to view image history.');
            }

            $query = EditedImage::where('user_id', $userId)->latest();

            if ($request->has('item_id')) {
                $query->where('item_id', $request->item_id);
            }

            $perPage = $request->integer('per_page', 15);
            $images = $query->paginate($perPage);

            ResponseService::successResponse('Edited images history retrieved.', $images);
        } catch (Exception $e) {
            Log::error('ImageEditorApiController::history error: ' . $e->getMessage());
            ResponseService::errorResponse('Failed to retrieve image history.', null, null, $e);
        }
    }

    /**
     * Soft delete an edited image record.
     *
     * @param int $id
     */
    public function delete($id)
    {
        try {
            $userId = Auth::id();
            $editedImage = EditedImage::where('id', $id)->first();

            if (!$editedImage) {
                ResponseService::errorResponse('Edited image record not found.');
            }

            if ($userId && $editedImage->user_id !== $userId && !Auth::user()->hasRole('admin')) {
                ResponseService::errorResponse('Unauthorized to delete this edited image.');
            }

            DB::transaction(function () use ($editedImage) {
                $editedImage->delete();
            });

            ResponseService::successResponse('Edited image removed successfully.');
        } catch (Exception $e) {
            Log::error('ImageEditorApiController::delete error: ' . $e->getMessage());
            ResponseService::errorResponse('Failed to delete edited image.', null, null, $e);
        }
    }

    /**
     * Upload an image embedded within rich text editor (Quill).
     *
     * @param Request $request
     */
    public function uploadEditorImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $path = \App\Services\FileService::compressAndUpload($request->file('image'), 'item_editor_images');
            if (!$path) {
                ResponseService::errorResponse('Failed to upload editor image.');
            }

            $disk = config('filesystems.default', 'public');
            $url = url(\Illuminate\Support\Facades\Storage::disk($disk)->url($path));

            ResponseService::successResponse('Editor image uploaded successfully.', [
                'path' => $path,
                'url' => $url,
            ]);
        } catch (Exception $e) {
            Log::error('ImageEditorApiController::uploadEditorImage error: ' . $e->getMessage());
            ResponseService::errorResponse('Failed to upload image.', null, null, $e);
        }
    }
}
