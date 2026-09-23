<?php

namespace App\Http\Controllers;

use App\Models\GeminiUsage;
use App\Models\Language;
use App\Services\CachingService;
use App\Services\GeminiService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GeminiAIController extends Controller
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    private function isGeminiEnabled(): bool
    {
        return CachingService::getSystemSettings('gemini_ai_enabled') === '1';
    }

    private function isAutoTranslateEnabled(): bool
    {
        return $this->isGeminiEnabled() && CachingService::getSystemSettings('gemini_auto_translate_enabled') === '1';
    }

    /**
     * Generate description for item
     */
    public function generateDescription(Request $request)
    {
        if (!$this->isGeminiEnabled()) {
            return ResponseService::validationError('AI content generation is currently disabled.');
        }

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'location' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'country' => 'nullable|string',
                'price' => 'nullable|string',
                'category_name' => 'nullable|string',
                'language_id' => 'nullable|integer',
                'currency_iso_code' => 'nullable',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $userId = $user?->id;

            $data = $request->only(['title', 'location', 'city', 'state', 'country', 'price', 'category_name', 'currency_iso_code']);

            if ($request->filled('language_id')) {
                $language = Language::where('id', $request->language_id)->first();
                if ($language) {
                    $data['language_name'] = $language->name;
                    $data['language_code'] = $language->code;
                }
            }

            $isCached = $this->geminiService->hasCachedDescription($data);

            if (!$isCached) {
                $globalLimit = (int) CachingService::getSystemSettings('gemini_description_limit_global');
                if ($globalLimit > 0 && GeminiUsage::hasExceededGlobalLimit('description', $globalLimit)) {
                    return ResponseService::validationError('Daily limit reached.');
                }

                $userLimit = (int) CachingService::getSystemSettings('gemini_description_limit');
                if ($userId && $userLimit > 0 && GeminiUsage::hasExceededLimit($userId, 'admin', 'description', $userLimit)) {
                    return ResponseService::validationError('Daily limit reached.');
                }
            }

            $result = $this->geminiService->generateDescription($data);

            if (!$result['success']) {
                return ResponseService::validationError($result['error'] ?? 'Failed to generate description');
            }

            if ($userId && !($result['cached'] ?? false)) {
                GeminiUsage::create([
                    'user_id' => $userId,
                    'user_type' => 'admin',
                    'type' => 'description',
                    'entity_type' => 'item',
                    'prompt_hash' => md5($this->geminiService->buildDescriptionPrompt($data)),
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'ip_address' => $request->ip(),
                ]);
            }

            ResponseService::successResponse('Description generated successfully', [
                'description' => $result['data'],
                'cached' => $result['cached'] ?? false,
            ]);

        } catch (\Exception $e) {
            Log::error('Gemini Description Error: ' . $e->getMessage());
            return ResponseService::validationError('Content generation currently not available, please try again later.');
        }
    }

    /**
     * Auto-translate item fields (name, description, custom text/number fields) into all other languages
     */
    public function autoTranslate(Request $request)
    {
        if (!$this->isAutoTranslateEnabled()) {
            return ResponseService::validationError('Auto translate is currently disabled.');
        }

        try {
            $validator = Validator::make($request->all(), [
                'name'                  => 'required|string|max:255',
                'description'           => 'required|string',
                'source_language_id'    => 'required|integer|exists:languages,id',
                'target_language_ids'   => 'nullable|array',
                'target_language_ids.*' => 'integer|exists:languages,id',
                'custom_fields'         => 'nullable|array',
                'custom_fields.*'       => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            set_time_limit(120);

            $sourceLanguage = Language::findOrFail($request->source_language_id);
            $targetQuery = Language::where('id', '!=', $request->source_language_id)
                ->select('id', 'name', 'name_in_english', 'code');

            if ($request->filled('target_language_ids')) {
                $targetQuery->whereIn('id', $request->target_language_ids);
            }

            $targetLanguages = $targetQuery->get();

            if ($targetLanguages->isEmpty()) {
                return ResponseService::validationError('No other languages configured for translation.');
            }

            $data = [
                'name'             => $request->name,
                'description'      => $request->description,
                'source_language'  => $sourceLanguage->name_in_english ?: $sourceLanguage->name,
                'target_languages' => $targetLanguages->map(fn($l) => [
                    'id'   => $l->id,
                    'name' => $l->name_in_english ?: $l->name,
                    'code' => $l->code,
                ])->values()->toArray(),
            ];

            if ($request->filled('custom_fields') && is_array($request->custom_fields)) {
                $data['custom_fields'] = array_filter($request->custom_fields, fn($v) => $v !== null && $v !== '');
            }

            $result = $this->geminiService->translateContent($data);

            if (!$result['success']) {
                return ResponseService::validationError($result['error'] ?? 'Failed to translate content');
            }

            $user = Auth::user();
            if ($user && !($result['cached'] ?? false)) {
                GeminiUsage::create([
                    'user_id'     => $user->id,
                    'user_type'   => 'admin',
                    'type'        => 'translation',
                    'entity_type' => 'item',
                    'prompt_hash' => md5($this->geminiService->buildTranslationPrompt($data)),
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'ip_address'  => $request->ip(),
                ]);
            }

            return ResponseService::successResponse('Content translated successfully', [
                'translations' => $result['data'],
                'cached'       => $result['cached'] ?? false,
            ]);

        } catch (\Exception $e) {
            Log::error('Gemini Auto-Translate Error: ' . $e->getMessage());
            return ResponseService::validationError('Translation currently not available, please try again later.');
        }
    }

    /**
     * Generate meta details for item
     */
    public function generateMetaDetails(Request $request)
    {
        if (!$this->isGeminiEnabled()) {
            return ResponseService::validationError('AI content generation is currently disabled.');
        }

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'location' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'country' => 'nullable|string',
                'price' => 'nullable|string',
                'language_id' => 'nullable|integer',
                'currency_iso_code' => 'nullable',
                'category_name' => 'nullable'
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $userId = $user?->id;

            $data = array_filter($request->only(['title', 'location', 'city', 'state', 'country', 'price', 'currency_iso_code', 'language_id', 'category_name']));

            if ($request->filled('language_id')) {
                $language = Language::where('id', $request->language_id)->first();
                if ($language) {
                    $data['language_name'] = $language->name;
                    $data['language_code'] = $language->code;
                }
            }

            $isCached = $this->geminiService->hasCachedMetaDetails($data);            
            if (!$isCached) {
                $globalLimit = (int) CachingService::getSystemSettings('gemini_meta_limit_global');
                if ($globalLimit > 0 && GeminiUsage::hasExceededGlobalLimit('meta', $globalLimit)) {
                    return ResponseService::validationError('Daily limit reached.');
                }

                $userLimit = (int) CachingService::getSystemSettings('gemini_meta_limit');
                if ($userId && $userLimit > 0 && GeminiUsage::hasExceededLimit($userId, 'admin', 'meta', $userLimit)) {
                    return ResponseService::validationError('Daily limit reached.');
                }
            }

            $result = $this->geminiService->generateMetaDetails($data);

            if (!$result['success']) {
                return ResponseService::validationError($result['error'] ?? 'Failed to generate meta details');
            }

            if ($userId && !($result['cached'] ?? false)) {
                GeminiUsage::create([
                    'user_id' => $userId,
                    'user_type' => 'admin',
                    'type' => 'meta',
                    'entity_type' => 'item',
                    'prompt_hash' => md5($this->geminiService->buildMetaPrompt($data)),
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'ip_address' => $request->ip(),
                ]);
            }

            ResponseService::successResponse('Meta details generated successfully', [
                'meta_title' => $result['data']['meta_title'] ?? '',
                'meta_description' => $result['data']['meta_description'] ?? '',
                'meta_keywords' => $result['data']['meta_keywords'] ?? '',
                'cached' => $result['cached'] ?? false,
            ]);

        } catch (\Exception $e) {
            Log::error('Gemini Meta Error: ' . $e->getMessage());
            return ResponseService::validationError('Content generation currently not available, please try again later.');
        }
    }
}
