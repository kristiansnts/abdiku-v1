<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo PayrollKami Siap</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f3f4f6; color: #1a1a2e;
        }
        .wrapper { max-width: 560px; margin: 40px auto; padding: 0 16px; }
        .card {
            background: #ffffff; border-radius: 16px;
            overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #1B3FA0 0%, #2563eb 100%);
            padding: 40px 40px 32px; text-align: center;
        }
        .logo { font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; }
        .logo span { color: #4ade80; }
        .header-subtitle { color: rgba(255,255,255,0.8); font-size: 14px; margin-top: 6px; }
        .body { padding: 36px 40px; }
        h1 { font-size: 22px; font-weight: 700; margin: 0 0 8px; color: #1a1a2e; }
        .greeting { color: #4b5563; font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .company-badge {
            display: inline-block;
            background: #eff6ff; color: #1B3FA0;
            border: 1px solid #bfdbfe;
            border-radius: 8px; padding: 6px 14px;
            font-size: 13px; font-weight: 600;
            margin-bottom: 24px;
        }
        .creds-box {
            background: #f8fafc; border: 2px solid #e2e8f0;
            border-radius: 12px; padding: 24px; margin-bottom: 28px;
        }
        .creds-title {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em; color: #6b7280; margin-bottom: 16px;
        }
        .cred-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid #e2e8f0;
        }
        .cred-row:last-child { border-bottom: none; }
        .cred-label { font-size: 13px; color: #6b7280; min-width: 90px; }
        .cred-value {
            font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace;
            font-size: 14px; font-weight: 700; color: #1a1a2e;
            background: #fff; border: 1px solid #d1d5db;
            border-radius: 6px; padding: 5px 10px; word-break: break-all;
        }
        .btn-wrapper { text-align: center; margin-bottom: 28px; }
        .btn {
            display: inline-block;
            background: #1B3FA0; color: #ffffff !important;
            text-decoration: none; font-size: 15px; font-weight: 700;
            padding: 14px 36px; border-radius: 10px;
            letter-spacing: 0.02em;
        }
        .expiry-box {
            background: #fffbeb; border: 1px solid #fde68a;
            border-radius: 8px; padding: 12px 16px;
            font-size: 13px; color: #92400e; margin-bottom: 24px;
        }
        .footer { padding: 24px 40px; border-top: 1px solid #f3f4f6; text-align: center; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.6; margin: 0; }
        .footer a { color: #1B3FA0; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <div class="logo">Payroll<span>Kami</span></div>
            <div class="header-subtitle">Sistem Penggajian & HR Modern</div>
        </div>

        <div class="body">
            <h1>Demo Anda Sudah Siap! 🎉</h1>
            <p class="greeting">
                Halo <strong>{{ $ownerName }}</strong>,<br>
                Akun demo PayrollKami untuk perusahaan Anda sudah siap digunakan.
                Silakan login menggunakan kredensial berikut:
            </p>

            <div class="company-badge">🏢 {{ $companyName }}</div>

            <!-- Web / Admin Panel -->
            <div class="creds-box">
                <div class="creds-title">💻 Login Panel Admin (Web)</div>
                <div class="cred-row">
                    <span class="cred-label">URL</span>
                    <span class="cred-value">console.payrollkami.app</span>
                </div>
                <div class="cred-row">
                    <span class="cred-label">Email</span>
                    <span class="cred-value">{{ $ownerEmail }}</span>
                </div>
                <div class="cred-row">
                    <span class="cred-label">Password</span>
                    <span class="cred-value">{{ $password }}</span>
                </div>
            </div>

            <div class="btn-wrapper">
                <a href="https://console.payrollkami.app/admin" class="btn">
                    🚀 Buka Panel Admin
                </a>
            </div>

            @if($employeeEmail)
            <!-- Mobile App -->
            <div class="creds-box" style="border-color: #4ade80; margin-top: 20px;">
                <div class="creds-title" style="color: #166534;">📱 Login Aplikasi Mobile (Karyawan)</div>
                <div class="cred-row">
                    <span class="cred-label">Aplikasi</span>
                    <span class="cred-value">PayrollKami (App Store / Play Store)</span>
                </div>
                <div class="cred-row">
                    <span class="cred-label">Email</span>
                    <span class="cred-value">{{ $employeeEmail }}</span>
                </div>
                <div class="cred-row">
                    <span class="cred-label">Password</span>
                    <span class="cred-value">{{ $employeePassword }}</span>
                </div>
            </div>
            <p style="font-size: 12px; color: #6b7280; text-align: center; margin-bottom: 24px;">
                Gunakan akun karyawan ini untuk mencoba fitur absensi, payslip, dan cuti di aplikasi mobile.
            </p>
            @endif

            <div class="expiry-box">
                ⏰ <strong>Perhatian:</strong> Sesi demo berlaku selama <strong>24 jam</strong>.
                Data demo akan dihapus otomatis setelah masa berlaku habis.
            </div>

            <p style="font-size: 14px; color: #4b5563; line-height: 1.7;">
                Demo ini sudah dilengkapi dengan:<br>
                ✅ 12 karyawan dengan data lengkap<br>
                ✅ Penggajian bulan lalu sudah selesai<br>
                ✅ Absensi &amp; geofence 2 lokasi kantor<br>
                ✅ BPJS &amp; PPh21 sudah dikonfigurasi
            </p>
        </div>

        <div class="footer">
            <p>
                Email ini dikirim karena Anda mendaftar demo di
                <a href="https://payrollkami.app">payrollkami.app</a>.<br>
                Jika bukan Anda, abaikan email ini.<br><br>
                &copy; {{ date('Y') }} PayrollKami. Semua hak dilindungi.
            </p>
        </div>
    </div>
</div>
</body>
</html>
