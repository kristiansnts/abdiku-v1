<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Status Penggajian Bulan Ini</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ $this->payrollIndexUrl }}"
               class="fi-btn fi-btn-size-sm fi-btn-color-primary fi-btn-outlined inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border border-primary-600 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950 transition-colors">
                <x-heroicon-o-arrow-right class="w-4 h-4" />
                Kelola Penggajian
            </a>
        </x-slot>

        @if ($this->currentPeriod)
            @php $period = $this->currentPeriod; @endphp
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Periode</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $period->period_start->format('d M') }} – {{ $period->period_end->format('d M Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    <span @class([
                        'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => $period->state === \App\Domain\Payroll\Enums\PayrollState::DRAFT,
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $period->state === \App\Domain\Payroll\Enums\PayrollState::REVIEW,
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $period->state === \App\Domain\Payroll\Enums\PayrollState::FINALIZED,
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $period->state === \App\Domain\Payroll\Enums\PayrollState::LOCKED,
                    ])>
                        {{ $period->state->getLabel() }}
                    </span>
                </div>
                @if ($period->payrollBatch)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Gaji</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            Rp {{ number_format((float) $period->payrollBatch->rows()->sum('net_amount'), 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jumlah Karyawan</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $period->payrollBatch->rows()->count() }} karyawan
                        </p>
                    </div>
                @else
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Penggajian</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">Belum diproses</p>
                    </div>
                @endif
            </div>
        @else
            <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-calendar class="w-5 h-5" />
                <div>
                    <p class="text-sm font-medium">Belum ada periode penggajian bulan ini</p>
                    <p class="text-xs">Buat periode baru untuk memulai proses penggajian.</p>
                </div>
                <a href="{{ route('filament.admin.resources.payroll-periods.create') }}"
                   class="ml-auto fi-btn-sm inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    Buat Periode
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
