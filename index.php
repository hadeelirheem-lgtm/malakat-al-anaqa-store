<?php
session_start();
require_once 'DB.php';

$current_theme = $_COOKIE['theme'] ?? 'light';
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$cart_ids = array_keys($_SESSION['cart'] ?? []);
$wishlist_ids = array_keys($_SESSION['wishlist'] ?? []);
$user_role = $_SESSION['role'] ?? 'user';

// جلب أحدث 4 منتجات غير مؤرشفة فقط لعرضها في الصفحة الرئيسية
$stmt = $pdo->query("SELECT * FROM products WHERE is_archived = 0 ORDER BY id DESC LIMIT 4");
$latest_products = $stmt->fetchAll();

// أكواد الخصم المرتبطة بمنتجات محددة لعرضها على البطاقات
$coupon_map = [];
$stmt_c = $pdo->query("SELECT code, discount_percent, product_code FROM coupons WHERE is_active = 1 AND product_code IS NOT NULL AND product_code != ''");
foreach ($stmt_c->fetchAll() as $c) {
    $coupon_map[$c['product_code']] = $c;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملكة الأناقة - المتجر الإلكتروني الأرقى</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preload" as="image" href="https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1600&q=80">
    
    <style>
        :root { --sidebar-width: 260px; }
        body {
            padding-right: var(--sidebar-width);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fffaf7;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* القائمة الجانبية */
        .sidebar-nav {
            position: fixed; top: 0; right: 0; width: var(--sidebar-width); height: 100vh;
            background: linear-gradient(180deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            color: #ffffff; z-index: 1000; display: flex; flex-direction: column;
            justify-content: space-between; padding: 25px 15px;
            box-shadow: -5px 0 25px rgba(234, 88, 12, 0.15);
        }
        .sidebar-brand { color: #fff; font-size: 1.4rem; font-weight: bold; text-decoration: none; padding: 12px; background: rgba(255,255,255,0.15); border-radius: 12px; display: flex; align-items: center; gap: 10px; }
        .sidebar-menu { list-style: none; padding: 0; margin-top: 30px; display: flex; flex-direction: column; gap: 8px; }
        .sidebar-link { color: rgba(255, 255, 255, 0.9); text-decoration: none; padding: 12px 18px; border-radius: 10px; display: flex; align-items: center; gap: 12px; font-weight: 600; transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: #ffffff; color: #ea580c; transform: translateX(-5px); }

        /* Hero Banner */
        .hero-banner {
            border-radius: 30px;
            margin-top: 30px;
            position: relative;
            overflow: hidden;
            min-height: 480px;
            display: flex;
            align-items: center;
            border: 1px solid #ffedd5;
            background: #1f1a15;
        }
        .hero-slide {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-color: #1f1a15;
            opacity: 0;
            transition: opacity 0.9s ease-in-out;
        }
        .hero-slide.active {
            opacity: 1;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background: rgba(0,0,0,0.55);
        }
        .hero-overlay::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.6) 80%);
        }
        .hero-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
            padding: 70px 40px;
            text-align: center;
            text-shadow: 0 2px 15px rgba(0,0,0,0.5);
        }
        .hero-title { font-size: 2.8rem; font-weight: 800; color: #d05208; line-height: 1.3; }
        .hero-subtitle { font-size: 1.15rem; color: #ffe8d6; margin-top: 15px; margin-bottom: 30px; line-height: 1.8; }
        .hero-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            display: flex;
            gap: 10px;
        }
        .hero-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            border: 2px solid rgba(255,255,255,0.6);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .hero-dot.active {
            background: #f97316;
            border-color: #ffffff;
            transform: scale(1.2);
        }
        .btn-orange-hero {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #ffffff; font-weight: bold; border-radius: 14px;
            padding: 14px 32px; border: none; font-size: 1.1rem;
            box-shadow: 0 10px 20px rgba(234, 88, 12, 0.3);
            transition: all 0.3s ease;
        }
        .btn-orange-hero:hover { transform: translateY(-3px); color: #fff; box-shadow: 0 15px 30px rgba(234, 88, 12, 0.45); }

        /* كروت الميزات */
        .feature-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #f3f4f6;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(234, 88, 12, 0.08); }
        .feature-icon {
            width: 60px; height: 60px; border-radius: 16px;
            background: #fff7ed; color: #ea580c;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 15px;
        }

        /* كروت المنتجات */
        .product-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #f3f4f6;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
        .product-img { height: 260px; object-fit: cover; width: 100%; }

        @media (max-width: 991px) {
            body { padding-right: 0; }
            .sidebar-nav { position: relative; width: 100%; height: auto; }
            .hero-banner { min-height: 380px; }
            .hero-title { font-size: 1.9rem; }
            .hero-content { padding: 50px 25px; }
            .hero-subtitle { font-size: 1rem; }
        }
        .marquee-wrapper {
            background: linear-gradient(90deg, #ea580c 0%, #f97316 50%, #ea580c 100%);
            color: #ffffff;
            overflow: hidden;
            padding: 10px 0;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(234, 88, 12, 0.25);
            position: relative;
            z-index: 999;
            width: 100%;
        }

        .marquee-track {
            display: flex;
            width: max-content;
            animation: marquee-infinite 25s linear infinite;
        }

        .marquee-track:hover {
            animation-play-state: paused;
        }

        .marquee-content {
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .coupon-code-highlight {
            background-color: #ffffff;
            color: #ea580c;
            padding: 2px 10px;
            border-radius: 8px;
            font-size: 1.05rem;
            margin: 0 4px;
        }

        @keyframes marquee-infinite {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }
    </style>
</head>
<body>
<?php
// جلب آخر كود خصم مفعل لعرضه في شريط الإعلانات
$stmt_announcement = $pdo->query("SELECT code, discount_percent FROM coupons WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$latest_coupon = $stmt_announcement->fetch();
?>

<!-- شريط الإعلان العاجل للكوبون -->
<?php if ($latest_coupon): ?>
    <div class="marquee-wrapper">
        <div class="marquee-track">
            <div class="marquee-content">
                <span><i class="fa-solid fa-bullhorn text-warning me-2"></i> بشرى سارة لزبائن متجر ملكة الأناقة! تم توفير كود خصم جديد: <strong class="coupon-code-highlight"><?php echo htmlspecialchars($latest_coupon['code']); ?></strong> بنسبة خصم <strong>%<?php echo $latest_coupon['discount_percent']; ?></strong>! استمتعوا بالتسوق الآن.</span>
                <span class="mx-4">•</span>
            </div>
            <div class="marquee-content" aria-hidden="true">
                <span><i class="fa-solid fa-bullhorn text-warning me-2"></i> بشرى سارة لزبائن متجر ملكة الأناقة! تم توفير كود خصم جديد: <strong class="coupon-code-highlight"><?php echo htmlspecialchars($latest_coupon['code']); ?></strong> بنسبة خصم <strong>%<?php echo $latest_coupon['discount_percent']; ?></strong>! استمتعوا بالتسوق الآن.</span>
                <span class="mx-4">•</span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- القائمة الجانبية -->
<?php $active_page = 'index'; include 'sidebar.php'; ?>

<!-- المحتوى الرئيسي -->
<div class="container pb-5">

    <!-- Hero Banner مع خلفية سلايدر متحركة -->
    <div class="hero-banner" id="heroSlider">
        <div class="hero-slide active" style="background-image:url('https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1600&q=80');"></div>
        <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1445205170230-053b83016050?w=1600&q=80');"></div>
        <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1537832816519-689ad163238b?w=1600&q=80');"></div>
        <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1600&q=80');"></div>
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <!-- <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
                <i class="fa-solid fa-sparkles me-1"></i> تشكيلة الموسم الجديدة
            </span> -->
            <h1 class="hero-title">تألَّقي بإطلالة ساحرة تليقُ بملكة!</h1>
            <p class="hero-subtitle">اكتشفي أرقى التصاميم وأحدث صيحات الموضة المصممة خصيصاً لتبرز جمالكِ وأناقتكِ في كل المناسبات.</p>
            <a href="products.php" class="btn btn-orange-hero text-decoration-none">
                تسوقي الآن <i class="fa-solid fa-arrow-left ms-2"></i>
            </a>
        </div>

        <div class="hero-dots" id="heroDots"></div>
    </div>

    <!-- قسم الميزات -->
    <div class="row g-4 my-4">
        <div class="col-md-4">
            <div class="feature-card text-center">
                <div class="feature-icon mx-auto">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <h5 class="fw-bold mb-2">توصيل سريع</h5>
                <p class="text-muted small mb-0">نصلكِ أينما كنتِ بأسرع وقت وبدقة متناهية في المواعيد.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card text-center">
                <div class="feature-icon mx-auto">
                    <i class="fa-solid fa-gem"></i>
                </div>
                <h5 class="fw-bold mb-2">جودة عالية</h5>
                <p class="text-muted small mb-0">أرقى خامات الأقمشة والتصاميم المتقنة بعناية فائقة.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card text-center">
                <div class="feature-icon mx-auto">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h5 class="fw-bold mb-2">دعم متميز</h5>
                <p class="text-muted small mb-0">فريقنا متواجد دائماً للإجابة عن استفساراتكِ ومساعدتكِ.</p>
            </div>
        </div>
    </div>

    <!-- قسم أحدث المنتجات -->
    <div class="my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color: #ea580c;">وصل حديثاً</h3>
                <p class="text-muted small mb-0">استكشفي أحدث القطع المضافة متجركِ</p>
            </div>
            <a href="products.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold">عرض الكل</a>
        </div>

        <div class="row g-4">
            <?php foreach ($latest_products as $product): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card h-100 d-flex flex-column position-relative">
                        <?php $in_cart = in_array($product['id'], $cart_ids); ?>
                        <?php $in_wishlist = in_array($product['id'], $wishlist_ids); ?>
                        <a href="add_to_wishlist.php?id=<?php echo $product['id']; ?>" 
                           class="d-flex align-items-center justify-content-center" title="أضف للمفضلة"
                           style="position:absolute; top:10px; left:10px; z-index:5; width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,0.9); box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                            <i class="fa-solid fa-heart" style="color:<?php echo $in_wishlist ? '#e11d48' : '#9ca3af'; ?>; font-size:1.1rem;"></i>
                        </a>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" class="product-img" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <span class="badge bg-light text-secondary w-auto align-self-start mb-2">مقاس: <?php echo htmlspecialchars($product['size']); ?></span>
                            <?php if (isset($coupon_map[$product['product_code'] ?? ''])): $cp = $coupon_map[$product['product_code']]; ?>
                                <span class="badge bg-warning text-dark w-auto align-self-start mb-2" title="كود خصم خاص بهذه القطعة"><i class="fa-solid fa-ticket me-1"></i>خصم <?php echo $cp['discount_percent']; ?>% بكود <?php echo htmlspecialchars($cp['code']); ?></span>
                            <?php endif; ?>
                            <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($product['title']); ?></h6>
                            <small class="text-muted d-block mb-2">الكود: <?php echo htmlspecialchars($product['product_code'] ?? '—'); ?></small>
                            <div class="mt-auto d-flex justify-content-between align-items-center pt-2">
                                <span class="fw-bold text-danger fs-5">$<?php echo number_format($product['price'], 2); ?></span>
                                <a href="products.php" class="btn btn-sm btn-warning rounded-circle text-white shadow-sm" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($latest_products)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">لا توجد منتجات معروضة حالياً.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var slider = document.getElementById('heroSlider');
        var slides = slider.querySelectorAll('.hero-slide');
        var dotsContainer = document.getElementById('heroDots');
        if (slides.length < 2) return;

        var current = 0;
        var interval = null;

        // إنشاء النقاط
        slides.forEach(function (_, i) {
            var dot = document.createElement('span');
            dot.className = 'hero-dot' + (i === 0 ? ' active' : '');
            dot.addEventListener('click', function () { goTo(i); });
            dotsContainer.appendChild(dot);
        });
        var dots = dotsContainer.querySelectorAll('.hero-dot');

        function goTo(index) {
            slides[current].classList.remove('active');
            dots[current].classList.remove('active');
            current = index;
            slides[current].classList.add('active');
            dots[current].classList.add('active');
        }

        function next() {
            goTo((current + 1) % slides.length);
        }

        function startAuto() {
            interval = setInterval(next, 2500);
        }

        startAuto();

        // إيقاف مؤقت عند التمرير فوق السلايدر
        slider.addEventListener('mouseenter', function () { clearInterval(interval); });
        slider.addEventListener('mouseleave', startAuto);
    })();
</script>
</body>
</html>