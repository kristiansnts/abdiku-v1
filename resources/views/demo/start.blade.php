<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coba Demo - PayrollKami</title>
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
            padding: 48px 40px; max-width: 480px; width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            text-align: center;
        }
        .logo { font-size: 32px; font-weight: 800; color: #1B3FA0; margin-bottom: 8px; }
        .logo span { color: #4CAF50; }
        h1 { font-size: 22px; color: #1a1a2e; margin-bottom: 12px; }
        p { color: #6b7280; line-height: 1.6; margin-bottom: 24px; font-size: 15px; }
        .features { text-align: left; margin-bottom: 32px; }
        .feature {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 0; color: #374151; font-size: 14px;
        }
        .check { color: #4CAF50; font-size: 18px; flex-shrink: 0; }
        .btn {
            display: block; width: 100%;
            background: #1B3FA0; color: white;
            border: none; padding: 16px;
            border-radius: 10px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
            text-decoration: none;
        }
        .btn:hover { background: #163080; }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .spinner {
            display: none; width: 20px; height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-text { display: none; }
        .error { color: #ef4444; font-size: 14px; margin-top: 12px; }
        .note { font-size: 12px; color: #9ca3af; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Payroll<span>Kami</span></div>
        <h1>Coba Demo Gratis</h1>
        <p>Eksplorasi fitur lengkap PayrollKami dengan data demo yang realistis. Tidak perlu kartu kredit.</p>

        <div class="features">
            <div class="feature"><span class="check">✓</span> 10 karyawan dengan data lengkap</div>
            <div class="feature"><span class="check">✓</span> Penggajian bulan lalu sudah selesai</div>
            <div class="feature"><span class="check">✓</span> Absensi & geofence 2 lokasi kantor</div>
            <div class="feature"><span class="check">✓</span> BPJS & PPh21 sudah dikonfigurasi</div>
        </div>

        @if ($errors->any())
            <div class="error">{{ $errors->first('error') }}</div>
        @endif

        <form method="POST" action="{{ route('demo.start') }}" id="demoForm">
            @csrf
            <button type="submit" class="btn" id="startBtn">
                <span id="btnText">🚀 Mulai Demo Sekarang</span>
                <span class="spinner" id="spinner"></span>
                <span class="loading-text" id="loadingText">Menyiapkan data demo...</span>
            </button>
        </form>

        <p class="note">Sesi demo bersifat terisolasi. Data Anda tidak mempengaruhi pengguna lain.</p>
    </div>

    <script>
        document.getElementById('demoForm').addEventListener('submit', function() {
            const btn = document.getElementById('startBtn');
            btn.disabled = true;
            document.getElementById('btnText').style.display = 'none';
            document.getElementById('spinner').style.display = 'block';
            document.getElementById('loadingText').style.display = 'inline';
        });
    </script>
</body>
</html>
