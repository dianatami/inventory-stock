<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventori TPN</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-body{min-height:100vh;padding:28px;background:#fffaf2;display:grid;place-items:center}
        .login-shell{width:min(1080px,100%);min-height:620px;display:grid;grid-template-columns:1.05fr .95fr;overflow:hidden;border:1px solid #eee2d2;border-radius:22px;background:#fff;box-shadow:0 18px 55px rgb(53 18 15 / 10%)}
        .login-brand{position:relative;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;padding:48px;background:#35120f;color:#fff}
        .login-brand::after{position:absolute;right:-90px;bottom:-130px;width:390px;height:390px;border:1px solid rgb(244 197 66 / 30%);border-radius:50%;content:""}
        .login-brand-head{display:flex;align-items:center;gap:13px}
        .login-company-logo{display:block;width:132px;height:132px;flex:0 0 auto;object-fit:contain}
        .login-brand-head strong{display:block;font-size:15px;letter-spacing:.08em}
        .login-brand-head span{display:block;margin-top:4px;color:#e7c9be;font-size:12px}
        .login-brand-copy{position:relative;z-index:1;max-width:430px}
        .login-brand-copy small{color:#f4c542;font-weight:700;letter-spacing:.13em}
        .login-brand-copy h1{margin:15px 0;font-size:38px;line-height:1.12}
        .login-brand-copy p{max-width:390px;color:#e7c9be;font-size:15px;line-height:1.75}
        .login-warehouse-visual{position:relative;z-index:1;display:flex;align-items:flex-end;gap:11px;height:112px;padding-left:10px}
        .login-warehouse-visual span{display:block;width:58px;border:1px solid #f4c542;border-radius:5px 5px 0 0;background:rgb(244 197 66 / 12%)}
        .login-warehouse-visual span:nth-child(1){height:49px}.login-warehouse-visual span:nth-child(2){height:78px}.login-warehouse-visual span:nth-child(3){height:62px}.login-warehouse-visual span:nth-child(4){height:96px}
        .login-form-side{display:grid;place-items:center;padding:48px}
        .login-card{width:min(390px,100%)}
        .login-company{margin:0 0 8px;color:#b42318;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
        .login-card h2{margin:0;color:#30211f;font-size:28px}
        .login-subtitle{margin:8px 0 26px;color:#81716d;font-size:14px;line-height:1.6}
        .login-error{margin-bottom:18px;padding:12px 14px;border:1px solid #fecaca;border-radius:9px;background:#fef2f2;color:#991b1b;font-size:13px}
        .login-field{display:grid;gap:7px;margin-top:17px}
        .login-field label{font-size:13px;font-weight:700;color:#30211f}
        .login-field input{width:100%;padding:12px 13px;border:1px solid #d8ccc1;border-radius:9px;background:#fff;color:#30211f;font:inherit}
        .login-field input:focus{outline:2px solid rgb(180 35 24 / 16%);border-color:#b42318}
        .login-password-wrap{position:relative}
        .login-password-wrap input{padding-right:76px}
        .login-password-toggle{position:absolute;top:50%;right:9px;transform:translateY(-50%);border:0;background:none;color:#8f1d14;font-size:12px;font-weight:700}
        .login-submit{width:100%;margin-top:24px;padding:13px;border:0;border-radius:9px;background:#b42318;color:white;font:inherit;font-weight:700;cursor:pointer}
        .login-submit:hover{background:#8f1d14}
        .login-footer{margin-top:26px;color:#81716d;font-size:12px;text-align:center}
        .login-field-error{margin:0;color:#b42318;font-size:12px}
        @media(max-width:760px){.login-body{padding:14px}.login-shell{grid-template-columns:1fr;min-height:0}.login-brand{min-height:250px;padding:26px}.login-brand-copy h1{font-size:28px}.login-brand-copy p{margin-bottom:0}.login-warehouse-visual{display:none}.login-form-side{padding:30px 24px 34px}}
    </style>
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-brand" aria-label="Informasi perusahaan">
            <div class="login-brand-head">
                <img class="login-company-logo"
                     src="{{ asset('images/logo-perusahaan.jpeg') }}"
                     alt="Logo PT Tiga Pemuda Nusantara">
                <div><strong>PT TIGA PEMUDA NUSANTARA</strong><span>Sistem inventori dan distribusi barang</span></div>
            </div>
            <div class="login-brand-copy">
                <small>INVENTORI TERINTEGRASI</small>
                <h1>Persediaan terpantau. Distribusi terkendali.</h1>
                <p>Kelola stok gudang, pesanan outlet, penerimaan, dan retur melalui satu sistem yang terhubung.</p>
            </div>
            <div class="login-warehouse-visual" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        </section>

        <section class="login-form-side">
            <div class="login-card">
                <p class="login-company">PT Tiga Pemuda Nusantara</p>
                <h2>Masuk ke sistem</h2>
                <p class="login-subtitle">Gunakan akun terdaftar untuk melanjutkan ke dashboard inventori.</p>

                @if ($errors->any())
                    <div class="login-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('login.process') }}" method="POST">
                    @csrf
                    <div class="login-field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="{{ old('username') }}"
                               placeholder="Masukkan username" autocomplete="username" required autofocus>
                        @error('username')<p class="login-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="login-field">
                        <label for="password">Password</label>
                        <div class="login-password-wrap">
                            <input type="password" id="password" name="password" placeholder="Masukkan password"
                                   autocomplete="current-password" required>
                            <button type="button" class="login-password-toggle" data-password-toggle
                                    aria-controls="password" aria-pressed="false">Lihat</button>
                        </div>
                        @error('password')<p class="login-field-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="login-submit">Login</button>
                </form>
                <p class="login-footer">&copy; {{ date('Y') }} PT Tiga Pemuda Nusantara</p>
            </div>
        </section>
    </main>
</body>
</html>
