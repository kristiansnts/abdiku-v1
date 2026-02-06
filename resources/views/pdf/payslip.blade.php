<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Slip Gaji - {{ $payslip->employeeName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #333;
        }

        .container {
            padding: 20px 30px;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 8pt;
            color: #666;
        }

        /* Title */
        .title-section {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #f5f5f5;
        }

        .title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .period {
            font-size: 10pt;
        }

        /* Sections */
        .section {
            margin: 15px 0;
        }

        .section-title {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 8px;
            padding-bottom: 3px;
            border-bottom: 1px solid #ccc;
            color: #333;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            padding: 4px 6px;
            vertical-align: top;
        }

        .label {
            width: 40%;
            color: #555;
        }

        .value {
            width: 60%;
        }

        .amount {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .total-row {
            border-top: 1px solid #333;
            font-weight: bold;
        }

        .total-row td {
            padding-top: 8px;
        }

        /* Two columns layout */
        .columns {
            width: 100%;
        }

        .columns td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .column-left {
            padding-right: 10px !important;
        }

        .column-right {
            padding-left: 10px !important;
        }

        /* Attendance grid */
        .attendance-grid {
            border: 1px solid #ddd;
            margin-top: 8px;
        }

        .attendance-grid th {
            background: #f5f5f5;
            font-size: 8pt;
            text-align: center;
            border: 1px solid #ddd;
            padding: 5px 3px;
        }

        .attendance-grid td {
            text-align: center;
            border: 1px solid #ddd;
            padding: 5px 3px;
        }

        /* Summary box */
        .summary-box {
            margin-top: 20px;
            border: 2px solid #333;
            padding: 15px;
            text-align: center;
            background: #f9f9f9;
        }

        .summary-label {
            font-size: 10pt;
            color: #666;
            margin-bottom: 5px;
        }

        .net-amount {
            font-size: 16pt;
            font-weight: bold;
            color: #000;
        }

        /* Employer contribution */
        .employer-section {
            background: #fafafa;
            padding: 10px;
            margin-top: 15px;
            border: 1px dashed #ccc;
        }

        .employer-section .section-title {
            font-size: 9pt;
            color: #666;
        }

        .employer-note {
            font-size: 8pt;
            color: #888;
            font-style: italic;
        }

        /* Footer */
        .footer {
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #888;
        }

        .footer p {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Company Header --}}
        <div class="header">
            <div class="company-name">{{ $payslip->companyName }}</div>
            @if($payslip->companyAddress)
                <div class="company-info">{{ $payslip->companyAddress }}</div>
            @endif
            @if($payslip->companyPhone || $payslip->companyEmail)
                <div class="company-info">
                    @if($payslip->companyPhone)Telp: {{ $payslip->companyPhone }}@endif
                    @if($payslip->companyPhone && $payslip->companyEmail) | @endif
                    @if($payslip->companyEmail)Email: {{ $payslip->companyEmail }}@endif
                </div>
            @endif
            @if($payslip->companyNpwp)
                <div class="company-info">NPWP: {{ $payslip->companyNpwp }}</div>
            @endif
        </div>

        {{-- Title --}}
        <div class="title-section">
            <div class="title">SLIP GAJI KARYAWAN</div>
            <div class="period">
                Periode: {{ $payslip->monthName }} {{ $payslip->year }}
                ({{ $payslip->periodStart }} - {{ $payslip->periodEnd }})
            </div>
        </div>

        {{-- Employee Info --}}
        <div class="section">
            <div class="section-title">INFORMASI KARYAWAN</div>
            <table>
                <tr>
                    <td class="label">Nama Karyawan</td>
                    <td class="value">: {{ $payslip->employeeName }}</td>
                </tr>
                <tr>
                    <td class="label">ID Karyawan</td>
                    <td class="value">: #{{ $payslip->employeeId }}</td>
                </tr>
            </table>
        </div>

        {{-- Attendance Summary --}}
        <div class="section">
            <div class="section-title">RINGKASAN KEHADIRAN</div>
            <table>
                <tr>
                    <td class="label">Total Hari Kerja</td>
                    <td class="value">: {{ $payslip->totalWorkingDays }} hari</td>
                </tr>
                <tr>
                    <td class="label">Hari Dibayar</td>
                    <td class="value">: {{ $payslip->payableDays }} hari</td>
                </tr>
            </table>

            <table class="attendance-grid">
                <tr>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Cuti Dibayar</th>
                    <th>Sakit Dibayar</th>
                    <th>Libur Dibayar</th>
                    <th>Absen</th>
                </tr>
                <tr>
                    <td>{{ $payslip->attendanceBreakdown['hadir'] }}</td>
                    <td>{{ $payslip->attendanceBreakdown['terlambat'] }}</td>
                    <td>{{ $payslip->attendanceBreakdown['cuti_dibayar'] }}</td>
                    <td>{{ $payslip->attendanceBreakdown['sakit_dibayar'] }}</td>
                    <td>{{ $payslip->attendanceBreakdown['libur_dibayar'] }}</td>
                    <td>{{ $payslip->attendanceBreakdown['absen'] }}</td>
                </tr>
            </table>
        </div>

        {{-- Earnings and Deductions (Two Columns) --}}
        <table class="columns">
            <tr>
                <td class="column-left">
                    <div class="section">
                        <div class="section-title">PENDAPATAN</div>
                        <table>
                            <tr>
                                <td>Gaji Pokok</td>
                                <td class="amount">Rp {{ number_format($payslip->baseSalary, 0, ',', '.') }}</td>
                            </tr>
                            @if($payslip->proratedBaseSalary != $payslip->baseSalary)
                            <tr>
                                <td>Gaji Prorata</td>
                                <td class="amount">Rp {{ number_format($payslip->proratedBaseSalary, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @foreach($payslip->allowances as $allowance)
                            <tr>
                                <td>{{ $allowance['name'] }}</td>
                                <td class="amount">Rp {{ number_format($allowance['amount'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            @foreach($payslip->additions as $addition)
                            <tr>
                                <td>{{ $addition['name'] }}</td>
                                <td class="amount">Rp {{ number_format($addition['amount'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            <tr class="total-row">
                                <td>Total Pendapatan</td>
                                <td class="amount">Rp {{ number_format($payslip->grossAmount, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td class="column-right">
                    <div class="section">
                        <div class="section-title">POTONGAN KARYAWAN</div>
                        <table>
                            @forelse($payslip->deductions as $deduction)
                                @if($deduction['employee_amount'] > 0)
                                <tr>
                                    <td>{{ $deduction['name'] }}</td>
                                    <td class="amount">Rp {{ number_format($deduction['employee_amount'], 0, ',', '.') }}</td>
                                </tr>
                                @endif
                            @empty
                            <tr>
                                <td colspan="2" style="color: #888; font-style: italic;">Tidak ada potongan</td>
                            </tr>
                            @endforelse
                            <tr class="total-row">
                                <td>Total Potongan</td>
                                <td class="amount">Rp {{ number_format($payslip->totalDeductions, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Employer Contributions --}}
        @if($payslip->totalEmployerDeductions > 0)
        <div class="employer-section">
            <div class="section-title">KONTRIBUSI PERUSAHAAN</div>
            <p class="employer-note">* Tidak mengurangi take home pay karyawan</p>
            <table style="margin-top: 8px;">
                @foreach($payslip->deductions as $deduction)
                    @if($deduction['employer_amount'] > 0)
                    <tr>
                        <td style="width: 70%;">{{ $deduction['name'] }} (Pemberi Kerja)</td>
                        <td class="amount">Rp {{ number_format($deduction['employer_amount'], 0, ',', '.') }}</td>
                    </tr>
                    @endif
                @endforeach
                <tr class="total-row">
                    <td>Total Kontribusi Perusahaan</td>
                    <td class="amount">Rp {{ number_format($payslip->totalEmployerDeductions, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
        @endif

        {{-- Net Amount Box --}}
        <div class="summary-box">
            <div class="summary-label">TAKE HOME PAY</div>
            <div class="net-amount">Rp {{ number_format($payslip->netAmount, 0, ',', '.') }}</div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Dicetak pada: {{ now()->format('d M Y H:i') }} WIB</p>
            <p>Dokumen ini digenerate secara elektronik dan sah tanpa tanda tangan.</p>
        </div>
    </div>
</body>
</html>
