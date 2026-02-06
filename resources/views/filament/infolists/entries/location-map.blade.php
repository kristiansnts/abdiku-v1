@php
    $record = $getRecord();
    $latitude = $record->companyLocation?->latitude ?? -6.2297;
    $longitude = $record->companyLocation?->longitude ?? 106.8164;
    $radius = $record->companyLocation?->geofence_radius_meters ?? 100;
@endphp

<div x-data="{
    map: null,
    marker: null,
    circle: null,
    latitude: @js($latitude),
    longitude: @js($longitude),
    radius: @js($radius),

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
            center: { lat: this.latitude, lng: this.longitude },
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

        this.marker = new google.maps.Marker({
            position: { lat: this.latitude, lng: this.longitude },
            map: this.map,
            draggable: false,
            title: 'Lokasi'
        });

        if (this.radius) {
            this.circle = new google.maps.Circle({
                strokeColor: '#137fec',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#137fec',
                fillOpacity: 0.2,
                map: this.map,
                center: { lat: this.latitude, lng: this.longitude },
                radius: this.radius
            });
        }
    }
}" wire:ignore>
    <div class="relative rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600" style="height: 300px;">
        <div x-ref="map" class="absolute inset-0 w-full h-full bg-gray-200 dark:bg-gray-800"></div>

        {{-- Coordinates Display --}}
        <div class="absolute top-3 left-3 z-10 bg-black/60 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-mono flex items-center gap-3">
            <span class="opacity-80">LAT: <span x-text="latitude.toFixed(6)"></span></span>
            <div class="w-px h-3 bg-white/30"></div>
            <span class="opacity-80">LNG: <span x-text="longitude.toFixed(6)"></span></span>
        </div>

        {{-- Radius Badge --}}
        <div class="absolute top-3 right-3 z-10 bg-primary-600/90 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-medium">
            Radius: <span x-text="radius"></span> meter
        </div>
    </div>
</div>
