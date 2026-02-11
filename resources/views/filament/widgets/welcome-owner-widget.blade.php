<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Selamat Datang di PayrollKami!
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Anda belum memiliki perusahaan. Mari kita mulai dengan membuat perusahaan Anda.
                    </p>
                </div>
            </div>

            <div
                class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4 border border-primary-200 dark:border-primary-800">
                <h3 class="font-semibold text-primary-900 dark:text-primary-100 mb-2">
                    Langkah Selanjutnya:
                </h3>
                <ol class="list-decimal list-inside space-y-2 text-sm text-primary-800 dark:text-primary-200">
                    <li>Klik menu <strong>"Pengaturan Perusahaan"</strong> di sidebar</li>
                    <li>Isi nama perusahaan Anda</li>
                    <li>Tambahkan lokasi kantor (opsional)</li>
                    <li>Klik <strong>"Simpan"</strong> untuk membuat perusahaan</li>
                </ol>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('filament.admin.pages.company-settings') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                    Buat Perusahaan Sekarang
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>