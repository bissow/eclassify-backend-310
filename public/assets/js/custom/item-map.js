// Shared map picker JS for items/create.blade.php and items/update.blade.php
// Config is read from data-* attributes on #map (see item-map.blade partial usage).
(function() {
    'use strict';

    const BHUJ_LAT = 23.2420;
    const BHUJ_LNG = 69.6669;

    let map, marker;
    let mapInitialized = false;
    let searchTimeout;
    let googleMapsLoadPromise = null;

    let MAP_PROVIDER = 'free_api';
    let GOOGLE_MAP_KEY = '';
    let GET_LOCATION_URL = '';
    let DEFAULT_LOCALE = 'en';
    let ITEM_LAT = NaN;
    let ITEM_LNG = NaN;
    let SETTINGS_LAT = NaN;
    let SETTINGS_LNG = NaN;

    function readConfig() {
        const el = document.getElementById('map');
        if (!el) return false;

        MAP_PROVIDER = el.dataset.mapProvider || 'free_api';
        GOOGLE_MAP_KEY = el.dataset.googleMapKey || '';
        GET_LOCATION_URL = el.dataset.getLocationUrl || '';
        DEFAULT_LOCALE = el.dataset.locale || 'en';
        ITEM_LAT = parseFloat(el.dataset.itemLat);
        ITEM_LNG = parseFloat(el.dataset.itemLng);
        SETTINGS_LAT = parseFloat(el.dataset.settingsLat);
        SETTINGS_LNG = parseFloat(el.dataset.settingsLng);
        return true;
    }

    // Admin's currently selected panel language (app()->getLocale()).
    function getLocale() {
        return DEFAULT_LOCALE;
    }

    function getDefaultLat() {
        return !isNaN(ITEM_LAT) && ITEM_LAT ? ITEM_LAT : (!isNaN(SETTINGS_LAT) && SETTINGS_LAT ? SETTINGS_LAT : BHUJ_LAT);
    }

    function getDefaultLng() {
        return !isNaN(ITEM_LNG) && ITEM_LNG ? ITEM_LNG : (!isNaN(SETTINGS_LNG) && SETTINGS_LNG ? SETTINGS_LNG : BHUJ_LNG);
    }

    function getDefaultZoom() {
        return 8;
    }

    function loadGoogleMapsScript() {
        if (googleMapsLoadPromise) return googleMapsLoadPromise;
        googleMapsLoadPromise = new Promise((resolve, reject) => {
            if (window.google && window.google.maps) {
                resolve();
                return;
            }
            window.__onGoogleMapsLoaded = resolve;
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(GOOGLE_MAP_KEY) + '&loading=async&callback=__onGoogleMapsLoaded';
            script.async = true;
            script.defer = true;
            script.onerror = reject;
            document.head.appendChild(script);
        });
        return googleMapsLoadPromise;
    }

    function initMap() {
        if (!readConfig()) return;

        // Check if map is already initialized
        if (mapInitialized && map) {
            setTimeout(() => {
                if (MAP_PROVIDER !== 'google_places' && map && typeof map.invalidateSize === 'function') {
                    map.invalidateSize();
                } else if (MAP_PROVIDER === 'google_places' && window.google && map) {
                    google.maps.event.trigger(map, 'resize');
                }
            }, 100);
            return;
        }

        const defaultLat = getDefaultLat();
        const defaultLng = getDefaultLng();
        const defaultZoom = getDefaultZoom();

        if (MAP_PROVIDER === 'google_places' && GOOGLE_MAP_KEY) {
            initGoogleMap(defaultLat, defaultLng, defaultZoom);
        } else {
            initLeafletMap(defaultLat, defaultLng, defaultZoom);
        }
    }

    function initGoogleMap(defaultLat, defaultLng, defaultZoom) {
        loadGoogleMapsScript().then(async () => {
            const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');

            map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: defaultLat, lng: defaultLng },
                zoom: defaultZoom,
                mapId: 'DEMO_MAP_ID'
            });

            marker = new AdvancedMarkerElement({
                position: { lat: defaultLat, lng: defaultLng },
                map: map,
                gmpDraggable: true
            });

            updateLatLngInputs(defaultLat, defaultLng);
            fetchAddressFromCoords(defaultLat, defaultLng);

            marker.addListener('dragend', function() {
                const pos = marker.position;
                updateLatLngInputs(pos.lat, pos.lng);
                fetchAddressFromCoords(pos.lat, pos.lng);
            });

            map.addListener('click', function(e) {
                const lat = e.latLng.lat();
                const lng = e.latLng.lng();
                marker.position = { lat: lat, lng: lng };
                updateLatLngInputs(lat, lng);
                fetchAddressFromCoords(lat, lng);
            });

            mapInitialized = true;
        }).catch(() => {
            // Fall back to free map if Google Maps fails to load
            initLeafletMap(defaultLat, defaultLng, defaultZoom);
        });
    }

    function initLeafletMap(defaultLat, defaultLng, defaultZoom) {
        try {
            map = L.map('map').setView([defaultLat, defaultLng], defaultZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            marker = L.marker([defaultLat, defaultLng], {
                draggable: true
            }).addTo(map);

            updateLatLngInputs(defaultLat, defaultLng);
            fetchAddressFromCoords(defaultLat, defaultLng);

            marker.on('dragend', function() {
                const pos = marker.getLatLng();
                updateLatLngInputs(pos.lat, pos.lng);
                fetchAddressFromCoords(pos.lat, pos.lng);
            });

            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                marker.setLatLng([lat, lng]);
                updateLatLngInputs(lat, lng);
                fetchAddressFromCoords(lat, lng);
            });

            mapInitialized = true;
        } catch (error) {
            // Silently handle map initialization error
        }
    }

    function updateLatLngInputs(lat, lng) {
        const latInput = document.getElementById('latitude-input');
        const lngInput = document.getElementById('longitude-input');
        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
    }

    // Location search functionality
    function searchLocation(query) {
        
        if (!query || query.length < 3) {
            $('#search-results').hide();
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: GET_LOCATION_URL,
                type: 'GET',
                data: {
                    search: query,
                    lang: getLocale()
                },
                headers: {
                    'Content-Language': getLocale()
                },
                success: function(response) {
                    let searchData = null;

                    if (response && (response.error === false || !response.error) && response.data) {
                        searchData = response.data;
                    } else if (response && Array.isArray(response)) {
                        searchData = response;
                    } else if (response && response.data) {
                        searchData = response.data;
                    }

                    if (searchData) {
                        displaySearchResults(searchData);
                    } else {
                        $('#search-results').hide();
                    }
                },
                error: function(error) {
                    $('#search-results').hide();
                }
            });
        }, 500);
    }

    function displaySearchResults(data) {
        const resultsContainer = $('#search-results');
        resultsContainer.empty();

        let hasResults = false;

        // Handle Google Places API autocomplete response
        if (data && data.predictions && Array.isArray(data.predictions) && data.predictions.length > 0) {
            data.predictions.forEach(function(prediction) {
                const item = $('<div class="p-2 border-bottom search-result-item" style="cursor: pointer; transition: background 0.2s;"></div>');
                item.html('<i class="fas fa-map-marker-alt text-primary me-2"></i>' + (prediction.description || prediction.name || ''));
                item.on('click', function(e) {
                    e.stopPropagation();
                    if (prediction.place_id) {
                        selectLocationFromSearch(prediction.place_id);
                    } else if (prediction.latitude && prediction.longitude) {
                        selectLocationFromCoords(prediction.latitude, prediction.longitude);
                    }
                    resultsContainer.hide();
                    $('#location-search').val(prediction.description || prediction.name || '');
                });
                item.on('mouseenter', function() {
                    $(this).css('background', '#f5f5f5');
                });
                item.on('mouseleave', function() {
                    $(this).css('background', 'white');
                });
                resultsContainer.append(item);
                hasResults = true;
            });
        }
        // Handle local database results (array of cities/areas)
        else if (data && Array.isArray(data) && data.length > 0) {
            data.forEach(function(location) {
                const cityName = location.city_translation || location.city || '';
                const stateName = location.state_translation || location.state || '';
                const countryName = location.country_translation || location.country || '';
                const areaName = location.area_translation || location.area || '';

                const address = [areaName, cityName, stateName, countryName].filter(Boolean).join(', ');
                if (!address) return;

                const lat = location.latitude || location.lat;
                const lng = location.longitude || location.lng;

                const item = $('<div class="p-2 border-bottom search-result-item" style="cursor: pointer; transition: background 0.2s;"></div>');
                item.html('<i class="fas fa-map-marker-alt text-primary me-2"></i>' + address);
                item.on('click', function(e) {
                    e.stopPropagation();
                    if (lat && lng) {
                        selectLocationFromCoords(lat, lng);
                        resultsContainer.hide();
                        $('#location-search').val(address);
                    } else {
                        alert('No coordinates available for this location');
                    }
                });
                item.on('mouseenter', function() {
                    $(this).css('background', '#f5f5f5');
                });
                item.on('mouseleave', function() {
                    $(this).css('background', 'white');
                });
                resultsContainer.append(item);
                hasResults = true;
            });
        }

        if (hasResults) {
            resultsContainer.show();
        } else {
            resultsContainer.hide();
        }
    }

    function selectLocationFromSearch(placeId) {
        $.ajax({
            url: GET_LOCATION_URL,
            type: 'GET',
            data: {
                place_id: placeId,
                lang: getLocale()
            },
            headers: {
                'Content-Language': getLocale()
            },
            success: function(response) {
                if (response && (response.error === false || !response.error) && response.data) {
                    const data = response.data;
                    if (data.results && data.results[0] && data.results[0].geometry && data.results[0].geometry.location) {
                        const lat = data.results[0].geometry.location.lat;
                        const lng = data.results[0].geometry.location.lng;
                        selectLocationFromCoords(lat, lng);
                    }
                } else if (response && response.results && response.results[0] && response.results[0].geometry) {
                    const lat = response.results[0].geometry.location.lat;
                    const lng = response.results[0].geometry.location.lng;
                    selectLocationFromCoords(lat, lng);
                }
            },
            error: function(error) {
                // Silently handle error
            }
        });
    }

    function selectLocationFromCoords(lat, lng) {
        if (!map || !mapInitialized) {
            initMap();
            setTimeout(function() {
                updateMapLocation(lat, lng);
            }, 300);
        } else {
            updateMapLocation(lat, lng);
        }
    }

    function updateMapLocation(lat, lng) {
        if (map && marker) {
            if (MAP_PROVIDER === 'google_places' && window.google) {
                map.setCenter({ lat: lat, lng: lng });
                map.setZoom(13);
                marker.position = { lat: lat, lng: lng };
            } else {
                map.setView([lat, lng], 13);
                marker.setLatLng([lat, lng]);
            }
            updateLatLngInputs(lat, lng);
            fetchAddressFromCoords(lat, lng);
        }
    }

    function locateUser() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    selectLocationFromCoords(lat, lng);
                },
                function(error) {
                    alert('Unable to get your location. Please select manually on the map.');
                }
            );
        } else {
            alert('Geolocation is not supported by your browser.');
        }
    }

    function fetchAddressFromCoords(lat, lng) {
        // Always resolve in English: this populates the hidden country/state/city/address
        // fields that get saved to the DB, and DB storage must stay locale-independent.
        $.ajax({
            url: GET_LOCATION_URL,
            type: 'GET',
            data: {
                lat: lat,
                lng: lng,
                lang: 'en'
            },
            headers: {
                'Content-Language': 'en'
            },
            success: function(response) {
                let fullAddressText = '';
                let countryName = '';
                let stateName = '';
                let cityName = '';

                if (response && response.data && (response.error === false || !response.error || response.status === 'success')) {
                    const data = response.data;

                    if (data.results && Array.isArray(data.results) && data.results.length > 0) {
                        const result = data.results[0];
                        fullAddressText = result.formatted_address || '';

                        if (result.address_components && Array.isArray(result.address_components)) {
                            result.address_components.forEach(function(component) {
                                if (component.types && Array.isArray(component.types)) {
                                    if (component.types.includes('country')) {
                                        countryName = component.long_name || component.short_name || '';
                                    }
                                    if (component.types.includes('administrative_area_level_1')) {
                                        stateName = component.long_name || component.short_name || '';
                                    }
                                    if (component.types.includes('locality') || component.types.includes('administrative_area_level_2')) {
                                        if (!cityName) {
                                            cityName = component.long_name || component.short_name || '';
                                        }
                                    }
                                }
                            });
                        }
                    }
                    else if (data && (data.city || data.city_translation)) {
                        const area = data.area_translation || data.area || '';
                        const city = data.city_translation || data.city || '';
                        const state = data.state_translation || data.state || '';
                        const country = data.country_translation || data.country || '';

                        fullAddressText = [area, city, state, country].filter(Boolean).join(', ');

                        countryName = data.country_translation || data.country || '';
                        stateName = data.state_translation || data.state || '';
                        cityName = data.city_translation || data.city || '';
                    }
                    else if (Array.isArray(data) && data.length > 0) {
                        const location = data[0];
                        if (location.formatted_address) {
                            fullAddressText = location.formatted_address;
                        } else if (location.address) {
                            fullAddressText = location.address;
                        } else {
                            const area = location.area_translation || location.area || '';
                            const city = location.city_translation || location.city || '';
                            const state = location.state_translation || location.state || '';
                            const country = location.country_translation || location.country || '';

                            fullAddressText = [area, city, state, country].filter(Boolean).join(', ');
                        }
                        countryName = location.country_translation || location.country || '';
                        stateName = location.state_translation || location.state || '';
                        cityName = location.city_translation || location.city || '';
                    }
                }
                else if (response && (response.formatted_address || response.address || response.city || response.city_translation)) {
                    if (response.formatted_address) {
                        fullAddressText = response.formatted_address;
                    } else if (response.address) {
                        fullAddressText = response.address;
                    } else {
                        const area = response.area_translation || response.area || '';
                        const city = response.city_translation || response.city || '';
                        const state = response.state_translation || response.state || '';
                        const country = response.country_translation || response.country || '';

                        fullAddressText = [area, city, state, country].filter(Boolean).join(', ');
                    }
                    countryName = response.country_translation || response.country || '';
                    stateName = response.state_translation || response.state || '';
                    cityName = response.city_translation || response.city || '';
                }

                if (!fullAddressText || fullAddressText.trim() === '') {
                    fullAddressText = lat + ', ' + lng;
                }

                const addressInput = document.getElementById('address-hidden');
                if (addressInput) addressInput.value = fullAddressText;

                const countryInput = document.getElementById('country-input');
                if (countryInput) countryInput.value = countryName;

                const stateInput = document.getElementById('state-input');
                if (stateInput) stateInput.value = stateName;

                const cityInput = document.getElementById('city-input');
                if (cityInput) cityInput.value = cityName;

                if (fullAddressText) {
                    $('#selected-address-display').show();
                    $('#selected-address-text').text(fullAddressText);
                    $('#location-search').val(fullAddressText);
                }
            },
            error: function(error) {
                const fallbackAddress = lat + ', ' + lng;
                const addressInput = document.getElementById('address-hidden');
                if (addressInput) addressInput.value = fallbackAddress;
                $('#selected-address-display').show();
                $('#selected-address-text').text('Location: ' + fallbackAddress);
                $('#location-search').val(fallbackAddress);
            }
        });
    }

    // Expose functions used elsewhere in the item create/update pages (e.g. other tab switches call initMap()).
    window.initMap = initMap;
    window.searchLocation = searchLocation;
    window.selectLocationFromCoords = selectLocationFromCoords;
    window.locateUser = locateUser;

    $(document).ready(function() {
        if (!document.getElementById('map')) return;

        $('a[href="#address"]').on('shown.bs.tab', function() {
            setTimeout(() => { initMap(); }, 300);
        });

        if ($('#address').hasClass('active') || $('#address').hasClass('show')) {
            setTimeout(() => { initMap(); }, 500);
        }

        $('#location-search').on('input', function() {
            const query = $(this).val().trim();
            if (query.length >= 3) {
                searchLocation(query);
            } else {
                $('#search-results').hide();
            }
        });

        $('#location-search').on('keyup', function(e) {
            if ([37, 38, 39, 40, 13, 27].indexOf(e.keyCode) !== -1) {
                return;
            }
            const query = $(this).val().trim();
            if (query.length >= 3) {
                searchLocation(query);
            } else {
                $('#search-results').hide();
            }
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#location-search, #search-results').length) {
                $('#search-results').hide();
            }
        });

        $('#locate-me-btn').on('click', function() {
            locateUser();
        });
    });
})();
