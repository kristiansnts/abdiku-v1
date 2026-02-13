<?php

declare(strict_types=1);

namespace App\Domain\Leave\Services;

use App\Models\MasterHoliday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndonesiaHolidayService
{
    // URL Ical Google Calendar untuk Hari Libur Indonesia
    private const ICAL_URL = 'https://calendar.google.com/calendar/ical/id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics';

    /**
     * Sync global master holidays from external iCal API
     */
    public function syncMasterHolidays(): array
    {
        try {
            Log::info('Memulai sinkronisasi Ical Hari Libur Indonesia...');
            
            $response = Http::get(self::ICAL_URL);
            
            if (!$response->successful()) {
                throw new \RuntimeException('Gagal mengambil data Ical dari server Google.');
            }

            $events = $this->parseIcal($response->body());
            $syncedCount = 0;

            foreach ($events as $event) {
                // Gunakan updateOrCreate untuk menghindari duplikasi berdasarkan external_id atau nama+tanggal
                $master = MasterHoliday::updateOrCreate(
                    ['external_id' => $event['uid']],
                    [
                        'name' => $event['summary'],
                        'date' => $event['start_date'],
                        'is_cuti_bersama' => str_contains(strtolower($event['summary']), 'cuti bersama'),
                    ]
                );

                if ($master->wasRecentlyCreated || $master->wasChanged()) {
                    $syncedCount++;
                }
            }

            Log::info("Sinkronisasi selesai. Berhasil memperbarui $syncedCount hari libur.");
            
            return [
                'success' => true,
                'count' => $syncedCount,
                'total' => count($events)
            ];

        } catch (\Exception $e) {
            Log::error('Holiday Sync Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Distribute master holidays to all companies that have auto-sync enabled
     */
    public function distributeToTenants(): void
    {
        $masterHolidays = MasterHoliday::all();
        $companies = \App\Models\Company::where('auto_sync_holidays', true)->get();

        foreach ($companies as $company) {
            foreach ($masterHolidays as $master) {
                $existing = \App\Domain\Leave\Models\Holiday::where('company_id', $company->id)
                    ->where('master_holiday_id', $master->id)
                    ->first();

                if (!$existing) {
                    // Buat baru jika belum ada
                    \App\Domain\Leave\Models\Holiday::create([
                        'company_id' => $company->id,
                        'master_holiday_id' => $master->id,
                        'name' => $master->name,
                        'date' => $master->date,
                        'is_paid' => !$master->is_cuti_bersama, // Cuti bersama biasanya memotong jatah
                    ]);
                } elseif ($existing->date->format('Y-m-d') !== $master->date->format('Y-m-d')) {
                    // Update dan beri notifikasi jika tanggal berubah
                    $oldDate = $existing->date->format('d M Y');
                    $existing->update(['date' => $master->date]);

                    $this->notifyOwnerOfChange($company, $master->name, $oldDate, $master->date->format('d M Y'));
                }
            }
        }
    }

    /**
     * Send database notification to company owners/admins
     */
    private function notifyOwnerOfChange($company, string $holidayName, string $oldDate, string $newDate): void
    {
        $owners = $company->users()->whereIn('role', ['OWNER', 'HR'])->get();
        
        $notification = \Filament\Notifications\Notification::make()
            ->title('Update Jadwal Libur Nasional')
            ->body("Pemerintah telah mengubah tanggal libur **{$holidayName}** dari {$oldDate} menjadi **{$newDate}**. Sistem telah menyesuaikan kalender perusahaan Anda secara otomatis.")
            ->warning()
            ->persistent();

        foreach ($owners as $user) {
            $notification->sendToDatabase($user);
        }
    }

    /**
     * Simple Ical Parser for Google Calendar format
     */
    private function parseIcal(string $content): array
    {
        $events = [];
        $lines = explode("\n", $content);
        $currentEvent = null;

        foreach ($lines as $line) {
            $line = trim($line);
            
            if ($line === 'BEGIN:VEVENT') {
                $currentEvent = [];
            } elseif ($line === 'END:VEVENT') {
                if (isset($currentEvent['uid'], $currentEvent['start_date'], $currentEvent['summary'])) {
                    $events[] = $currentEvent;
                }
                $currentEvent = null;
            } elseif ($currentEvent !== null) {
                if (str_starts_with($line, 'UID:')) {
                    $currentEvent['uid'] = substr($line, 4);
                } elseif (str_starts_with($line, 'DTSTART;VALUE=DATE:')) {
                    $dateStr = substr($line, 19);
                    $currentEvent['start_date'] = Carbon::createFromFormat('Ymd', $dateStr)->toDateString();
                } elseif (str_starts_with($line, 'SUMMARY:')) {
                    $currentEvent['summary'] = substr($line, 8);
                }
            }
        }

        return $events;
    }
}
