<?php

namespace App\Http\Controllers\Api;

use App\Models\Language;
use App\Models\VerificationField;
use App\Models\VerificationFieldValue;
use App\Models\VerificationRequest;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Verification */
class VerificationApiController extends BaseApiController
{
    /** Get Verification Fields */
    public function getVerificationFields()
    {
        try {
            $fields = VerificationField::all();
            ResponseService::successResponse(__('Verification Field Fetched Successfully'), $fields);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> addVerificationFieldValues');
            ResponseService::errorResponse();
        }
    }

    /** Send Verification Request */
    public function sendVerificationRequest(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'verification_field' => 'required|array',
                'verification_field.*' => 'required',
                'verification_field_files' => 'nullable|array',
                'verification_field_files.*' => 'nullable|mimes:jpeg,png,jpg,pdf,doc|max:7168',

            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            DB::beginTransaction();

            $user = Auth::user();
            $verificationRequest = VerificationRequest::updateOrCreate([
                'user_id' => $user->id,
            ], ['status' => 'pending']);

            $user = auth()->user();
            if ($request->verification_field) {
                $itemCustomFieldValues = [];
                foreach ($request->verification_field as $id => $value) {
                    $itemCustomFieldValues[] = [
                        'user_id' => $user->id,
                        'verification_field_id' => $id,
                        'verification_request_id' => $verificationRequest->id,
                        'value' => is_array($value) ? implode(',', $value) : $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'language_id' => CachingService::getDefaultLanguage()?->id ?? null,
                    ];
                }
                if (count($itemCustomFieldValues) > 0) {
                    VerificationFieldValue::upsert($itemCustomFieldValues, ['user_id', 'verification_fields_id', 'language_id'], ['value', 'updated_at']);
                }
            }

            if ($request->verification_field_files) {
                $itemCustomFieldValues = [];
                foreach ($request->verification_field_files as $fieldId => $file) {
                    $itemCustomFieldValues[] = [
                        'user_id' => $user->id,
                        'verification_field_id' => $fieldId,
                        'verification_request_id' => $verificationRequest->id,
                        'value' => ! empty($file) ? FileService::upload($file, 'verification_field_files') : '',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'language_id' => CachingService::getDefaultLanguage()?->id ?? null,
                    ];
                }
                if (count($itemCustomFieldValues) > 0) {
                    VerificationFieldValue::upsert($itemCustomFieldValues, ['user_id', 'verification_field_id', 'language_id'], ['value', 'updated_at']);
                }
            }
            DB::commit();

            ResponseService::successResponse(__('Verification request submitted successfully.'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> SendVerificationRequest');
            ResponseService::errorResponse();
        }
    }

    /** Get Verification Request */
    public function getVerificationRequest(Request $request)
    {
        try {
            $verificationRequest = VerificationRequest::with([
                'verification_field_values.verification_field.translations',
            ])->owner()->first();

            if (empty($verificationRequest)) {
                ResponseService::errorResponse('No Request found');
            }

            $response = $verificationRequest->toArray();
            $response['verification_fields'] = [];

            $contentLangCode = $request->header('Content-Language') ?? app()->getLocale();
            $currentLanguage = Language::where('code', $contentLangCode)->first();
            $currentLangId = $currentLanguage->id ?? 1;

            foreach ($verificationRequest->verification_field_values as $verificationFieldValue) {
                if (
                    $verificationFieldValue->relationLoaded('verification_field') &&
                    ! empty($verificationFieldValue->verification_field)
                ) {

                    $field = $verificationFieldValue->verification_field;
                    $tempRow = $field->toArray();

                    $rawValue = $verificationFieldValue->value;

                    $normalizedValue = [];
                    if ($field->type === 'fileinput') {
                        $normalizedValue = ! empty($rawValue) ? [url(Storage::url($rawValue))] : [];
                    } elseif (is_array($rawValue)) {
                        $normalizedValue = $rawValue;
                    } elseif (is_string($rawValue)) {
                        $decoded = json_decode($rawValue, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $normalizedValue = $decoded;
                        } else {
                            $normalizedValue = [$rawValue];
                        }
                    } elseif (! empty($rawValue)) {
                        $normalizedValue = [$rawValue];
                    }

                    $tempRow['value'] = array_map('trim', explode(',', $normalizedValue[0]));

                    $tempRow['verification_field_value'] = $verificationFieldValue->toArray();
                    unset($tempRow['verification_field_value']['verification_field']);
                    $tempRow['verification_field_value']['value'] = $normalizedValue;
                    $tempRow['verification_field_value']['language_id'] = $verificationFieldValue->language_id;
                    $selected = [];
                    $type = $field->type ?? null;
                    $allPossibleValues = $field->values ?? [];

                    $translatedValues = [];
                    if (! empty($field->translations)) {
                        $valueTrans = collect($field->translations)->where('language_id', $currentLangId)->where('key', 'value')->first();
                        $translatedValues = $valueTrans['value'] ?? [];
                        if (is_string($translatedValues)) {
                            $translatedValues = json_decode($translatedValues, true) ?? [];
                        }
                    }
                    if (empty($translatedValues)) {
                        $translatedValues = $allPossibleValues;
                    }

                    if (in_array($type, ['checkbox', 'radio', 'dropdown'])) {
                        foreach ($normalizedValue as $val) {
                            $index = array_search($val, $allPossibleValues);
                            $translatedVal = ($index !== false && isset($translatedValues[$index]))
                                ? $translatedValues[$index]
                                : $val;
                            $selected[] = $translatedVal;
                        }
                    } elseif (in_array($type, ['textbox', 'number'])) {
                        $selected = $normalizedValue;
                    }
                    $tempRow['language_id'] = $verificationFieldValue->language_id;
                    $tempRow['translated_selected_values'] = $selected;
                    $response['verification_fields'][] = $tempRow;
                }
            }

            ResponseService::successResponse(__('Verification request fetched successfully.'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> SendVerificationRequest');
            ResponseService::errorResponse();
        }
    }
}
