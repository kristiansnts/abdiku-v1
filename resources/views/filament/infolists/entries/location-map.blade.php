@php
    use App\Domain\Attendance\Enums\EvidenceType;
    use App\Domain\Attendance\Enums\EvidenceAction;

    $record = $getRecord();

    // Company geofence (center of the circle)
    $fenceLat = $record->companyLocation?->latitude ?? null;
    $fenceLng = $record->companyLocation?->longitude ?? null;
    $radius = $record->companyLocation?->geofence_radius_meters ?? 100;

    // Employee's actual clock-in GPS from evidence
    $clockInEvidence = $record->evidences()
        ->where('type', EvidenceType::GEOLOCATION->value)
        ->where('action', EvidenceAction::CLOCK_IN->value)
        ->first();

    $employeeLat = null;
    $employeeLng = null;
    $withinGeofence = true;

    if ($clockInEvidence) {
        $payload = is_string($clockInEvidence->payload)
            ? json_decode($clockInEvidence->payload, true)
            : (array) $clockInEvidence->payload;
        $employeeLat = $payload['lat'] ?? null;
        $employeeLng = $payload['lng'] ?? null;
        $withinGeofence = $payload['within_geofence'] ?? true;
    }

    // Fall back to company location if no evidence GPS
    $centerLat = $employeeLat ?? $fenceLat ?? -6.2297;
    $centerLng = $employeeLng ?? $fenceLng ?? 106.8164;
@endphp

<div x-data="{
    map: null,
    employeeLat: @js($employeeLat),
    employeeLng: @js($employeeLng),
    fenceLat: @js($fenceLat),
    fenceLng: @js($fenceLng),
    radius: @js($radius),
    withinGeofence: @js($withinGeofence),
    centerLat: @js($centerLat),
    centerLng: @js($centerLng),

    init() {
        this.initMap();
    },

    initMap() {
        if (!window.google || !window.google.maps) {
            const checkGoogle = setInterval(() => {
                if (window.google && window.google.maps) {
                    clearInterval(checkGoogle);
                    this.createMap();
                }
            }, 100);
            setTimeout(() => clearInterval(checkGoogle), 10000);
            return;
        }
        this.createMap();
    },

    createMap() {
        const mapElement = this.$refs.map;
        if (!mapElement) return;

        this.map = new google.maps.Map(mapElement, {
            center: { lat: this.centerLat, lng: this.centerLng },
            zoom: 17,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            zoomControl: true,
            draggable: true,
            scrollwheel: true,
            disableDoubleClickZoom: false,
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });

        // Geofence circle at company location
        if (this.fenceLat && this.fenceLng && this.radius) {
            new google.maps.Circle({
                strokeColor: '#137fec',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#137fec',
                fillOpacity: 0.2,
                map: this.map,
                center: { lat: this.fenceLat, lng: this.fenceLng },
                radius: this.radius
            });
        }

        // Employee's actual clock-in location (red marker)
        if (this.employeeLat && this.employeeLng) {
            new google.maps.Marker({
                position: { lat: this.employeeLat, lng: this.employeeLng },
                map: this.map,
                draggable: false,
                title: 'Lokasi Karyawan',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: this.withinGeofence ? '#22c55e' : '#ef4444',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                }
            });

            // If outside geofence, also show company center marker and fit bounds
            if (!this.withinGeofence && this.fenceLat && this.fenceLng) {
                new google.maps.Marker({
                    position: { lat: this.fenceLat, lng: this.fenceLng },
                    map: this.map,
                    draggable: false,
                    title: 'Lokasi Kantor',
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 8,
                        fillColor: '#137fec',
                        fillOpacity: 1,
                        strokeColor: '#fff',
                        strokeWeight: 2,
                    }
                });

                const bounds = new google.maps.LatLngBounds();
                bounds.extend({ lat: this.employeeLat, lng: this.employeeLng });
                bounds.extend({ lat: this.fenceLat, lng: this.fenceLng });
                this.map.fitBounds(bounds, { top: 60, right: 60, bottom: 60, left: 60 });
            }
        } else if (this.fenceLat && this.fenceLng) {
            // Fallback: show company location marker
            new google.maps.Marker({
                position: { lat: this.fenceLat, lng: this.fenceLng },
                map: this.map,
                draggable: false,
                title: 'Lokasi Kantor',
            });
        }
    }
}" wire:ignore>
    <div class="relative rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600" style="height: 320px;">
        <div x-ref="map" class="absolute inset-0 w-full h-full bg-gray-200 dark:bg-gray-800"></div>

        {{-- Coordinates Display (employee GPS) --}}
        <div class="absolute top-3 left-3 z-10 bg-black/60 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-mono flex items-center gap-3">
            <span class="opacity-80">LAT: <span x-text="(employeeLat ?? fenceLat ?? 0).toFixed(6)"></span></span>
            <div class="w-px h-3 bg-white/30"></div>
            <span class="opacity-80">LNG: <span x-text="(employeeLng ?? fenceLng ?? 0).toFixed(6)"></span></span>
        </div>

        {{-- Geofence status badge --}}
        @if($employeeLat)
        <div class="absolute top-3 right-3 z-10 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-medium {{ $withinGeofence ? 'bg-green-600/90' : 'bg-red-600/90' }}">
            {{ $withinGeofence ? '✓ Dalam Area' : '✗ Di Luar Area' }}
        </div>
        @else
        <div class="absolute top-3 right-3 z-10 bg-primary-600/90 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-medium">
            Radius: <span x-text="radius"></span> meter
        </div>
        @endif

        {{-- Legend (when outside geofence) --}}
        @if($employeeLat && !$withinGeofence)
        <div class="absolute bottom-3 left-3 z-10 bg-black/60 backdrop-blur-md text-white px-3 py-2 rounded-lg text-xs flex flex-col gap-1">
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Lokasi Karyawan</div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Lokasi Kantor</div>
        </div>
        @endif
    </div>
</div>
