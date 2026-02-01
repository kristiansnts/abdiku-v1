<div x-data="{
        state: $wire.$entangle('{{ $getStatePath() }}'),
        map: null,
        marker: null,
        circle: null,
        searchBox: null,
        parentPath: '{{ $getParentStatePath() }}',
        
        latitude: @js($getLatitude() ?? -6.2297),
        longitude: @js($getLongitude() ?? 106.8164),
        radius: @js($getRadius() ?? 100),
        address: @js($getAddress() ?? ''),
        
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
                draggable: true,
                title: 'Lokasi'
            });
            
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
            
            // Set initial address in search input
            if (this.address && this.$refs.searchInput) {
                this.$refs.searchInput.value = this.address;
            }
            
            this.marker.addListener('dragend', (event) => {
                const newLat = event.latLng.lat();
                const newLng = event.latLng.lng();
                this.latitude = newLat;
                this.longitude = newLng;
                this.circle.setCenter({ lat: newLat, lng: newLng });
                this.updateState();
                
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: { lat: newLat, lng: newLng } }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        this.address = results[0].formatted_address;
                        this.$refs.searchInput.value = this.address;
                        this.$wire.set(this.parentPath + '.address', this.address);
                    }
                });
            });
            
            const searchInput = this.$refs.searchInput;
            if (searchInput) {
                const autocomplete = new google.maps.places.Autocomplete(searchInput, {
                    fields: ['formatted_address', 'geometry', 'name'],
                    componentRestrictions: { country: 'id' }
                });
                
                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (!place.geometry || !place.geometry.location) return;
                    
                    const newLat = place.geometry.location.lat();
                    const newLng = place.geometry.location.lng();
                    
                    this.latitude = newLat;
                    this.longitude = newLng;
                    this.address = place.formatted_address || '';
                    
                    this.map.setCenter({ lat: newLat, lng: newLng });
                    this.marker.setPosition({ lat: newLat, lng: newLng });
                    this.circle.setCenter({ lat: newLat, lng: newLng });
                    
                    this.updateState();
                    this.$wire.set(this.parentPath + '.address', this.address);
                    if (place.name) {
                        this.$wire.set(this.parentPath + '.name', place.name);
                    }
                });
            }
        },
        
        updateMapPosition() {
            if (!this.map || !this.marker || !this.circle) return;
            
            const newCenter = { lat: this.latitude, lng: this.longitude };
            this.map.setCenter(newCenter);
            this.marker.setPosition(newCenter);
            this.circle.setCenter(newCenter);
            this.circle.setRadius(this.radius);
            
            // Update search input with address
            if (this.address && this.$refs.searchInput) {
                this.$refs.searchInput.value = this.address;
            }
        },
        
        updateState() {
            this.$wire.set(this.parentPath + '.latitude', this.latitude);
            this.$wire.set(this.parentPath + '.longitude', this.longitude);
        },
        
        updateRadius(newRadius) {
            this.radius = parseInt(newRadius);
            if (this.circle) {
                this.circle.setRadius(this.radius);
            }
            this.$wire.set(this.parentPath + '.geofence_radius_meters', this.radius);
        }
    }" wire:ignore class="space-y-4">
    {{-- Search Input --}}
    <div class="relative">
        <div style="padding-left: 1rem;" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <x-heroicon-o-map-pin class="h-5 w-5 text-gray-400" />
        </div>
        <input style="padding-left: 2.5rem;" x-ref="searchInput" type="text" placeholder="Cari alamat..."
            class="block w-full pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
    </div>

    {{-- Map Container --}}
    <div class="relative rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600" style="height: 400px;">
        <div x-ref="map" class="absolute inset-0 w-full h-full bg-gray-200 dark:bg-gray-800"></div>

        {{-- Coordinates Display --}}
        <div
            class="absolute top-3 left-3 z-10 bg-black/60 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-mono flex items-center gap-3">
            <span class="opacity-80">LAT: <span x-text="latitude.toFixed(6)"></span></span>
            <div class="w-px h-3 bg-white/30"></div>
            <span class="opacity-80">LNG: <span x-text="longitude.toFixed(6)"></span></span>
        </div>
    </div>

    {{-- Radius Control --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
        <div class="flex justify-between items-center">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Radius Geofence</label>
            <span class="text-sm font-bold text-primary-600 dark:text-primary-400" x-text="radius + ' meter'"></span>
        </div>
        <input type="range" min="10" max="500" step="10" x-model="radius" @input="updateRadius($event.target.value)"
            class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-primary-600">
        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>10m</span>
            <span>250m</span>
            <span>500m</span>
        </div>
    </div>
</div>