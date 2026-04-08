<?php
session_start();
include('config.php');

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('location: login.php');
    exit();
}

$params = [];
$types = '';
$where = '';

if (!empty($_GET['search'])) {
    $keyword = '%' . trim($_GET['search']) . '%';
    $where = " WHERE ads.title LIKE ? OR ads.description LIKE ?";
    $types = 'ss';
    $params = [$keyword, $keyword];
}

$sql = "SELECT ads.*, categories.name AS category_name, users.location,
        (SELECT image_path FROM ad_images WHERE ad_id = ads.id LIMIT 1) AS main_image
        FROM ads
        JOIN categories ON ads.category_id = categories.id
        JOIN users ON ads.user_id = users.id
        $where
        ORDER BY ads.created_at DESC";

$stmt = mysqli_prepare($con, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سوقك | منصة الإعلانات المجانية</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --ink: #1a1a2e;
            --amber: #e8a838;
            --amber-dk: #c8891a;
            --clay: #c75c3a;
            --sand: #fdf6ee;
            --card-bg: #ffffff;
            --muted: #7a7a8c;
            --border: #ede8e0;
            --radius: 14px;
            --shadow: 0 4px 24px rgba(26, 26, 46, .07);
            --shadow-h: 0 12px 40px rgba(26, 26, 46, .13);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--sand);
            color: var(--ink);
            min-height: 100vh;
        }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            padding: 0 20px 50px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -80px;
            left: -80px;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(232, 168, 56, .18) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -100px;
            right: -60px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(199, 92, 58, .14) 0%, transparent 70%);
            pointer-events: none;
        }

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0 10px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            margin-bottom: 40px;
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.5px;
        }

        .logo span {
            color: var(--amber);
        }

        .nav-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: rgba(255, 255, 255, .85);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 18px;
            border-radius: 100px;
            border: 1px solid rgba(255, 255, 255, .2);
            transition: all .25s;
        }

        .nav-links a:hover,
        .nav-links a.primary {
            background: var(--amber);
            border-color: var(--amber);
            color: var(--ink);
        }

        /* ── HERO TEXT & SEARCH ── */
        .hero-body {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero-body h1 {
            color: #fff;
            font-size: clamp(26px, 4vw, 44px);
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 10px;
        }

        .hero-body h1 span {
            color: var(--amber);
        }

        .hero-body p {
            color: rgba(255, 255, 255, .65);
            font-size: 16px;
            margin-bottom: 32px;
        }

        .search-bar {
            display: flex;
            gap: 0;
            max-width: 600px;
            margin: 0 auto;
            background: black;
            border-radius: 100px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .25);
        }

        .search-bar input {
            flex: 1;
            border: none;
            outline: none;
            padding: 16px 24px;
            font-family: 'Tajawal', sans-serif;
            font-size: 15px;
            color: var(--ink);
            background: transparent;
        }

        .search-bar input::placeholder {
            color: #aaa;
        }

        .search-bar button {
            background: var(--amber);
            border: none;
            padding: 0 28px;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            transition: background .2s;
            white-space: nowrap;
        }

        .search-bar button:hover {
            background: var(--amber-dk);
        }

        /* ── STATS BAR ── */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 36px;
            flex-wrap: wrap;
        }

        .stat {
            text-align: center;
        }

        .stat-num {
            font-size: 22px;
            font-weight: 800;
            color: var(--amber);
        }

        .stat-label {
            font-size: 12px;
            color: rgba(255, 255, 255, .55);
            margin-top: 2px;
        }

        /* ── SECTION ── */
        .section {
            max-width: 1280px;
            margin: 48px auto;
            padding: 0 20px;
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .section-head h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
        }

        .section-head h2::after {
            content: '';
            display: block;
            width: 40px;
            height: 3px;
            background: var(--amber);
            border-radius: 2px;
            margin-top: 6px;
        }

        .results-count {
            font-size: 13px;
            color: var(--muted);
            background: var(--border);
            padding: 4px 14px;
            border-radius: 100px;
        }

        /* ── GRID ── */
        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 22px;
        }

        /* ── CARD ── */
        .ad-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform .28s ease, box-shadow .28s ease;
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        .ad-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-h);
        }

        .card-img-wrap {
            position: relative;
            overflow: hidden;
            height: 196px;
            background: #f0ebe3;
        }

        .card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s ease;
        }

        .ad-card:hover .card-img-wrap img {
            transform: scale(1.05);
        }

        .badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .badge.new {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge.used {
            background: #fff3e0;
            color: #e65100;
        }

        .card-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-price {
            font-size: 20px;
            font-weight: 800;
            color: var(--clay);
            margin-bottom: 12px;
        }

        .card-price span {
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
        }

        .card-meta {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .card-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
            background: var(--sand);
            padding: 3px 10px;
            border-radius: 100px;
        }

        .view-btn {
            display: block;
            text-align: center;
            background: var(--ink);
            color: #fff;
            text-decoration: none;
            padding: 11px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            transition: background .2s;
            margin-top: auto;
        }

        .view-btn:hover {
            background: #0f3460;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 20px;
            color: var(--muted);
        }

        .empty-state .icon {
            font-size: 56px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--ink);
        }

        /* ── FOOTER ── */
        .footer {
            text-align: center;
            padding: 30px 20px;
            color: var(--muted);
            font-size: 13px;
            border-top: 1px solid var(--border);
            margin-top: 40px;
        }

        @media (max-width: 640px) {
            .topbar {
                flex-direction: column;
                gap: 16px;
            }

            .stats-bar {
                gap: 24px;
            }

            .ads-grid {
                grid-template-columns: 1fr 1fr;
                gap: 14px;
            }
        }

        @media (max-width: 420px) {
            .ads-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="hero">
        <div class="topbar">
            <div class="logo">سو<span>قك</span></div>
            <nav class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="add_ad.php" class="primary">+ أضف إعلاناً</a>
                    <a href="products.php">إعلاناتي</a>
                    <a href="index.php?logout=1" onclick="return confirm('هل تريد تسجيل الخروج؟');">خروج</a>
                <?php else: ?>
                    <a href="login.php" class="primary">+ أضف إعلاناً</a>
                    <a href="login.php">دخول / تسجيل</a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="hero-body">
            <h1>اشترِ وبِع بكل سهولة<br><span>بدون عمولة</span></h1>
            <p>آلاف الإعلانات في انتظارك — ابحث عمّا تريد الآن</p>
            <form class="search-bar" action="" method="GET">
                <input type="text" name="search" placeholder="ابحث عن سيارة، جوال، لابتوب..."
                    value="<?= e($_GET['search'] ?? '') ?>">
                <button type="submit">بحث 🔍</button>
            </form>

            <div class="stats-bar">
                <div class="stat">
                    <div class="stat-num">مجاني</div>
                    <div class="stat-label">النشر دائماً مجاني</div>
                </div>
                <div class="stat">
                    <div class="stat-num">آمن</div>
                    <div class="stat-label">تواصل مباشر مع البائع</div>
                </div>
                <div class="stat">
                    <div class="stat-num">سريع</div>
                    <div class="stat-label">نشر الإعلان في ثوانٍ</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-head">
            <h2><?= !empty($_GET['search']) ? 'نتائج البحث' : 'أحدث الإعلانات' ?></h2>
            <?php $count = mysqli_num_rows($result); ?>
            <span class="results-count"><?= $count ?> إعلان</span>
        </div>

        <div class="ads-grid">
            <?php if ($count > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php $image = !empty($row['main_image']) ? e($row['main_image']) : 'images/default-placeholder.png'; ?>
                    <?php $is_new = ($row['item_condition'] === 'جديد'); ?>
                    <div class="ad-card">
                        <div class="card-img-wrap">
                            <img src="<?= $image ?>" alt="<?= e($row['title']) ?>" loading="lazy">
                            <span class="badge <?= $is_new ? 'new' : 'used' ?>"><?= e($row['item_condition']) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="card-title"><?= e($row['title']) ?></div>
                            <div class="card-price"><?= e($row['price']) ?> <span>دينار</span></div>
                            <div class="card-meta">
                                <span>📍 <?= e($row['location']) ?></span>
                                <span>📂 <?= e($row['category_name']) ?></span>
                            </div>
                            <a href="ad_details.php?id=<?= (int) $row['id'] ?>" class="view-btn">عرض التفاصيل</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">🔍</div>
                    <h3>لا توجد إعلانات مطابقة</h3>
                    <p>جرّب كلمات بحث مختلفة أو تصفّح كل الإعلانات</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        © <?= date('Y') ?> سوقك — منصة الإعلانات المجانية
    </div>

</body>

</html>