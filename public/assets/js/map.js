// Shared map picker JS for places (city/area) create + edit pages and item preview.
// Supports both Leaflet (free_api) and Google Maps (google_places) providers.
(function () {
    'use strict';

    const mapRegistry = {}; // containerId -> { provider, map, marker }
    let googleMapsLoadPromise = null;

    function loadGoogleMapsScript(apiKey) {
        if (googleMapsLoadPromise) return googleMapsLoadPromise;
        googleMapsLoadPromise = new Promise((resolve, reject) => {
            if (window.google && window.google.maps) {
                resolve();
                return;
            }
            window.__onGoogleMapsLoadedShared = resolve;
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey) + '&loading=async&callback=__onGoogleMapsLoadedShared';
            script.async = true;
            script.defer = true;
            script.onerror = reject;
            document.head.appendChild(script);
        });
        return googleMapsLoadPromise;
    }

    function notifyChange(lat, lng, onChange) {
        updateCoordinates(lat, lng);
        if (typeof onChange === 'function') onChange(lat, lng);
    }

    function initLeaflet(containerId, lat, lng, zoom, opts) {
        const map = L.map(containerId).setView([lat, lng], zoom);

        const defaultIcon = L.icon({
            iconUrl: '/assets/css/images/marker-icon.png',
            shadowUrl: '/assets/css/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        L.Marker.prototype.options.icon = defaultIcon;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker([lat, lng], {
            draggable: opts.draggable !== false
        }).addTo(map);

        if (opts.interactive !== false) {
            map.on('click', function (e) {
                marker.setLatLng(e.latlng);
                notifyChange(e.latlng.lat, e.latlng.lng, opts.onChange);
            });

            marker.on('dragend', function (e) {
                const pos = e.target.getLatLng();
                notifyChange(pos.lat, pos.lng, opts.onChange);
            });
        }

        mapRegistry[containerId] = { provider: 'free_api', map, marker };
        return map;
    }

    function initGoogle(containerId, lat, lng, zoom, opts) {
        loadGoogleMapsScript(opts.googleMapKey).then(async () => {
            const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');

            const map = new google.maps.Map(document.getElementById(containerId), {
                center: { lat, lng },
                zoom,
                mapId: 'DEMO_MAP_ID'
            });

            const marker = new AdvancedMarkerElement({
                position: { lat, lng },
                map,
                gmpDraggable: opts.draggable !== false
            });

            if (opts.interactive !== false) {
                marker.addListener('dragend', function () {
                    const pos = marker.position;
                    notifyChange(pos.lat, pos.lng, opts.onChange);
                });

                map.addListener('click', function (e) {
                    const la = e.latLng.lat();
                    const ln = e.latLng.lng();
                    marker.position = { lat: la, lng: ln };
                    notifyChange(la, ln, opts.onChange);
                });
            }

            mapRegistry[containerId] = { provider: 'google_places', map, marker };
        }).catch(() => {
            initLeaflet(containerId, lat, lng, zoom, opts);
        });

        return null;
    }

    // Map initialization function
    // opts: { provider, googleMapKey, onChange(lat, lng), draggable, interactive }
    function initializeMap(containerId, defaultLat, defaultLng, defaultZoom = 13, opts = {}) {
        const provider = opts.provider || 'free_api';

        if (provider === 'google_places' && opts.googleMapKey) {
            return initGoogle(containerId, defaultLat, defaultLng, defaultZoom, opts);
        }
        return initLeaflet(containerId, defaultLat, defaultLng, defaultZoom, opts);
    }

    // Function to update coordinates in form fields
    function updateCoordinates(lat, lng) {
        // Update city coordinates if on city page
        if (typeof window.updateCityCoordinates === 'function') {
            window.updateCityCoordinates(lat, lng);
        }

        // Update area coordinates if on area page
        if (typeof window.updateAreaCoordinates === 'function') {
            window.updateAreaCoordinates(lat, lng);
        }
    }

    // Function to set map view to specific coordinates
    function setMapView(map, lat, lng, zoom = 13) {
        if (map && typeof map.setView === 'function') {
            map.setView([lat, lng], zoom);
        } else if (map && typeof map.setCenter === 'function') {
            map.setCenter({ lat, lng });
            map.setZoom(zoom);
        }
    }

    // Function to update marker position
    function updateMarkerPosition(marker, lat, lng) {
        if (!marker) return;
        if (typeof marker.setLatLng === 'function') {
            marker.setLatLng([lat, lng]);
        } else {
            marker.position = { lat, lng };
        }
    }

    // Export functions for use in other files
    window.mapUtils = {
        initializeMap,
        updateCoordinates,
        setMapView,
        updateMarkerPosition,
        removeMap(containerId) {
            const container = document.getElementById(containerId);
            const entry = mapRegistry[containerId];

            if (entry && entry.provider === 'free_api' && entry.map && typeof entry.map.remove === 'function') {
                entry.map.remove();
            }

            delete mapRegistry[containerId];

            // Clear inner HTML to fully reset the container (also resets Google Maps instances)
            if (container) container.innerHTML = '';
        }
    };
})();
