<?php
session_start();
require_once 'DB.php';

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$current_theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// التحقق من وجود معرف المنتج
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);

// جلب تفاصيل المنتج من القاعدة
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_archived = 0");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit();
}

$in_cart = isset($_SESSION['cart'][$product_id]);
$in_wishlist = isset($_SESSION['wishlist'][$product_id]);

// كوبون خصم خاص بهذه القطعة إن وُجد
$product_coupon = null;
$stmt_c = $pdo->prepare("SELECT code, discount_percent, product_code FROM coupons WHERE is_active = 1 AND product_code = ? LIMIT 1");
$stmt_c->execute([$product['product_code'] ?? '']);
$product_coupon = $stmt_c->fetch();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> | ملكة الأناقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root { --sidebar-width: 260px; }
        body {
            padding-right: var(--sidebar-width);
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #fffaf0, #ffedd5, #fef3c7, #fff5eb);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .bg-shape { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.35; animation: float 8s ease-in-out infinite alternate; z-index: 0; }
        .shape-1 { width: 320px; height: 320px; background: #fb923c; top: -40px; left: -40px; }
        .shape-2 { width: 280px; height: 280px; background: #ea580c; bottom: -40px; right: var(--sidebar-width); animation-delay: -4s; }

        @keyframes float { 0% { transform: translateY(0) scale(1); } 100% { transform: translateY(30px) scale(1.08); } }

        .sidebar-nav {
            position: fixed; top: 0; right: 0; width: var(--sidebar-width); height: 100vh;
            background: linear-gradient(180deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            color: #ffffff; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; padding: 25px 15px;
            box-shadow: -5px 0 25px rgba(234, 88, 12, 0.2);
        }
        .sidebar-brand { color: #ffffff; font-size: 1.4rem; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 12px 15px; background: rgba(255, 255, 255, 0.15); border-radius: 12px; backdrop-filter: blur(5px); }
        .sidebar-menu { list-style: none; padding: 0; margin: 30px 0 0 0; display: flex; flex-direction: column; gap: 8px; }
        .sidebar-link { color: rgba(255, 255, 255, 0.9); text-decoration: none; padding: 12px 18px; border-radius: 10px; display: flex; align-items: center; gap: 12px; font-weight: 600; transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: #ffffff; color: #ea580c; transform: translateX(-5px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .sidebar-footer { border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 15px; }

        .details-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: none;
            box-shadow: 0 15px 35px rgba(234, 88, 12, 0.1);
            overflow: hidden;
        }

        .product-main-img {
            width: 100%;
            max-height: 450px;
            object-fit: cover;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 12px 28px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(234, 88, 12, 0.25);
        }

        .btn-add-cart:hover {
            background: linear-gradient(135deg, #ea580c, #c2410c);
            color: #ffffff;
            transform: translateY(-2px);
        }

        @media (max-width: 991px) {
            body { padding-right: 0; }
            .sidebar-nav { position: relative; width: 100%; height: auto; }
            .shape-2 { right: 0; }
        }
    </style>
</head>
<body>

<div class="bg-shape shape-1"></div>
<div class="bg-shape shape-2"></div>

<!-- القائمة الجانبية -->
<?php $active_page = 'products'; include 'sidebar.php'; ?>

<!-- المحتوى الرئيسي -->
<div class="container my-5" style="position: relative; z-index: 2;">
    <div class="mb-4">
        <a href="products.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm">
            <i class="fa-solid fa-arrow-right me-2"></i> العودة للمنتجات
        </a>
    </div>

    <div class="card details-card p-4">
        <div class="row g-4 align-items-center">
            <div class="col-md-5">
                <img src="<?php echo htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/400'); ?>" class="product-main-img" alt="<?php echo htmlspecialchars($product['title']); ?>">
            </div>
            <div class="col-md-7">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
                    <?php echo htmlspecialchars($product['category'] ?? 'عام'); ?>
                </span>
                <h2 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($product['title']); ?></h2>
                <small class="text-muted d-inline-block mb-3"><i class="fa-solid fa-barcode me-1"></i> كود المنتج: <?php echo htmlspecialchars($product['product_code'] ?? '—'); ?></small>
                <h3 class="fw-bold text-danger mb-4">$<?php echo number_format($product['price'], 2); ?></h3>

                <?php if ($product_coupon): ?>
                    <div class="alert alert-warning py-2 px-3 border-0 shadow-sm rounded-3 mb-4">
                        <i class="fa-solid fa-ticket me-1"></i> خصم <strong><?php echo $product_coupon['discount_percent']; ?>%</strong> على هذه القطعة — استخدمي الكود <strong class="text-danger text-decoration-underline"><?php echo htmlspecialchars($product_coupon['code']); ?></strong> عند الدفع.
                    </div>
                <?php endif; ?>
                
                <p class="text-secondary mb-4 leading-relaxed fs-6">
                    <?php echo nl2br(htmlspecialchars($product['description'] ?? 'لا يوجد وصف متاح لهذا المنتج.')); ?>
                </p>

                <div class="d-flex gap-3 mt-4 align-items-center">
                    <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn btn-add-cart btn-lg d-flex align-items-center gap-2">
                        <i class="fa-solid fa-cart-plus"></i> إضافة إلى السلة
                    </a>
                    <a href="add_to_wishlist.php?id=<?php echo $product['id']; ?>"
                       class="btn d-flex align-items-center justify-content-center rounded-circle"
                       style="width:54px; height:54px; border:2px solid <?php echo $in_wishlist ? '#e11d48' : '#f97316'; ?>; background:<?php echo $in_wishlist ? '#fff1f2' : '#fff'; ?>;"
                       title="أضف للمفضلة">
                        <i class="fa-solid fa-heart" style="color:<?php echo $in_wishlist ? '#e11d48' : '#f97316'; ?>; font-size:1.3rem;"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>