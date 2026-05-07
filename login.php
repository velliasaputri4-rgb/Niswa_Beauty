<?php
session_start();

// Jika sudah login sebagai admin, redirect ke dashboard
if (isset($_SESSION['user']) && ($_SESSION['user_role'] ?? '') === 'admin') {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Login — NISWÀ BEAUTY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #F8EDE3, #F2E4D4); font-family: 'Poppins', sans-serif; }
        .login-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card {
            width: 100%; max-width: 420px; background: #fff;
            border-radius: 20px; padding: 40px;
            box-shadow: 0 20px 50px rgba(180,148,110,0.2);
            animation: fadeUp 0.4s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .brand { text-align: center; margin-bottom: 24px; font-weight: bold; font-size: 22px; color: #CBB89D; }
        .login-title { font-size: 26px; font-weight: 700; text-align: center; margin-bottom: 6px; color: #3a2e28; }
        .login-sub { text-align: center; font-size: 13px; color: #999; margin-bottom: 24px; }
        .form-control { border-radius: 12px; height: 50px; font-size: 14px; border: 1.5px solid #e8ddd0; }
        .form-control:focus { border-color: #CBB89D; box-shadow: 0 0 0 0.2rem rgba(203,184,157,0.25); }
        .btn-login {
            background: linear-gradient(135deg, #CBB89D, #b8a082);
            border: none; border-radius: 50px; height: 50px;
            font-weight: 600; color: white; font-size: 15px;
            transition: 0.3s; width: 100%; cursor: pointer;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(180,148,110,0.4); color: white; }
        .btn-home {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; margin-top: 12px; padding: 13px;
            border: 1.5px solid #e8ddd0; border-radius: 50px;
            color: #8b7055; font-size: 14px; font-weight: 500;
            text-decoration: none; transition: 0.3s; background: transparent;
        }
        .btn-home:hover { background: #faf5ef; border-color: #CBB89D; color: #5A4A42; }
        .input-wrap { position: relative; }
        .input-wrap .ico { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #CBB89D; cursor: pointer; }
        .alert-err {
            background: #fff0f0; border: 1px solid #fecdd3; color: #e11d48;
            border-radius: 12px; padding: 12px 16px; font-size: 13px;
            margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
        }
        .admin-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #faf5ef; border: 1px solid #e8ddd0;
            color: #8b7055; border-radius: 50px;
            font-size: 12px; padding: 4px 14px;
            margin: 0 auto 20px; font-weight: 500;
        }
        .admin-badge-wrap { text-align: center; }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">

        <div class="brand"><i class="fas fa-spa"></i> NISWÀ BEAUTY</div>
        <div class="admin-badge-wrap">
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin Access</span>
        </div>
        <div class="login-title">Admin Login</div>
        <div class="login-sub">Masukkan kredensial admin untuk melanjutkan</div>

        <?php if ($error): ?>
        <div class="alert-err">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form action="proses_login.php" method="POST">
            <input type="hidden" name="redirect" value="dashboard.php">

            <div class="mb-3">
                <label class="form-label fw-semibold">Email Admin</label>
                <div class="input-wrap">
                    <input type="email" name="email" class="form-control pe-5"
                           placeholder="admin@niswàbeauty.com"
                           value="<?= htmlspecialchars($_SESSION['last_email'] ?? '') ?>"
                           required autofocus>
                    <span class="ico"><i class="fas fa-envelope"></i></span>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="pwd"
                           class="form-control pe-5" placeholder="••••••••" required>
                    <span class="ico" onclick="togglePwd()"><i class="fas fa-eye" id="eyeIco"></i></span>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Masuk sebagai Admin
            </button>
        </form>

        <a href="index.php" class="btn-home" style="margin-top: 16px;">
            <i class="fas fa-house"></i> Kembali ke Home
        </a>

    </div>
</div>
<script>
function togglePwd() {
    const i = document.getElementById('pwd');
    const e = document.getElementById('eyeIco');
    i.type = i.type === 'password' ? 'text' : 'password';
    e.classList.toggle('fa-eye');
    e.classList.toggle('fa-eye-slash');
}
</script>
</body>
</html>