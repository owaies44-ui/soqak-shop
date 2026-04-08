<?php
include 'config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}

$errors = [];

if (isset($_POST['submit'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $cpass    = $_POST['cpassword'] ?? '';

    if (empty($name))     $errors[] = 'الاسم مطلوب.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صالح.';
    if (empty($phone))    $errors[] = 'رقم الهاتف مطلوب.';
    if (empty($location)) $errors[] = 'الموقع مطلوب.';
    if (strlen($pass) < 8) $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.';
    if ($pass !== $cpass) $errors[] = 'كلمتا المرور غير متطابقتين.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($con, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'هذا البريد الإلكتروني مسجل مسبقاً.';
        } else {
            mysqli_stmt_close($stmt);
            $hashed_pass = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($con, "INSERT INTO users (name, email, password, phone, location) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssss', $name, $email, $hashed_pass, $phone, $location);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('location: login.php?registered=1');
                exit();
            } else {
                $errors[] = 'حدث خطأ أثناء إنشاء الحساب.';
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب | سوقك</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1a1a2e; --amber: #e8a838; --amber-dk: #c8891a;
            --clay: #c75c3a; --sand: #fdf6ee; --border: #ede8e0;
            --muted: #7a7a8c; --radius: 14px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--sand);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(26,26,46,.12);
            padding: 44px 40px;
            width: 100%;
            max-width: 520px;
        }
        .card-header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 28px; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
        .logo span { color: var(--amber); }
        h2 { font-size: 22px; font-weight: 700; color: var(--ink); }
        .subtitle { font-size: 14px; color: var(--muted); margin-top: 4px; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }

        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-family: 'Tajawal', sans-serif;
            font-size: 15px;
            color: var(--ink);
            outline: none;
            transition: border-color .2s;
        }
        input:focus { border-color: var(--amber); }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: var(--ink);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Tajawal', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s;
            margin-top: 8px;
        }
        .btn-primary:hover { background: #0f3460; }

        .error-box {
            background: #fff5f5;
            border: 1.5px solid #fca5a5;
            border-radius: 10px;
            padding: 12px 16px;
            color: #b91c1c;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .error-box ul { padding-right: 18px; margin: 0; }
        .error-box li { margin: 4px 0; }

        .divider { border: none; border-top: 1px solid var(--border); margin: 24px 0; }
        .link-row { text-align: center; font-size: 14px; color: var(--muted); }
        .link-row a { color: var(--clay); font-weight: 600; text-decoration: none; }

        @media (max-width: 480px) {
            .card { padding: 30px 20px; }
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="logo">سو<span>قك</span></div>
        <h2>إنشاء حساب جديد</h2>
        <p class="subtitle">انضم مجاناً وابدأ البيع والشراء</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <ul>
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <div class="grid-2">
            <div class="form-group">
                <label>الاسم الكامل</label>
                <input type="text" name="name" required placeholder="أحمد محمد"
                       value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>المدينة / الموقع</label>
                <input type="text" name="location" required placeholder="عمّان"
                       value="<?= e($_POST['location'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" required placeholder="example@email.com"
                   value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>رقم الهاتف</label>
            <input type="tel" name="phone" required placeholder="0791234567"
                   value="<?= e($_POST['phone'] ?? '') ?>">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required placeholder="8 أحرف على الأقل">
            </div>
            <div class="form-group">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="cpassword" required placeholder="أعد الكتابة">
            </div>
        </div>

        <button type="submit" name="submit" class="btn-primary">إنشاء الحساب</button>
    </form>

    <hr class="divider">
    <div class="link-row">
        لديك حساب؟ <a href="login.php">تسجيل الدخول</a>
    </div>
</div>
</body>
</html>
