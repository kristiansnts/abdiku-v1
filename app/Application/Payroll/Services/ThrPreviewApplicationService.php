<?php

declare(strict_types=1);

namespace App\Application\Payroll\Services;

use App\Domain\Payroll\Contracts\PayrollPeriodRepositoryInterface;
use Illuminate\Support\HtmlString;

final class ThrPreviewApplicationService
{
    public function __construct(
        private readonly BulkThrCalculationApplicationService $bulkThrService,
        private readonly PayrollPeriodRepositoryInterface $periodRepository
    ) {
    }

    /**
     * Generate HTML preview for UI display
     */
    public function generateHtmlPreview(
        int $companyId,
        int $periodId,
        string $defaultEmployeeType = 'permanent',
        int $workingDaysInYear = 260
    ): HtmlString {
        try {
            $preview = $this->bulkThrService->generatePreview(
                $companyId,
                $periodId,
                $defaultEmployeeType,
                $workingDaysInYear
            );

            return $this->renderPreviewHtml($preview);
        } catch (\Exception $e) {
            return new HtmlString(
                '<div class="text-red-600 p-4 border border-red-200 rounded">' .
                'Error: ' . htmlspecialchars($e->getMessage()) .
                '</div>'
            );
        }
    }

    /**
     * Get formatted options for period selection
     */
    public function getFormattedPeriodOptions(int $companyId): array
    {
        return $this->periodRepository->getFormattedOptionsForCompany($companyId);
    }

    /**
     * Render preview data as HTML
     */
    private function renderPreviewHtml(array $preview): HtmlString
    {
        if (empty($preview['employees'])) {
            return new HtmlString(
                '<div class="text-gray-600 p-4 border border-gray-200 rounded">' .
                'Tidak ada karyawan yang memenuhi syarat THR atau semua sudah memiliki THR untuk periode ini.' .
                '</div>'
            );
        }

        $summary = $preview['summary'];
        $employees = $preview['employees'];
        $errors = $preview['errors'];

        $html = '<div class="space-y-4">';
        
        // Summary section
        $html .= '<div class="bg-green-50 p-4 rounded-lg border border-green-200">';
        $html .= '<h4 class="font-semibold text-green-800 mb-2">Ringkasan</h4>';
        $html .= '<div class="grid grid-cols-2 gap-4 text-sm">';
        $html .= '<div>Jumlah Karyawan Eligible: ' . $summary['eligible_employees'] . '</div>';
        $html .= '<div>Total THR: Rp ' . number_format($summary['total_thr_amount'], 0, ',', '.') . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Table section
        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="w-full text-sm border border-gray-200">';
        $html .= '<thead class="bg-gray-50">';
        $html .= '<tr>';
        $html .= '<th class="p-2 text-left border border-gray-200">Karyawan</th>';
        $html .= '<th class="p-2 text-right border border-gray-200">Masa Kerja (bulan)</th>';
        $html .= '<th class="p-2 text-right border border-gray-200">Jumlah THR</th>';
        $html .= '<th class="p-2 text-left border border-gray-200">Keterangan</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($employees as $employee) {
            $html .= '<tr class="hover:bg-gray-50">';
            $html .= '<td class="p-2 border border-gray-200 font-medium">' . htmlspecialchars($employee['employee_name']) . '</td>';
            $html .= '<td class="p-2 border border-gray-200 text-right">' . number_format($employee['months_worked'], 1) . '</td>';
            $html .= '<td class="p-2 border border-gray-200 text-right font-mono">';
            $html .= 'Rp ' . number_format($employee['thr_amount'], 0, ',', '.');
            $html .= '</td>';
            $html .= '<td class="p-2 border border-gray-200 text-xs text-gray-600">' . htmlspecialchars($employee['calculation_notes']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        // Error section
        if (!empty($errors)) {
            $html .= '<div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">';
            $html .= '<h4 class="font-semibold text-yellow-800 mb-2">Errors (' . count($errors) . ')</h4>';
            $html .= '<ul class="text-sm text-yellow-700 space-y-1">';
            foreach (\array_slice($errors, 0, 5) as $error) {
                $html .= '<li>• ' . htmlspecialchars($error) . '</li>';
            }
            if (count($errors) > 5) {
                $html .= '<li class="text-xs">... dan ' . (count($errors) - 5) . ' error lainnya</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }
}