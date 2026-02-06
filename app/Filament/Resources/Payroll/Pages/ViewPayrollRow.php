<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Application\Payroll\Services\PayslipPdfService;
use App\Filament\Resources\Payroll\PayrollRowResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

final class ViewPayrollRow extends ViewRecord
{
    protected static string $resource = PayrollRowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_payslip')
                ->label('Unduh Slip Gaji')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->record->payrollBatch?->finalized_at !== null)
                ->action(function () {
                    $service = app(PayslipPdfService::class);
                    $pdfContent = $service->generate($this->record);
                    $filename = $service->generateFilename($this->record);

                    return response()->streamDownload(
                        fn () => print($pdfContent),
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                }),
        ];
    }
}
