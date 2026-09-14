<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Absensi</title>
    <style>
        body {
            background-color: #243447; /* Warna background navy gelap */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-card {
            background-color: #FFFFFF;
            border-radius: 20px;
            padding: 40px 35px;
            width: 100%;
            max-width: 360px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
            text-align: center;
        }
        .login-header h2 {
            color: #1E293B;
            font-size: 24px;
            font-weight: 900;
            margin: 0;
            letter-spacing: 1px;
        }
        .login-header p {
            color: #64748B;
            font-size: 13px;
            margin-top: 8px;
            margin-bottom: 30px;
        }
        .input-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }
        .input-group input {
            width: 100%;
            padding: 14px 15px;
            border: 1.5px solid #E2E8F0;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s;
            background-color: #F8FAFC;
        }
        .input-group input:focus {
            border-color: #3498DB;
            background-color: #FFFFFF;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: #3498DB;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.3);
            letter-spacing: 1px;
        }
        .btn-login:hover {
            background-color: #2980B9;
            transform: translateY(-1px);
        }
        .login-footer {
            margin-top: 35px;
            font-size: 12px;
            color: #94A3B8;
        }
        .login-footer span {
            color: #475569;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Judul -->
        <div class="login-header">
            <h2>SISTEM ABSENSI</h2>
            <p>Login untuk mengakses panel</p>
        </div>

        <!-- Alert Jika Login Gagal -->
        @if ($errors->any())
            <div style="background-color: #FEE2E2; color: #B91C1C; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; font-weight: bold;">
                ❌ NIP atau Password salah!
            </div>
        @endif

        <!-- Form Login -->
        <form method="POST" action="/login" autocomplete="off">
            @csrf

            <div class="input-group">
                <label for="email">Username / NIP</label>
                <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="off" placeholder="Masukkan username / NIP">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Masukkan password">
            </div>

            <button type="submit" class="btn-login">MASUK</button>
        </form>

        <!-- Footer Hak Cipta -->
        <div class="login-footer">
            <p>Powered By <span>Abdul Hakim</span></p>
            <p style="margin-top: -5px;">Pastikan akun Anda memiliki hak akses.</p>
        </div>
    </div>

</body>
</html>