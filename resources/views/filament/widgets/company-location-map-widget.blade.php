<x-filament-widgets::widget>
    <x-filament::section heading="Lokasi Perusahaan">
        <div x-data="{
            map: null,
            locations: @js($this->getLocations()),
            
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
                if (!mapElement || this.locations.length === 0) {
                    if (mapElement && this.locations.length === 0) {
                        mapElement.innerHTML = '<div style=&quot;display: flex; align-items: center; justify-content: center; height: 100%; color: #6b7280;&quot;>Tidak ada lokasi tersedia</div>';
                    }
                    return;
                }
                
                this.map = new google.maps.Map(mapElement, {
                    zoom: 17,
                    center: { lat: this.locations[0].lat, lng: this.locations[0].lng },
                    mapTypeControl: false,
                    streetViewControl: false,
                });
                
                const bounds = new google.maps.LatLngBounds();
                
                this.locations.forEach((location) => {
                    const position = { lat: location.lat, lng: location.lng };
                    
                    const marker = new google.maps.Marker({
                        position: position,
                        map: this.map,
                        title: location.name,
                    });
                    
                    if (location.radius) {
                        new google.maps.Circle({
                            strokeColor: '#4043e9ff',
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                            fillColor: '#4074e9',
                            fillOpacity: 0.2,
                            map: this.map,
                            center: position,
                            radius: location.radius,
                        });
                    }
                    
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style=&quot;padding: 8px; color: #111827;&quot;>
                                <strong style=&quot;display: block; margin-bottom: 4px;&quot;>${location.name}</strong>
                                <p style=&quot;margin: 0; color: #4b5563; font-size: 0.875rem;&quot;>${location.address || '-'}</p>
                                ${location.radius ? `<p style=&quot;margin: 4px 0 0; color: #6b7280; font-size: 0.75rem;&quot;>Radius: ${location.radius}m</p>` : ''}
                            </div>
                        `,
                    });
                    
                    marker.addListener('click', () => {
                        infoWindow.open(this.map, marker);
                    });
                    
                    bounds.extend(position);
                });
                
                if (this.locations.length > 1) {
                    this.map.fitBounds(bounds);
                }
            }
        }" wire:ignore class="space-y-4">
            {{-- Map container --}}
            <div x-ref="map" style="height: 400px; width: 100%; border-radius: 0.5rem;"
                class="bg-gray-100 dark:bg-gray-800"></div>

            {{-- Location List --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <template x-for="location in locations" :key="location.id">
                    <div @click="map.setCenter({lat: location.lat, lng: location.lng}); map.setZoom(17)"
                        class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 cursor-pointer hover:border-primary-500 dark:hover:border-primary-500 transition-colors shadow-sm group">
                        <div
                            class="mt-1 bg-primary-50 dark:bg-primary-900/30 p-2 rounded-lg group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                            <x-heroicon-s-map-pin class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate" x-text="location.name">
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1"
                                x-text="location.address || '-'"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>