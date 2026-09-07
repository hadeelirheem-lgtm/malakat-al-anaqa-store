<?php
session_start();
require_once 'DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $remove_id = intval($_POST['product_id']);
    unset($_SESSION['wishlist'][$remove_id]);
    header("Location: wishlist.php");
    exit();
}

$wishlist_products = [];
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

if (!empty($_SESSION['wishlist'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['wishlist'])));
    if (!empty($ids)) {
        $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
        $wishlist_products = $stmt->fetchAll();
    }
}

$cart_ids = array_keys($_SESSION['cart'] ?? []);
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$current_theme = $_COOKIE['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المفضلة - ملكة الأناقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; }
        body {
            padding-right: 260px;
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
        .shape-2 { width: 280px; height: 280px; background: #ea580c; bottom: -40px; right: 260px; animation-delay: -4s; }
        @keyframes float { 0% { transform: translateY(0) scale(1); } 100% { transform: translateY(30px) scale(1.08); } }
        .sidebar-nav { position: fixed; top: 0; right: 0; width: 260px; height: 100vh; background: linear-gradient(180deg, #f97316 0%, #ea580c 50%, #c2410c 100%); color: #ffffff; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; padding: 25px 15px; box-shadow: -5px 0 25px rgba(234, 88, 12, 0.2); }
        .sidebar-brand { color: #ffffff; font-size: 1.4rem; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 12px 15px; background: rgba(255, 255, 255, 0.15); border-radius: 12px; backdrop-filter: blur(5px); }
        .sidebar-menu { list-style: none; padding: 0; margin: 30px 0 0 0; display: flex; flex-direction: column; gap: 8px; }
        .sidebar-link { color: rgba(255, 255, 255, 0.9); text-decoration: none; padding: 12px 18px; border-radius: 10px; display: flex; align-items: center; gap: 12px; font-weight: 600; transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: #ffffff; color: #ea580c; transform: translateX(-5px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .sidebar-footer { border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 15px; }
        .cart-card { border: none; border-radius: 20px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(10px); box-shadow: 0 15px 35px rgba(234, 88, 12, 0.12); overflow: hidden; position: relative; z-index: 1; }
        .product-card { border: none; border-radius: 20px; overflow: hidden; background: #ffffff; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(234, 88, 12, 0.15); }
        .product-img { height: 220px; object-fit: cover; width: 100%; }
        .btn-orange-action { background: linear-gradient(135deg, #f97316, #ea580c); color: #ffffff; border: none; border-radius: 12px; padding: 12px 28px; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(234, 88, 12, 0.25); }
        .btn-orange-action:hover { background: linear-gradient(135deg, #ea580c, #c2410c); color: #ffffff; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(234, 88, 12, 0.35); }
        .btn-delete { color: #ef4444; background: #fef2f2; border: none; width: 36px; height: 36px; border-radius: 10px; transition: all 0.2s ease; }
        .btn-delete:hover { background: #ef4444; color: #ffffff; transform: scale(1.08); }
        @media (max-width: 991px) { body { padding-right: 0; } .sidebar-nav { position: relative; width: 100%; height: auto; } .shape-2 { right: 0; } }
    </style>
</head>
<body>

<div class="bg-shape shape-1"></div>
<div class="bg-shape shape-2"></div>

<?php $active_page = 'wishlist'; include 'sidebar.php'; ?>

<div class="container my-5" style="position: relative; z-index: 2;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-heart text-danger fs-2"></i> المفضلة
        </h3>
        <span class="badge bg-danger text-white fs-6 px-3 py-2 rounded-pill shadow-sm">
            <?php echo $wishlist_count; ?> منتج
        </span>
    </div>

    <?php if (empty($wishlist_products)): ?>
        <div class="cart-card text-center py-5 p-4">
            <div class="mb-3">
                <i class="fa-regular fa-heart fa-5x text-danger opacity-75"></i>
            </div>
            <h4 class="fw-bold text-dark">المفضلة فارغة حالياً!</h4>
            <p class="text-muted">أضيفي المنتجات المفضلة بالضغط على أيقونة القلب.</p>
            <a href="products.php" class="btn btn-orange-action mt-2">
                <i class="fa-solid fa-store me-2"></i> تصفح المنتجات الآن
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($wishlist_products as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card product-card h-100 position-relative">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top product-img" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        </a>
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark"><?php echo htmlspecialchars($product['title']); ?></a>
                                </h6>
                                <small class="text-muted d-block mb-2">الكود: <?php echo htmlspecialchars($product['product_code'] ?? '—'); ?></small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="fs-5 fw-bold text-danger">$<?php echo number_format($product['price'], 2); ?></span>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn btn-orange-action btn-sm rounded-pill px-3 py-1" title="أضف للسلة وأكمل الشراء">
                                        <i class="fa-solid fa-cart-plus"></i> السلة
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn-delete" title="إزالة من المفضلة">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-orange-action">
                <i class="fa-solid fa-store me-2"></i> تصفح المزيد من المنتجات
            </a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>