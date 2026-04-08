<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) { header('location: login.php'); exit(); }

$user_id = (int)$_SESSION['user_id'];
$errors  = [];
$success_msg = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header('location: products.php'); exit(); }
$ad_id = (int)$_GET['id'];

$stmt = mysqli_prepare($con, "SELECT * FROM ads WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $ad_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$ad  = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
if (!$ad) { header('location: products.php'); exit(); }

$img_stmt = mysqli_prepare($con, "SELECT id, image_path FROM ad_images WHERE ad_id = ?");
mysqli_stmt_bind_param($img_stmt, 'i', $ad_id);
mysqli_stmt_execute($img_stmt);
$existing_images = mysqli_stmt_get_result($img_stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($img_stmt);

if (isset($_POST['update_ad'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $condition   = $_POST['condition'] ?? '';

    if (empty($title))     $errors[] = 'عنوان الإعلان مطلوب.';
    if ($category_id <= 0) $errors[] = 'يرجى اختيار القسم.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'السعر غير صالح.';
    if (!in_array($condition, ['جديد', 'مستعمل'])) $errors[] = 'حالة المنتج غير صالحة.';

    $new_valid_images = [];
    if (isset($_FILES['images']) && $_FILES['images']['name'][0] !== '') {
        $total = count($_FILES['images']['name']);
        $existing_count = count($existing_images);
        if (($existing_count + $total) > MAX_IMAGES_PER_AD) {
            $errors[] = 'الحد الأقصى ' . MAX_IMAGES_PER_AD . ' صور. لديك ' . $existing_count . ' حالياً.';
        } else {
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['images']['size'][$i] > MAX_IMAGE_SIZE) { $errors[] = 'الصورة ' . ($i+1) . ' تتجاوز 5MB.'; continue; }
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($_FILES['images']['tmp_name'][$i]);
                if (!in_array($mime, ALLOWED_IMAGE_TYPES)) { $errors[] = 'نوع ملف غير مسموح في الصورة ' . ($i+1) . '.'; continue; }
                $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
                $new_valid_images[] = ['tmp_name' => $_FILES['images']['tmp_name'][$i], 'ext' => $ext_map[$mime]];
            }
        }
    }

    if (empty($errors)) {
        $upd = mysqli_prepare($con, "UPDATE ads SET category_id=?, title=?, description=?, price=?, item_condition=? WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($upd, 'issdsis', $category_id, $title, $description, $price, $condition, $ad_id, $user_id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);

        $delete_ids = array_map('intval', $_POST['delete_image'] ?? []);
        foreach ($delete_ids as $img_id) {
            $ps = mysqli_prepare($con, "SELECT image_path FROM ad_images WHERE id = ? AND ad_id = ?");
            mysqli_stmt_bind_param($ps, 'ii', $img_id, $ad_id);
            mysqli_stmt_execute($ps);
            $prow = mysqli_stmt_get_result($ps)->fetch_assoc();
            mysqli_stmt_close($ps);
            if ($prow) {
                $real = realpath($prow['image_path']); $base = realpath('images');
                if ($real && $base && strpos($real, $base) === 0) @unlink($real);
                $ds = mysqli_prepare($con, "DELETE FROM ad_images WHERE id = ? AND ad_id = ?");
                mysqli_stmt_bind_param($ds, 'ii', $img_id, $ad_id);
                mysqli_stmt_execute($ds);
                mysqli_stmt_close($ds);
            }
        }

        if (!is_dir('images')) mkdir('images', 0755, true);
        foreach ($new_valid_images as $img) {
            $new_name = bin2hex(random_bytes(16)) . '.' . $img['ext'];
            $upload_path = 'images/' . $new_name;
            if (move_uploaded_file($img['tmp_name'], $upload_path)) {
                $is = mysqli_prepare($con, "INSERT INTO ad_images (ad_id, image_path) VALUES (?, ?)");
                mysqli_stmt_bind_param($is, 'is', $ad_id, $upload_path);
                mysqli_stmt_execute($is);
                mysqli_stmt_close($is);
            }
        }

        $stmt = mysqli_prepare($con, "SELECT * FROM ads WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $ad_id, $user_id);
        mysqli_stmt_execute($stmt);
        $ad = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);

        $img_stmt2 = mysqli_prepare($con, "SELECT id, image_path FROM ad_images WHERE ad_id = ?");
        mysqli_stmt_bind_param($img_stmt2, 'i', $ad_id);
        mysqli_stmt_execute($img_stmt2);
        $existing_images = mysqli_stmt_get_result($img_stmt2)->fetch_all(MYSQLI_ASSOC);
        mysqli_stmt_close($img_stmt2);

        $success_msg = 'تم تحديث الإعلان بنجاح! ✅';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الإعلان | سوقك</title>
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
            display: flex; align-items: center; justify-content: space-between;
        }
        .logo { font-size: 22px; font-weight: 800; color: #fff; text-decoration: none; }
        .logo span { color: var(--amber); }
        .back-btn {
            color: rgba(255,255,255,.8); text-decoration: none; font-size: 14px;
            font-weight: 500; border: 1px solid rgba(255,255,255,.25);
            padding: 7px 16px; border-radius: 100px;
        }

        .page { max-width: 700px; margin: 40px auto; padding: 0 20px 40px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 24px; font-weight: 800; }
        .page-header p { font-size: 14px; color: var(--muted); margin-top: 4px; }

        .card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 4px 24px rgba(26,26,46,.07);
            border: 1px solid var(--border);
            padding: 28px;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 13px; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: .5px;
            margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border);
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 18px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
        input[type="text"], input[type="number"], select, textarea {
            width: 100%; padding: 12px 16px;
            border: 1.5px solid var(--border); border-radius: 10px;
            font-family: 'Tajawal', sans-serif; font-size: 15px; color: var(--ink);
            outline: none; transition: border-color .2s; background: #fff;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--amber); }
        textarea { resize: vertical; }

        /* EXISTING IMAGES */
        .img-grid { display: flex; flex-wrap: wrap; gap: 12px; }
        .img-item { position: relative; }
        .img-item img { width: 90px; height: 90px; object-fit: cover; border-radius: 10px; border: 2px solid var(--border); display: block; transition: opacity .2s; }
        .img-item input[type=checkbox] { position: absolute; opacity: 0; }
        .del-label {
            position: absolute; top: 5px; right: 5px;
            background: var(--clay); color: #fff;
            font-size: 11px; font-weight: 700;
            padding: 2px 8px; border-radius: 6px;
            cursor: pointer; user-select: none;
        }
        .img-item input:checked ~ img { opacity: .3; border-color: var(--clay); }

        /* UPLOAD */
        .upload-zone {
            border: 2px dashed var(--border); border-radius: 12px;
            padding: 28px 20px; text-align: center; position: relative; cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .upload-zone:hover { border-color: var(--amber); background: #fffbf3; }
        .upload-zone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-zone p { font-size: 14px; color: var(--muted); }
        .upload-zone .hint-small { font-size: 12px; margin-top: 6px; color: #bbb; }

        .btn-submit {
            width: 100%; padding: 15px;
            background: var(--amber); color: var(--ink);
            border: none; border-radius: 10px;
            font-family: 'Tajawal', sans-serif; font-size: 17px; font-weight: 800;
            cursor: pointer; transition: background .2s;
        }
        .btn-submit:hover { background: #c8891a; }

        .success-box {
            background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 10px;
            padding: 14px 18px; color: #15803d; font-size: 15px; font-weight: 600;
            margin-bottom: 20px; text-align: center;
        }
        .error-box {
            background: #fff5f5; border: 1.5px solid #fca5a5; border-radius: 10px;
            padding: 14px 18px; color: #b91c1c; font-size: 14px; margin-bottom: 20px;
        }
        .error-box ul { padding-right: 18px; margin: 0; }
        .error-box li { margin: 4px 0; }

        @media (max-width: 500px) {
            .form-row { grid-template-columns: 1fr; }
            .card { padding: 18px; }
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
        <h1>تعديل الإعلان</h1>
        <p>عدّل بيانات إعلانك ثم احفظ التغييرات</p>
    </div>

    <?php if ($success_msg): ?>
        <div class="success-box"><?= e($success_msg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="error-box"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <div class="card">
            <div class="card-title">معلومات المنتج</div>

            <div class="form-group">
                <label>عنوان الإعلان</label>
                <input type="text" name="title" required value="<?= e($ad['title']) ?>">
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
                            <option value="<?= (int)$cat['id'] ?>" <?= ($ad['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>حالة المنتج</label>
                    <select name="condition" required>
                        <option value="جديد"   <?= ($ad['item_condition'] === 'جديد')   ? 'selected' : '' ?>>جديد</option>
                        <option value="مستعمل" <?= ($ad['item_condition'] === 'مستعمل') ? 'selected' : '' ?>>مستعمل</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>السعر (دينار)</label>
                <input type="number" name="price" required min="0" step="0.01" value="<?= e($ad['price']) ?>">
            </div>

            <div class="form-group">
                <label>وصف المنتج</label>
                <textarea name="description" rows="5" required><?= e($ad['description']) ?></textarea>
            </div>
        </div>

        <?php if (!empty($existing_images)): ?>
        <div class="card">
            <div class="card-title">الصور الحالية — ضع علامة لحذف الصورة</div>
            <div class="img-grid">
                <?php foreach ($existing_images as $img): ?>
                    <div class="img-item">
                        <input type="checkbox" name="delete_image[]"
                               id="del_<?= (int)$img['id'] ?>"
                               value="<?= (int)$img['id'] ?>">
                        <label class="del-label" for="del_<?= (int)$img['id'] ?>">✕ حذف</label>
                        <img src="<?= e($img['image_path']) ?>" alt="صورة">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-title">إضافة صور جديدة (اختياري)</div>
            <div class="upload-zone">
                <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/gif">
                <p>🖼️ اسحب الصور أو انقر للاختيار</p>
                <p class="hint-small">JPG، PNG، WebP، GIF — حتى 5MB لكل صورة</p>
            </div>
        </div>

        <button type="submit" name="update_ad" class="btn-submit">💾 حفظ التغييرات</button>
    </form>
</div>

</body>
</html>
