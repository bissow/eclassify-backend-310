<?php

namespace App\Http\Controllers\Api;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Language;
use App\Models\Setting;
use App\Models\State;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Location */
class LocationApiController extends BaseApiController
{
    /** Get Countries */
    public function getCountries(Request $request)
    {
        try {
            $searchQuery = $request->search ?? '';
            $countries = Country::withCount('states')
                ->where('status', 1)
                ->where(function ($query) use ($searchQuery) {
                    $query->where('name', 'LIKE', "%{$searchQuery}%")
                        ->orWhereHas('translations', function ($q) use ($searchQuery) {
                            $q->where('key', 'name')->where('value', 'LIKE', "%{$searchQuery}%");
                        });
                })
                ->with(['translations.language:id,code'])
                ->orderBy('name', 'ASC')
                ->paginate();

            $countries->getCollection()->transform(function ($country) {
                if ($country->translations instanceof \Illuminate\Support\Collection) {
                    $country->translations = $country->translations
                        ->where('key', 'name')
                        ->map(function ($translation) use ($country) {
                            return [
                                'id' => $translation->id,
                                'country_id' => $country->id,
                                'language_id' => $translation->language_id,
                                'name' => $translation->value,
                                'language_code' => optional($translation->language)->code,
                            ];
                        })->values();
                } else {
                    $country->translations = [];
                }

                return $country;
            });

            ResponseService::successResponse(__('Countries Fetched Successfully'), $countries);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCountries');
            ResponseService::errorResponse(__('Failed to fetch countries'));
        }
    }

    /** Get States */
    public function getStates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'nullable|integer',
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $searchQuery = $request->search ?? '';
            $statesQuery = State::withCount('cities')
                ->where('status', 1)
                ->where('name', 'LIKE', "%{$searchQuery}%")
                ->orderBy('name', 'ASC');

            if (isset($request->country_id)) {
                $statesQuery->where('country_id', $request->country_id);
            }

            $states = $statesQuery->paginate($request->input('per_page', 15));

            ResponseService::successResponse(__('States Fetched Successfully'), $states);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller->getStates');
            ResponseService::errorResponse(__('Failed to fetch states'));
        }
    }

    /** Get Cities */
    public function getCities(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'state_id' => 'nullable|integer',
                'search' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $searchQuery = $request->search ?? '';

            $citiesQuery = City::with('translations')
                ->withCount('areas')
                ->where('cities.status', 1)
                ->orderBy('cities.name', 'ASC');

            if ($searchQuery !== '') {
                $citiesQuery->where(function ($q) use ($searchQuery) {
                    $q->where('cities.name', 'LIKE', "%{$searchQuery}%")
                        ->orWhereHas('translations', function ($t) use ($searchQuery) {
                            $t->where('key', 'name')->where('value', 'LIKE', "%{$searchQuery}%");
                        });
                });
            }

            if ($request->filled('state_id')) {
                $citiesQuery->where('cities.state_id', $request->state_id);
            }

            $cities = $citiesQuery->paginate($request->input('per_page', 15));

            return ResponseService::successResponse(__('Cities Fetched Successfully'), $cities);
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller->getCities');

            return ResponseService::errorResponse(__('Failed to fetch cities'));
        }
    }

    /** Get Areas */
    public function getAreas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'nullable|integer',
            'search' => 'nullable',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $searchQuery = $request->search ?? '';
            $data = Area::with('translations')->search($searchQuery)->where('status', 1)->orderBy('name', 'ASC');
            if (isset($request->city_id)) {
                $data->where('city_id', $request->city_id);
            }

            $data = $data->paginate();
            ResponseService::successResponse(__('Area fetched Successfully'), $data);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getAreas');
            ResponseService::errorResponse();
        }
    }

    /** Get Location From Coordinates */
    public function getLocationFromCoordinates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'lang' => 'nullable|string',
            'search' => 'nullable|string',
            'place_id' => 'nullable|string',
            'session_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $lat = $request->lat;
            $lng = $request->lng;
            $lang = $request->lang ?? 'en';
            $search = $request->search;
            $placeId = $request->place_id;
            $mapProvider = Setting::where('name', 'map_provider')->value('value') ?? 'free_api';

            $contentLangCode = $request->header('Content-Language') ?? app()->getLocale();
            $currentLanguage = Language::where('code', $contentLangCode)->first();
            $currentLangId = $currentLanguage->id ?? 1;

            if ($search) {
                if ($mapProvider === 'google_places') {
                    $apiKey = Setting::where('name', 'place_api_key')->value('value');
                    if (! $apiKey) {
                        return ResponseService::errorResponse(__('Google Maps API key not set'));
                    }

                    $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
                        'key' => $apiKey,
                        'input' => $search,
                        'language' => $lang,
                        'sessiontoken' => $request->session_id,
                    ]);

                    return $response->successful()
                        ? ResponseService::successResponse(__('Location fetched from Google API'), $response->json())
                        : ResponseService::errorResponse(__('Failed to fetch from Google Maps API'));
                } else {
                    $areas = Area::with([
                        'translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'city.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'city.state.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'city.state.country.translations' => fn($q) => $q->where('language_id', $currentLangId),
                    ])
                        ->where('status', 1)
                        ->where('name', 'like', "%{$search}%")
                        ->limit(10)
                        ->get();

                    if ($areas->isNotEmpty()) {
                        return ResponseService::successResponse(__('Matching areas found'), $areas->map(function ($area) {
                            return [
                                'area_id' => $area->id,
                                'area' => $area->name,
                                'area_translation' => optional($area->translations->where('key', 'name')->first())->value ?? $area->name,
                                'city_id' => optional($area->city)->id,
                                'city' => optional($area->city)->name,
                                'city_translation' => optional($area->city->translations->where('key', 'name')->first())->value ?? optional($area->city)->name,
                                'state' => optional($area->city->state)->name,
                                'state_translation' => optional($area->city->state->translations->where('key', 'name')->first())->value ?? optional($area->city->state)->name,
                                'country' => optional($area->city->state->country)->name,
                                'country_translation' => optional($area->city->state->country->translations->where('key', 'name')->first())->value ?? optional($area->city->state->country)->name,
                                'latitude' => $area->latitude,
                                'longitude' => $area->longitude,
                            ];
                        }));
                    }

                    $cities = City::with([
                        'translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'state.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'state.country.translations' => fn($q) => $q->where('language_id', $currentLangId),
                    ])
                        ->where('status', 1)
                        ->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhereHas('state', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('state.country', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
                        })
                        ->limit(10)
                        ->get();

                    if ($cities->isEmpty()) {
                        return ResponseService::errorResponse(__('No matching location found'));
                    }

                    return ResponseService::successResponse(__('Matching cities found'), $cities->map(function ($city) {
                        return [
                            'city_id' => $city->id,
                            'city' => $city->name,
                            'city_translation' => optional($city->translations->where('key', 'name')->first())->value ?? $city->name,
                            'state' => optional($city->state)->name,
                            'state_translation' => optional($city->state->translations->where('key', 'name')->first())->value ?? optional($city->state)->name,
                            'country' => optional($city->state->country)->name,
                            'country_translation' => optional($city->state->country->translations->where('key', 'name')->first())->value ?? optional($city->state->country)->name,
                            'latitude' => $city->latitude,
                            'longitude' => $city->longitude,
                        ];
                    }));
                }
            }

            if (! empty($lat) && ! empty($lng)) {
                if ($mapProvider === 'google_places') {
                    $apiKey = Setting::where('name', 'place_api_key')->value('value');
                    if (! $apiKey) {
                        return ResponseService::errorResponse(__('Google Maps API key not set'));
                    }

                    $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'latlng' => "{$lat},{$lng}",
                        'key' => $apiKey,
                        'language' => $lang,
                        'sessiontoken' => $request->session_id,
                    ]);

                    return $response->successful()
                        ? ResponseService::successResponse(__('Location fetched from Google API'), $response->json())
                        : ResponseService::errorResponse(__('Failed to fetch from Google Maps API'));
                } else {

                    // STEP 1: Area-first lookup — find the closest Area across ALL cities.
                    // This provides more accurate city assignment near city boundaries,
                    // since areas are smaller geographic units within cities.
                    $closestArea = Area::with([
                        'translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'city.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'city.state.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'city.state.country.translations' => fn($q) => $q->where('language_id', $currentLangId),
                    ])
                        ->where('status', 1)
                        ->whereNotNull('latitude')
                        ->whereNotNull('longitude')
                        ->selectRaw('
                        id, name, latitude, longitude, city_id,
                        (6371 * acos(cos(radians(?))
                            * cos(radians(latitude))
                            * cos(radians(longitude) - radians(?))
                            + sin(radians(?))
                            * sin(radians(latitude)))) AS distance
                    ', [$lat, $lng, $lat])
                        ->orderBy('distance', 'asc')
                        ->first();

                    // STEP 2: If an area is found, use its parent city
                    if ($closestArea && $closestArea->city) {
                        $closestCity = $closestArea->city;

                        return ResponseService::successResponse(__('Location fetched from local database'), [
                            'city_id' => $closestCity->id,
                            'city' => $closestCity->name,
                            'city_translation' => optional($closestCity->translations->where('key', 'name')->first())->value ?? $closestCity->name,
                            'state' => optional($closestCity->state)->name,
                            'state_translation' => optional($closestCity->state?->translations?->where('key', 'name')->first())->value ?? optional($closestCity->state)->name,
                            'country' => optional($closestCity->state?->country)->name,
                            'country_translation' => optional($closestCity->state?->country?->translations?->where('key', 'name')->first())->value ?? optional($closestCity->state?->country)->name,
                            'area_id' => $closestArea->id,
                            'area' => $closestArea->name,
                            'area_translation' => optional($closestArea->translations->where('key', 'name')->first())->value ?? $closestArea->name,
                            'latitude' => $closestArea->latitude,
                            'longitude' => $closestArea->longitude,
                        ]);
                    }

                    // STEP 3: Fallback — no areas with coordinates exist in DB,
                    // fall back to closest city center (original behavior)
                    $closestCity = City::with([
                        'translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'state.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'state.country.translations' => fn($q) => $q->where('language_id', $currentLangId),
                    ])
                        ->where('status', 1)
                        ->whereNotNull('latitude')
                        ->whereNotNull('longitude')
                        ->selectRaw('
                        id, name, latitude, longitude, state_id,
                        (6371 * acos(cos(radians(?))
                            * cos(radians(latitude))
                            * cos(radians(longitude) - radians(?))
                            + sin(radians(?))
                            * sin(radians(latitude)))) AS distance
                    ', [$lat, $lng, $lat])
                        ->orderBy('distance', 'asc')
                        ->first();

                    if (! $closestCity) {
                        return ResponseService::errorResponse(__('No nearby city found'));
                    }

                    return ResponseService::successResponse(__('Location fetched from local database'), [
                        'city_id' => $closestCity->id,
                        'city' => $closestCity->name,
                        'city_translation' => optional($closestCity->translations->where('key', 'name')->first())->value ?? $closestCity->name,
                        'state' => optional($closestCity->state)->name,
                        'state_translation' => optional($closestCity->state->translations->where('key', 'name')->first())->value ?? optional($closestCity->state)->name,
                        'country' => optional($closestCity->state->country)->name,
                        'country_translation' => optional($closestCity->state->country->translations->where('key', 'name')->first())->value ?? optional($closestCity->state->country)->name,
                        'area_id' => null,
                        'area' => null,
                        'area_translation' => null,
                        'latitude' => $closestCity->latitude,
                        'longitude' => $closestCity->longitude,
                    ]);
                }
            }

            if ($placeId) {
                if ($mapProvider === 'google_places') {
                    $apiKey = Setting::where('name', 'place_api_key')->value('value');
                    if (! $apiKey) {
                        return ResponseService::errorResponse(__('Google Maps API key not set'));
                    }
                    $sessionParam = $request->session_id ? "&sessiontoken={$request->session_id}" : '';
                    $url = "https://maps.googleapis.com/maps/api/geocode/json?place_id={$placeId}&key={$apiKey}&language={$lang}{$sessionParam}";

                    $response = Http::get($url);

                    return $response->successful()
                        ? ResponseService::successResponse(__('Location fetched from place_id'), $response->json())
                        : ResponseService::errorResponse(__('Failed to fetch from Google Maps API using place_id'));
                } else {
                    return ResponseService::errorResponse(__('place_id is only supported with Google Maps provider'));
                }
            }
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getLocationFromCoordinates');

            return ResponseService::errorResponse(__('Failed to fetch location'));
        }
    }
}
