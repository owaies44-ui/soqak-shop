<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($con,
    "SELECT ads.*, categories.name AS category_name,
     (SELECT image_path FROM ad_images WHERE ad_id = ads.id LIMIT 1) AS main_image
     FROM ads
     JOIN categories ON ads.category_id = categories.id
     WHERE ads.user_id = ?
     ORDER BY ads.created_at DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count  = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعلاناتي | سوقك</title>
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

        /* TOPBAR */
        .topbar {
            background: linear-gradient(135deg, #1a1a2e, #0f3460);
            padding: 14px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .logo { font-size: 22px; font-weight: 800; color: #fff; text-decoration: none; }
        .logo span { color: var(--amber); }
        .topbar-nav { display: flex; gap: 10px; flex-wrap: wrap; }
        .topbar-nav a {
            color: rgba(255,255,255,.8); text-decoration: none; font-size: 14px;
            font-weight: 500; border: 1px solid rgba(255,255,255,.2);
            padding: 7px 16px; border-radius: 100px; transition: all .2s;
        }
        .topbar-nav a:hover { background: rgba(255,255,255,.12); color: #fff; }
        .topbar-nav a.primary { background: var(--amber); border-color: var(--amber); color: var(--ink); font-weight: 700; }
        .topbar-nav a.primary:hover { background: #c8891a; }

        /* PAGE */
        .page { max-width: 1180px; margin: 40px auto; padding: 0 20px; }

        /* HEADER */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .page-header h1 { font-size: 24px; font-weight: 800; }
        .page-header h1 span { color: var(--clay); }
        .count-badge {
            background: var(--ink);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 100px;
        }

        /* GRID */
        .ads-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 22px; }

        /* CARD */
        .ad-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform .28s, box-shadow .28s;
        }
        .ad-card:hover { transform: translateY(-5px); box-shadow: 0 12px 40px rgba(26,26,46,.12); }

        .card-img { width: 100%; height: 190px; object-fit: cover; background: #f5f0ea; }
        .card-body { padding: 16px; flex: 1; display: flex; flex-direction: column; }
        .card-title { font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-price { font-size: 19px; font-weight: 800; color: var(--clay); margin-bottom: 8px; }
        .card-price small { font-size: 12px; color: var(--muted); font-weight: 400; }
        .card-cat { font-size: 12px; color: var(--muted); margin-bottom: 16px; }

        .card-actions { display: flex; gap: 8px; margin-top: auto; }
        .btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 9px 6px;
            border-radius: 8px;
            text-decoration: none;
            font-family: 'Tajawal', sans-serif;
            font-size: 13px;
            font-weight: 600;
            transition: opacity .2s, transform .15s;
            border: none;
            cursor: pointer;
            color: #fff;
        }
        .btn:hover { opacity: .88; transform: translateY(-1px); }
        .btn-view   { background: var(--ink); }
        .btn-edit   { background: var(--amber); color: var(--ink); }
        .btn-delete { background: var(--clay); }

        /* EMPTY STATE */
        .empty {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 20px;
            color: var(--muted);
        }
        .empty .icon { font-size: 60px; margin-bottom: 16px; }
        .empty h3 { font-size: 20px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
        .empty a {
            display: inline-block;
            margin-top: 18px;
            padding: 12px 28px;
            background: var(--ink);
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 500px) {
            .ads-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <a class="logo" href="index.php">سو<span>قك</span></a>
    <nav class="topbar-nav">
        <a href="add_ad.php" class="primary">+ إعلان جديد</a>
        <a href="index.php">الرئيسية</a>
    </nav>
</div>

<div class="page">
    <div class="page-header">
        <h1>إعلاناتي <span><?= e($_SESSION['user_name'] ?? '') ?></span></h1>
        <span class="count-badge"><?= $count ?> إعلان</span>
    </div>

    <div class="ads-grid">
        <?php if ($count > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php $image = !empty($row['main_image']) ? e($row['main_image']) : 'images/default-placeholder.png'; ?>
                <div class="ad-card">
                    <img class="card-img" src="<?= $image ?>" alt="<?= e($row['title']) ?>" loading="lazy">
                    <div class="card-body">
                        <div class="card-title"><?= e($row['title']) ?></div>
                        <div class="card-price"><?= e($row['price']) ?> <small>دينار</small></div>
                        <div class="card-cat">📂 <?= e($row['category_name']) ?></div>
                        <div class="card-actions">
                            <a href="ad_details.php?id=<?= (int)$row['id'] ?>" class="btn btn-view">👁 عرض</a>
                            <a href="edit_ad.php?id=<?= (int)$row['id'] ?>" class="btn btn-edit">✏ تعديل</a>
                            <a href="delete.php?id=<?= (int)$row['id'] ?>" class="btn btn-delete"
                               onclick="return confirm('هل أنت متأكد من حذف هذا الإعلان نهائياً؟');">🗑 حذف</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty">
                <div class="icon">📭</div>
                <h3>لم تنشر أي إعلانات بعد</h3>
                <p>ابدأ الآن وأضف أول إعلان لك مجاناً</p>
                <a href="add_ad.php">+ أضف إعلاناً</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
