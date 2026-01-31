<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Models\Employee;
use Carbon\Carbon;

/**
 * Service untuk menghitung Tunjangan Hari Raya (THR) berdasarkan ketentuan yang berlaku
 * 
 * Mendukung berbagai jenis karyawan:
 * - Karyawan Tetap
 * - Karyawan Kontrak
 * - Karyawan Harian Lepas (Freelance)
 * - Karyawan Baru
 * - Karyawan yang Mengundurkan Diri
 */
class CalculateThrService
{
    /**
     * Menghitung THR untuk seorang karyawan
     *
     * @param Employee $employee
     * @param Carbon $thrCalculationDate Tanggal perhitungan THR (biasanya tanggal pembayaran)
     * @param string $employeeType Jenis karyawan: 'permanent', 'contract', 'daily', 'freelance'
     * @param int|null $workingDaysInYear Untuk karyawan harian/freelance (total hari kerja dalam tahun)
     * @return array
     */
    public function calculate(
        Employee $employee, 
        Carbon $thrCalculationDate, 
        string $employeeType = 'permanent',
        ?int $workingDaysInYear = null
    ): array {
        // Validasi input
        $this->validateInputs($employee, $thrCalculationDate, $employeeType);

        // Dapatkan kompensasi aktif
        $compensation = $this->getActiveCompensation($employee);
        if (!$compensation) {
            return $this->createThrResult(0, 0, 0, 'Tidak ada kompensasi aktif');
        }

        // Hitung masa kerja
        $workPeriod = $this->calculateWorkPeriod($employee, $thrCalculationDate);
        
        // Dapatkan gaji pokok untuk perhitungan
        $baseSalary = (float) $compensation->base_salary;

        // Hitung THR berdasarkan jenis karyawan
        $thrAmount = match ($employeeType) {
            'permanent' => $this->calculatePermanentEmployeeThr($baseSalary, $workPeriod),
            'contract' => $this->calculateContractEmployeeThr($baseSalary, $workPeriod),
            'daily', 'freelance' => $this->calculateDailyEmployeeThr($baseSalary, $workingDaysInYear ?? 0, $workPeriod['days_worked']),
            default => $this->calculatePermanentEmployeeThr($baseSalary, $workPeriod)
        };

        return $this->createThrResult(
            $thrAmount, 
            $baseSalary, 
            $workPeriod['months_worked'], 
            $this->generateCalculationNote($employeeType, $workPeriod, $baseSalary, $thrAmount)
        );
    }

    /**
     * Menghitung THR untuk karyawan tetap
     *
     * @param float $baseSalary
     * @param array $workPeriod
     * @return float
     */
    protected function calculatePermanentEmployeeThr(float $baseSalary, array $workPeriod): float
    {
        $monthsWorked = $workPeriod['months_worked'];
        
        // Jika sudah bekerja 12 bulan atau lebih, dapatkan THR penuh
        if ($monthsWorked >= 12) {
            return $baseSalary;
        }
        
        // Jika kurang dari 12 bulan, hitung proporsional
        if ($monthsWorked >= 1) {
            return ($monthsWorked / 12) * $baseSalary;
        }
        
        return 0;
    }

    /**
     * Menghitung THR untuk karyawan kontrak
     *
     * @param float $baseSalary
     * @param array $workPeriod
     * @return float
     */
    protected function calculateContractEmployeeThr(float $baseSalary, array $workPeriod): float
    {
        $monthsWorked = $workPeriod['months_worked'];
        
        // Karyawan kontrak mendapat THR jika sudah bekerja minimal 1 bulan
        if ($monthsWorked >= 1) {
            return ($monthsWorked / 12) * $baseSalary;
        }
        
        return 0;
    }

    /**
     * Menghitung THR untuk karyawan harian lepas/freelance
     *
     * @param float $monthlySalary
     * @param int $totalWorkingDays
     * @param int $actualWorkDays
     * @return float
     */
    protected function calculateDailyEmployeeThr(float $monthlySalary, int $totalWorkingDays, int $actualWorkDays): float
    {
        if ($totalWorkingDays <= 0 || $actualWorkDays <= 0) {
            return 0;
        }
        
        // THR = (Jumlah Hari Kerja / Jumlah Hari dalam Tahun) × Gaji Bulanan
        $daysInYear = 365;
        return ($actualWorkDays / $daysInYear) * $monthlySalary;
    }

    /**
     * Menghitung masa kerja karyawan
     *
     * @param Employee $employee
     * @param Carbon $calculationDate
     * @return array
     */
    protected function calculateWorkPeriod(Employee $employee, Carbon $calculationDate): array
    {
        $joinDate = $employee->join_date;
        $endDate = $employee->resign_date ?? $calculationDate;
        
        // Pastikan end date tidak melebihi calculation date
        if ($endDate->gt($calculationDate)) {
            $endDate = $calculationDate;
        }

        $diffInMonths = $joinDate->diffInMonths($endDate);
        $diffInDays = $joinDate->diffInDays($endDate);

        return [
            'months_worked' => $diffInMonths,
            'days_worked' => $diffInDays,
            'join_date' => $joinDate,
            'end_date' => $endDate,
            'is_resigned' => $employee->resign_date !== null
        ];
    }

    /**
     * Mendapatkan kompensasi aktif karyawan
     *
     * @param Employee $employee
     * @return EmployeeCompensation|null
     */
    protected function getActiveCompensation(Employee $employee): ?EmployeeCompensation
    {
        return $employee->compensations()
            ->whereNull('effective_to')
            ->latest('effective_from')
            ->first();
    }

    /**
     * Validasi input parameter
     *
     * @param Employee $employee
     * @param Carbon $thrCalculationDate
     * @param string $employeeType
     * @throws \InvalidArgumentException
     */
    protected function validateInputs(Employee $employee, Carbon $thrCalculationDate, string $employeeType): void
    {
        $validEmployeeTypes = ['permanent', 'contract', 'daily', 'freelance'];
        
        if (!in_array($employeeType, $validEmployeeTypes)) {
            throw new \InvalidArgumentException("Invalid employee type: {$employeeType}. Must be one of: " . implode(', ', $validEmployeeTypes));
        }

        if ($thrCalculationDate->lt($employee->join_date)) {
            throw new \InvalidArgumentException("THR calculation date cannot be before employee join date");
        }
    }

    /**
     * Membuat hasil perhitungan THR
     *
     * @param float $thrAmount
     * @param float $baseSalary
     * @param float $monthsWorked
     * @param string $notes
     * @return array
     */
    protected function createThrResult(float $thrAmount, float $baseSalary, float $monthsWorked, string $notes): array
    {
        return [
            'thr_amount' => round($thrAmount, 2),
            'base_salary' => $baseSalary,
            'months_worked' => round($monthsWorked, 1),
            'calculation_notes' => $notes,
            'calculation_date' => now(),
        ];
    }

    /**
     * Menghasilkan catatan perhitungan THR
     *
     * @param string $employeeType
     * @param array $workPeriod
     * @param float $baseSalary
     * @param float $thrAmount
     * @return string
     */
    protected function generateCalculationNote(string $employeeType, array $workPeriod, float $baseSalary, float $thrAmount): string
    {
        $monthsWorked = $workPeriod['months_worked'];
        $isResigned = $workPeriod['is_resigned'];
        
        $typeLabel = match ($employeeType) {
            'permanent' => 'Karyawan Tetap',
            'contract' => 'Karyawan Kontrak',
            'daily' => 'Karyawan Harian',
            'freelance' => 'Freelance',
            default => 'Karyawan'
        };

        if ($thrAmount == 0) {
            return "{$typeLabel} - Tidak berhak THR (masa kerja kurang dari 1 bulan)";
        }

        $monthsWorkedFormatted = round($monthsWorked, 1);
        
        if ($monthsWorked >= 12 && $employeeType === 'permanent') {
            return "{$typeLabel} - THR penuh (masa kerja {$monthsWorkedFormatted} bulan)";
        }

        $calculation = match ($employeeType) {
            'daily', 'freelance' => "THR = (Hari kerja / 365) × Gaji bulanan",
            default => "THR = ({$monthsWorkedFormatted} bulan / 12) × Rp " . number_format($baseSalary, 0, ',', '.')
        };

        $status = $isResigned ? ' (Karyawan yang mengundurkan diri)' : '';
        
        return "{$typeLabel}{$status} - {$calculation} = Rp " . number_format($thrAmount, 0, ',', '.');
    }

    /**
     * Menghitung THR untuk multiple karyawan
     *
     * @param \Illuminate\Support\Collection $employees
     * @param Carbon $thrCalculationDate
     * @param array $employeeTypeMapping Array mapping employee_id => employee_type
     * @return array
     */
    public function calculateBatch($employees, Carbon $thrCalculationDate, array $employeeTypeMapping = []): array
    {
        $results = [];
        
        foreach ($employees as $employee) {
            $employeeType = $employeeTypeMapping[$employee->id] ?? 'permanent';
            
            try {
                $thrResult = $this->calculate($employee, $thrCalculationDate, $employeeType);
                $results[$employee->id] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'success' => true,
                    'data' => $thrResult
                ];
            } catch (\Exception $e) {
                $results[$employee->id] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}