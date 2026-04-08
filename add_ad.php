<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors  = [];
$success_msg = '';

if (isset($_POST['submit_ad'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $category_id = (int)($_POST['category_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $condition   = $_POST['condition'] ?? '';

    if (empty($title))       $errors[] = 'عنوان الإعلان مطلوب.';
    if ($category_id <= 0)   $errors[] = 'يرجى اختيار القسم.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'السعر غير صالح.';
    if (!in_array($condition, ['جديد', 'مستعمل'])) $errors[] = 'حالة المنتج غير صالحة.';

    $valid_images = [];
    if (isset($_FILES['images']) && $_FILES['images']['name'][0] !== '') {
        $total = count($_FILES['images']['name']);
        if ($total > MAX_IMAGES_PER_AD) {
            $errors[] = 'الحد الأقصى للصور هو ' . MAX_IMAGES_PER_AD . ' صور.';
        } else {
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) { $errors[] = 'خطأ في رفع الصورة ' . ($i+1) . '.'; continue; }
                if ($_FILES['images']['size'][$i] > MAX_IMAGE_SIZE) { $errors[] = 'الصورة ' . ($i+1) . ' تتجاوز 5MB.'; continue; }
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($_FILES['images']['tmp_name'][$i]);
                if (!in_array($mime, ALLOWED_IMAGE_TYPES)) { $errors[] = 'نوع ملف غير مسموح في الصورة ' . ($i+1) . '.'; continue; }
                $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
                $valid_images[] = ['tmp_name' => $_FILES['images']['tmp_name'][$i], 'ext' => $ext_map[$mime]];
            }
        }
    } else {
        $errors[] = 'يرجى إضافة صورة واحدة على الأقل.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($con, "INSERT INTO ads (user_id, category_id, title, description, price, item_condition) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iissds', $user_id, $category_id, $title, $description, $price, $condition);
        if (mysqli_stmt_execute($stmt)) {
            $ad_id = mysqli_insert_id($con);
            mysqli_stmt_close($stmt);
            if (!is_dir('images')) mkdir('images', 0755, true);
            foreach ($valid_images as $img) {
                $new_name    = bin2hex(random_bytes(16)) . '.' . $img['ext'];
                $upload_path = 'images/' . $new_name;
                if (move_uploaded_file($img['tmp_name'], $upload_path)) {
                    $stmt2 = mysqli_prepare($con, "INSERT INTO ad_images (ad_id, image_path) VALUES (?, ?)");
                    mysqli_stmt_bind_param($stmt2, 'is', $ad_id, $upload_path);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);
                }
            }
            $success_msg = 'تم نشر إعلانك بنجاح! ✅';
        } else {
            $errors[] = 'حدث خطأ أثناء نشر الإعلان.';
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أضف إعلاناً | سوقك</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1a1a2e; --amber: #e8a838; --clay: #c75c3a;
            --sand: #fdf6ee; --border: #ede8e0; --muted: #7a7a8c; --radius: 14px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Tajawal', sans-serif; background: var(--sand); min-height: 100vh; }

        .topbar {
            background: linear-gradient(135deg, #1a1a2e, #0f3460);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo { font-size: 22px; font-weight: 800; color: #fff; text-decoration: none; }
        .logo span { color: var(--amber); }
        .back-btn {
            color: rgba(255,255,255,.8); text-decoration: none; font-size: 14px;
            font-weight: 500; border: 1px solid rgba(255,255,255,.25);
            padding: 7px 16px; border-radius: 100px; transition: all .2s;
        }
        .back-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

        .page { max-width: 700px; margin: 40px auto; padding: 0 20px; }

        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 26px; font-weight: 800; color: var(--ink); }
        .page-header p { font-size: 14px; color: var(--muted); margin-top: 4px; }

        .card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 4px 24px rgba(26,26,46,.07);
            border: 1px solid var(--border);
            padding: 30px;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 18px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-family: 'Tajawal', sans-serif;
            font-size: 15px;
            color: var(--ink);
            outline: none;
            transition: border-color .2s;
            background: #fff;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--amber); }
        textarea { resize: vertical; }

        /* FILE UPLOAD */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            position: relative;
        }
        .upload-zone:hover { border-color: var(--amber); background: #fffbf3; }
        .upload-zone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-icon { font-size: 36px; margin-bottom: 10px; }
        .upload-zone p { font-size: 14px; color: var(--muted); }
        .upload-zone .hint-small { font-size: 12px; margin-top: 6px; color: #bbb; }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--ink);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Tajawal', sans-serif;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s;
        }
        .btn-submit:hover { background: #0f3460; }

        .success-box {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            border-radius: 10px;
            padding: 14px 18px;
            color: #15803d;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }
        .error-box {
            background: #fff5f5;
            border: 1.5px solid #fca5a5;
            border-radius: 10px;
            padding: 14px 18px;
            color: #b91c1c;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .error-box ul { padding-right: 18px; margin: 0; }
        .error-box li { margin: 4px 0; }

        @media (max-width: 500px) {
            .form-row { grid-template-columns: 1fr; }
            .card { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <a class="logo" href="index.php">سو<span>قك</span></a>
    <a class="back-btn" href="products.php">← إعلاناتي</a>
</div>

<div class="page">
    <div class="page-header">
        <h1>إضافة إعلان جديد</h1>
        <p>أكمل البيانات أدناه لنشر إعلانك في ثوانٍ</p>
    </div>

    <?php if ($success_msg): ?>
        <div class="success-box"><?= e($success_msg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="error-box"><ul><?php foreach ($errors as $e_): ?><li><?= e($e_) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <!-- BASIC INFO -->
        <div class="card">
            <div class="card-title">معلومات المنتج</div>

            <div class="form-group">
                <label>عنوان الإعلان</label>
                <input type="text" name="title" required placeholder="مثال: سيارة تويوتا كامري 2020"
                       value="<?= e($_POST['title'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>القسم</label>
                    <select name="category_id" required>
                        <option value="">اختر القسم</option>
                        <?php
                        $cat_query = mysqli_query($con, "SELECT * FROM categories ORDER BY name");
                        while ($cat = mysqli_fetch_assoc($cat_query)):
                        ?>
                            <option value="<?= (int)$cat['id'] ?>"
                                <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>حالة المنتج</label>
                    <select name="condition" required>
                        <option value="جديد"   <?= (($_POST['condition'] ?? '') === 'جديد')   ? 'selected' : '' ?>>جديد</option>
                        <option value="مستعمل" <?= (($_POST['condition'] ?? '') === 'مستعمل') ? 'selected' : '' ?>>مستعمل</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>السعر (دينار أردني)</label>
                <input type="number" name="price" required placeholder="0.00" min="0" step="0.01"
                       value="<?= e($_POST['price'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>وصف المنتج</label>
                <textarea name="description" rows="5" required
                          placeholder="اكتب وصفاً دقيقاً: الحالة، المميزات، سبب البيع..."><?= e($_POST['description'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- IMAGES -->
        <div class="card">
            <div class="card-title">صور المنتج</div>
            <div class="upload-zone">
                <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" required>
                <div class="upload-icon">🖼️</div>
                <p>اسحب الصور هنا أو انقر للاختيار</p>
                <p class="hint-small">JPG، PNG، WebP، GIF — حتى <?= MAX_IMAGES_PER_AD ?> صور، 5MB لكل صورة</p>
            </div>
        </div>

        <button type="submit" name="submit_ad" class="btn-submit">🚀 نشر الإعلان الآن</button>
    </form>
</div>

</body>
</html>
