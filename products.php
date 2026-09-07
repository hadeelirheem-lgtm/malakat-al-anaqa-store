<?php
session_start();
require_once 'DB.php';

// جلب كافة الأقسام المضافة للمنتجات من قاعدة البيانات تلقائياً
$stmt_cats = $pdo->query("SELECT DISTINCT category FROM products WHERE is_archived = 0 AND category IS NOT NULL AND category != ''");
$categories = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$cart_ids = array_keys($_SESSION['cart'] ?? []);
$wishlist_ids = array_keys($_SESSION['wishlist'] ?? []);
$current_theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// قائمة الأقسام الثابتة
$categories = ['عام', 'نسائي', 'رجالي', 'أطفال', 'ميك أب', 'إكسسوارات', 'شنط'];

$search = trim($_GET['search'] ?? '');
$selected_category = $_GET['category'] ?? 'all';

$query = "SELECT * FROM products WHERE is_archived = 0";
$params = [];

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR description LIKE ? OR product_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($selected_category !== 'all' && !empty($selected_category)) {
    $query .= " AND category = ?";
    $params[] = $selected_category;
}

$query .= " ORDER BY id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

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
    <title>معرض المنتجات | ملكة الأناقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
        }

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

        .bg-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.4;
            animation: float 8s ease-in-out infinite alternate;
            z-index: 0;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            background: #fb923c;
            top: -50px;
            left: -50px;
        }

        .shape-2 {
            width: 250px;
            height: 250px;
            background: #f43f5e;
            bottom: -50px;
            right: var(--sidebar-width);
            animation-delay: -4s;
        }

        @keyframes float {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(30px) scale(1.1); }
        }

        .sidebar-nav {
            position: fixed;
            top: 0;
            right: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            color: #ffffff;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 25px 15px;
            box-shadow: -5px 0 25px rgba(234, 88, 12, 0.2);
        }

        .sidebar-brand {
            color: #ffffff;
            font-size: 1.4rem;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            backdrop-filter: blur(5px);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 30px 0 0 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background: #ffffff;
            color: #ea580c;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .sidebar-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 15px;
        }

        /* كارت الفلترة بحواف دائرية */
        .filter-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            border: none;
            box-shadow: 0 10px 30px rgba(234, 88, 12, 0.08);
        }

        .search-box-wrapper {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        .search-input-pill {
            border-radius: 50px !important;
            padding-right: 25px;
            padding-left: 25px;
            border: 1px solid #fed7aa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        .search-input-pill:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.15);
        }

        .btn-filter-pill {
            border-radius: 50px !important;
            padding: 10px 32px;
            background: #f97316;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-filter-pill:hover {
            background: #ea580c;
        }

        .category-btn {
            border-radius: 50px;
            padding: 8px 24px;
            font-weight: 600;
            border: 1px solid #f97316;
            color: #ea580c;
            transition: all 0.2s ease;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .category-btn.active, .category-btn:hover {
            background: #ea580c;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
        }

        .product-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(234, 88, 12, 0.15);
        }

        .product-img {
            height: 240px;
            object-fit: cover;
            width: 100%;
        }

        .badge-category {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #f59e0b;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
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
    <div class="text-center mb-4">
        <h2 class="fw-bold text-dark m-0"><i class="fa-solid fa-store me-2 text-warning"></i>معرض المنتجات</h2>
    </div>

    <!-- كارت الفلترة الموسط بالكامل -->
    <div class="card filter-card p-4 mb-5">
        <form method="GET" class="search-box-wrapper mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control search-input-pill" placeholder="ابحثي عن منتج..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-filter-pill text-white fw-bold ms-2" type="submit">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> بحث
                </button>
            </div>
        </form>

        <!-- أزرار الأقسام -->
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="products.php" class="category-btn <?php echo $selected_category === 'all' ? 'active' : ''; ?>">الكل</a>
            <?php foreach ($categories as $cat): ?>
                <a href="products.php?category=<?php echo urlencode($cat); ?>&search=<?php echo urlencode($search); ?>" 
                   class="category-btn <?php echo $selected_category === $cat ? 'active' : ''; ?>">
                   <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- عرض المنتجات -->
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="card product-card h-100 position-relative">
                    <span class="badge-category"><?php echo htmlspecialchars($product['category'] ?? 'عام'); ?></span>
                    <?php $in_wishlist = in_array($product['id'], $wishlist_ids); ?>
                    <a href="add_to_wishlist.php?id=<?php echo $product['id']; ?>" 
                       class="wishlist-heart" title="أضف للمفضلة" style="position:absolute; top:12px; left:12px; z-index:5;">
                        <i class="fa-solid fa-heart" style="font-size:1.3rem; color:<?php echo $in_wishlist ? '#e11d48' : '#ffffff'; ?>; text-shadow:0 1px 3px rgba(0,0,0,0.4);"></i>
                    </a>
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top product-img" alt="<?php echo htmlspecialchars($product['title']); ?>">
                    <div class="card-body d-flex flex-column justify-content-between p-3">
                        <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($product['title']); ?></h6>
                        <small class="text-muted d-block mb-2">الكود: <?php echo htmlspecialchars($product['product_code'] ?? '—'); ?></small>
                        <?php if (isset($coupon_map[$product['product_code'] ?? ''])): $cp = $coupon_map[$product['product_code']]; ?>
                            <span class="badge bg-warning text-dark w-auto align-self-start mb-2" title="كود خصم خاص بهذه القطعة"><i class="fa-solid fa-ticket me-1"></i>خصم <?php echo $cp['discount_percent']; ?>% بكود <?php echo htmlspecialchars($cp['code']); ?></span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="fs-5 fw-bold text-danger">$<?php echo number_format($product['price'], 2); ?></span>
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-warning btn-sm rounded-circle">
                                <i class="fa-solid fa-cart-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-box-open fs-1 text-muted mb-3 d-block"></i>
                <h5 class="text-muted">لا توجد منتجات مطابقة للبحث أو القسم المحدد.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>