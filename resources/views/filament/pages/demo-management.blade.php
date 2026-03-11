<x-filament-panels::page>
    <div class="space-y-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="fi-section-header-heading text-base font-semibold text-gray-950 dark:text-white">
                        Sesi Demo Aktif
                    </h3>
                    <span class="fi-badge rounded-full px-3 py-1 text-sm font-medium bg-primary-50 text-primary-700">
                        {{ $this->demoCompanies->count() }} sesi
                    </span>
                </div>
            </div>

            <div class="fi-section-content px-6 pb-6">
                @if ($this->demoCompanies->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                        Tidak ada sesi demo aktif.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                            <thead class="text-xs text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="py-3 pr-4">ID</th>
                                    <th class="py-3 pr-4">Nama Perusahaan</th>
                                    <th class="py-3 pr-4">Pengguna</th>
                                    <th class="py-3 pr-4">Karyawan</th>
                                    <th class="py-3">Dibuat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($this->demoCompanies as $company)
                                    <tr>
                                        <td class="py-3 pr-4 font-mono text-xs text-gray-400">{{ $company->id }}</td>
                                        <td class="py-3 pr-4 font-medium">{{ $company->name }}</td>
                                        <td class="py-3 pr-4">{{ $company->users_count }}</td>
                                        <td class="py-3 pr-4">{{ $company->employees_count }}</td>
                                        <td class="py-3 text-gray-400">{{ $company->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-2">Demo URL</h3>
            <p class="text-sm text-gray-500 mb-3">Bagikan URL ini kepada calon pelanggan:</p>
            <code class="block bg-gray-100 dark:bg-gray-800 rounded-lg px-4 py-3 text-sm font-mono text-primary-700 dark:text-primary-400">
                {{ url('/demo/start') }}
            </code>
        </div>
    </div>
</x-filament-panels::page>
