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
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $errors[] = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.';
    } else {
        $stmt = mysqli_prepare($con, "SELECT id, name, password FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($pass, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('location: index.php');
            exit();
        } else {
            $errors[] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | سوقك</title>
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
            padding: 20px;
        }
        .split {
            display: flex;
            width: 100%;
            max-width: 900px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(26,26,46,.15);
        }
        .panel-left {
            flex: 1;
            background: linear-gradient(145deg, #1a1a2e, #0f3460);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .panel-left .logo { font-size: 30px; font-weight: 800; color: #fff; }
        .panel-left .logo span { color: var(--amber); }
        .panel-left .tagline { color: rgba(255,255,255,.65); font-size: 15px; margin-top: 10px; line-height: 1.7; }
        .panel-left .perks { margin-top: 40px; list-style: none; }
        .panel-left .perks li {
            color: rgba(255,255,255,.8);
            font-size: 14px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .panel-left .perks li::before { content: '✓'; color: var(--amber); font-weight: 800; }

        .panel-right {
            flex: 1;
            background: #fff;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        h2 { font-size: 24px; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
        .subtitle { font-size: 14px; color: var(--muted); margin-bottom: 30px; }

        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-family: 'Tajawal', sans-serif;
            font-size: 15px;
            color: var(--ink);
            transition: border-color .2s;
            outline: none;
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
            margin-top: 6px;
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
        .success-box {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            border-radius: 10px;
            padding: 12px 16px;
            color: #15803d;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .link-row {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--muted);
        }
        .link-row a { color: var(--clay); font-weight: 600; text-decoration: none; }
        .back-link {
            display: inline-block;
            margin-top: 24px;
            font-size: 13px;
            color: var(--muted);
            text-decoration: none;
        }
        .back-link:hover { color: var(--ink); }

        @media (max-width: 640px) {
            .split { flex-direction: column; }
            .panel-left { padding: 30px 24px; }
            .panel-right { padding: 30px 24px; }
        }
    </style>
</head>
<body>
<div class="split">
    <div class="panel-left">
        <div>
            <div class="logo">سو<span>قك</span></div>
            <div class="tagline">منصة الإعلانات الأولى في الأردن<br>بيع واشترِ بأمان وبدون عمولة</div>
        </div>
        <ul class="perks">
            <li>نشر الإعلانات مجاناً تماماً</li>
            <li>تواصل مباشر مع البائع</li>
            <li>آلاف الإعلانات يومياً</li>
            <li>حماية بياناتك أولويتنا</li>
        </ul>
    </div>

    <div class="panel-right">
        <h2>أهلاً بعودتك 👋</h2>
        <p class="subtitle">سجّل دخولك للوصول إلى إعلاناتك</p>

        <?php if (isset($_GET['registered'])): ?>
            <div class="success-box">✅ تم إنشاء حسابك بنجاح! يمكنك الدخول الآن.</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-box"><?= e($errors[0]) ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" required placeholder="example@email.com"
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" name="submit" class="btn-primary">تسجيل الدخول</button>
        </form>

        <div class="link-row">
            ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a>
        </div>
        <a class="back-link" href="index.php">← العودة للرئيسية</a>
    </div>
</div>
</body>
</html>
