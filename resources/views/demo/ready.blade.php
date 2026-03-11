<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Siap - PayrollKami</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1B3FA0 0%, #4CAF50 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .card {
            background: white; border-radius: 16px;
            padding: 48px 40px; max-width: 500px; width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            text-align: center;
        }
        .logo { font-size: 32px; font-weight: 800; color: #1B3FA0; margin-bottom: 8px; }
        .logo span { color: #4CAF50; }
        .success-icon { font-size: 48px; margin: 16px 0; }
        h1 { font-size: 22px; color: #1a1a2e; margin-bottom: 8px; }
        .company-name { color: #4CAF50; font-weight: 600; font-size: 16px; margin-bottom: 24px; }

        .credentials-box {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
            text-align: left;
        }
        .credentials-box h2 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin-bottom: 16px;
            text-align: center;
        }
        .credential-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .credential-row:last-child { border-bottom: none; }
        .cred-label {
            font-size: 12px; color: #6b7280;
            text-transform: uppercase; letter-spacing: 0.05em;
            min-width: 80px;
        }
        .cred-value {
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 14px; color: #1a1a2e; font-weight: 600;
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 6px; padding: 6px 10px;
            cursor: pointer; transition: background 0.15s;
            flex: 1; margin-left: 12px; text-align: center;
        }
        .cred-value:hover { background: #eff6ff; border-color: #1B3FA0; }
        .copy-hint { font-size: 11px; color: #9ca3af; margin-top: 8px; text-align: center; }

        .btn {
            display: block; width: 100%;
            background: #1B3FA0; color: white;
            border: none; padding: 16px;
            border-radius: 10px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
            text-decoration: none; margin-top: 8px;
        }
        .btn:hover { background: #163080; }
        .note { font-size: 12px; color: #9ca3af; margin-top: 16px; line-height: 1.5; }

        .copied-toast {
            position: fixed; top: 20px; right: 20px;
            background: #1B3FA0; color: white;
            padding: 10px 18px; border-radius: 8px;
            font-size: 14px; font-weight: 500;
            opacity: 0; transform: translateY(-8px);
            transition: all 0.2s;
            pointer-events: none;
        }
        .copied-toast.show { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>
    <div class="copied-toast" id="toast">✓ Disalin!</div>

    <div class="card">
        <div class="logo">Payroll<span>Kami</span></div>
        <div class="success-icon">🎉</div>
        <h1>Demo Anda Siap!</h1>
        <p class="company-name">{{ $company }}</p>

        <div class="credentials-box">
            <h2>Kredensial Login</h2>
            <div class="credential-row">
                <span class="cred-label">Email</span>
                <span class="cred-value" onclick="copyText(this, '{{ $email }}')">{{ $email }}</span>
            </div>
            <div class="credential-row">
                <span class="cred-label">Password</span>
                <span class="cred-value" onclick="copyText(this, '{{ $password }}')">{{ $password }}</span>
            </div>
            <p class="copy-hint">Klik untuk menyalin</p>
        </div>

        <a href="/admin" class="btn">🚀 Masuk ke Dashboard</a>

        <p class="note">
            Simpan kredensial ini sebelum masuk. Sesi demo berlaku 24 jam.<br>
            Semua perubahan akan direset setelah sesi berakhir.
        </p>
    </div>

    <script>
        function copyText(el, text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.getElementById('toast');
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 1800);
            });
        }
    </script>
</body>
</html>
