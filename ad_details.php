<?php
session_start();
include('config.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('location: index.php');
    exit();
}

$ad_id = (int)$_GET['id'];

$stmt = mysqli_prepare($con,
    "SELECT ads.*, categories.name AS category_name, users.name AS seller_name, users.phone, users.location
     FROM ads
     JOIN categories ON ads.category_id = categories.id
     JOIN users ON ads.user_id = users.id
     WHERE ads.id = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $ad_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("<h2 style='text-align:center;margin-top:50px;font-family:Tajawal,sans-serif;'>عذراً، هذا الإعلان غير موجود أو تم حذفه.</h2>");
}

$ad = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$img_stmt = mysqli_prepare($con, "SELECT image_path FROM ad_images WHERE ad_id = ?");
mysqli_stmt_bind_param($img_stmt, 'i', $ad_id);
mysqli_stmt_execute($img_stmt);
$img_result = mysqli_stmt_get_result($img_stmt);
$images = [];
while ($img = mysqli_fetch_assoc($img_result)) $images[] = $img['image_path'];
mysqli_stmt_close($img_stmt);

$main_image = !empty($images) ? e($images[0]) : 'images/default-placeholder.png';

$raw_phone = preg_replace('/\D/', '', $ad['phone']);
if (strlen($raw_phone) <= 10 && substr($raw_phone, 0, 2) === '07') {
    $whatsapp_number = '962' . substr($raw_phone, 1);
} else {
    $whatsapp_number = $raw_phone;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($ad['title']) ?> | سوقك</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1a1a2e; --amber: #e8a838; --clay: #c75c3a;
            --sand: #fdf6ee; --border: #ede8e0; --muted: #7a7a8c;
            --card-bg: #fff; --radius: 14px;
            --shadow: 0 4px 24px rgba(26,26,46,.07);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Tajawal', sans-serif; background: var(--sand); color: var(--ink); }

        /* NAV */
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
            color: rgba(255,255,255,.8);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,.25);
            padding: 7px 16px;
            border-radius: 100px;
            transition: all .2s;
        }
        .back-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

        /* LAYOUT */
        .page { max-width: 1100px; margin: 36px auto; padding: 0 20px; display: flex; gap: 28px; align-items: flex-start; flex-wrap: wrap; }

        /* GALLERY */
        .gallery { flex: 1.1; min-width: 300px; background: var(--card-bg); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; border: 1px solid var(--border); }
        .main-img-wrap { background: #f5f0ea; height: 420px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .main-img-wrap img { width: 100%; height: 100%; object-fit: contain; transition: transform .4s; }
        .thumbs { display: flex; gap: 10px; padding: 14px; overflow-x: auto; }
        .thumbs img {
            width: 72px; height: 72px; object-fit: cover;
            border-radius: 8px; cursor: pointer; flex-shrink: 0;
            border: 2.5px solid transparent; transition: border-color .2s, transform .2s;
        }
        .thumbs img:hover, .thumbs img.active { border-color: var(--amber); transform: scale(1.05); }

        /* INFO */
        .info { flex: 1; min-width: 280px; display: flex; flex-direction: column; gap: 18px; }

        .info-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            padding: 24px;
        }

        .ad-title { font-size: 22px; font-weight: 800; color: var(--ink); margin-bottom: 10px; }
        .ad-price { font-size: 32px; font-weight: 800; color: var(--clay); margin-bottom: 18px; }
        .ad-price span { font-size: 16px; color: var(--muted); font-weight: 400; }

        .tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
        .tag {
            font-size: 13px;
            padding: 5px 14px;
            border-radius: 100px;
            font-weight: 500;
        }
        .tag.cat  { background: #eef2ff; color: #4338ca; }
        .tag.cond { background: #fef9c3; color: #854d0e; }
        .tag.loc  { background: #f0fdf4; color: #15803d; }
        .tag.date { background: #f1f5f9; color: #475569; }

        .divider { border: none; border-top: 1px solid var(--border); margin: 0; }

        .section-label { font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; }
        .description { font-size: 15px; line-height: 1.9; color: #444; white-space: pre-wrap; word-break: break-word; }

        /* CONTACT */
        .contact-card {
            background: linear-gradient(135deg, #1a1a2e, #0f3460);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
        }
        .contact-card h3 { color: #fff; font-size: 16px; font-weight: 700; margin-bottom: 16px; }
        .seller-name { color: var(--amber); font-weight: 800; }

        .btn-contact {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            padding: 13px;
            border-radius: 10px;
            font-family: 'Tajawal', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            transition: opacity .2s, transform .15s;
            margin-bottom: 10px;
        }
        .btn-contact:hover { opacity: .9; transform: translateY(-1px); }
        .btn-contact:last-child { margin-bottom: 0; }
        .btn-call { background: rgba(255,255,255,.15); border: 1.5px solid rgba(255,255,255,.3); }
        .btn-whatsapp { background: #25D366; }

        @media (max-width: 640px) {
            .page { flex-direction: column; }
            .main-img-wrap { height: 260px; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <a class="logo" href="index.php">سو<span>قك</span></a>
    <a class="back-btn" href="index.php">← العودة للرئيسية</a>
</div>

<div class="page">
    <!-- GALLERY -->
    <div class="gallery">
        <div class="main-img-wrap">
            <img id="mainImage" src="<?= $main_image ?>" alt="صورة الإعلان">
        </div>
        <?php if (count($images) > 1): ?>
            <div class="thumbs">
                <?php foreach ($images as $idx => $img): ?>
                    <img src="<?= e($img) ?>" class="<?= $idx === 0 ? 'active' : '' ?>"
                         onclick="setMain(this)" alt="صورة <?= $idx + 1 ?>">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- INFO -->
    <div class="info">
        <div class="info-card">
            <div class="ad-title"><?= e($ad['title']) ?></div>
            <div class="ad-price"><?= e($ad['price']) ?> <span>دينار أردني</span></div>

            <div class="tags">
                <span class="tag cat">📂 <?= e($ad['category_name']) ?></span>
                <span class="tag cond">🔖 <?= e($ad['item_condition']) ?></span>
                <span class="tag loc">📍 <?= e($ad['location']) ?></span>
                <span class="tag date">🕒 <?= e(date('Y/m/d', strtotime($ad['created_at']))) ?></span>
            </div>

            <div class="divider"></div>
            <div style="padding-top:16px;">
                <div class="section-label">تفاصيل الإعلان</div>
                <div class="description"><?= e($ad['description']) ?></div>
            </div>
        </div>

        <!-- CONTACT -->
        <div class="contact-card">
            <h3>التواصل مع البائع<br><span class="seller-name"><?= e($ad['seller_name']) ?></span></h3>
            <a href="tel:<?= e($ad['phone']) ?>" class="btn-contact btn-call">
                📞 اتصال: <?= e($ad['phone']) ?>
            </a>
            <a href="https://wa.me/<?= e($whatsapp_number) ?>?text=<?= urlencode('مرحباً، أنا مهتم بإعلانك (' . $ad['title'] . ') على سوقك.') ?>"
               target="_blank" rel="noopener noreferrer" class="btn-contact btn-whatsapp">
                💬 تواصل عبر واتساب
            </a>
        </div>
    </div>
</div>

<script>
function setMain(el) {
    document.getElementById('mainImage').src = el.src;
    document.querySelectorAll('.thumbs img').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
}
</script>
</body>
</html>
