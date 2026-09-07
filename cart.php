<?php
session_start();
require_once 'DB.php';

$coupon_error = '';
$coupon_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coupon_action'])) {
    if ($_POST['coupon_action'] === 'apply') {
        $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
        if (!empty($code)) {
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code) = :code AND is_active = 1 LIMIT 1");
            $stmt->execute(['code' => $code]);
            $coupon = $stmt->fetch();

            if ($coupon) {
                $_SESSION['applied_coupon'] = [
                    'code' => $coupon['code'],
                    'discount_percent' => floatval($coupon['discount_percent']),
                    'product_code' => $coupon['product_code'] ?? null
                ];
                $coupon_success = "تم تطبيق كود الخصم بنجاح!";
            } else {
                $coupon_error = "كود الخصم غير صحيح أو غير مفعل!";
                unset($_SESSION['applied_coupon']);
            }
        }
    } elseif ($_POST['coupon_action'] === 'remove') {
        unset($_SESSION['applied_coupon']);
        $coupon_success = "تم إلغاء كود الخصم.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['coupon_action'])) {
    $product_id = $_POST['product_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if ($product_id && isset($_SESSION['cart'][$product_id])) {
        if ($action === 'increase') {
            $_SESSION['cart'][$product_id]++;
        } elseif ($action === 'decrease') {
            $_SESSION['cart'][$product_id]--;
            if ($_SESSION['cart'][$product_id] <= 0) {
                unset($_SESSION['cart'][$product_id]);
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    header("Location: cart.php");
    exit();
}

$cart_products = [];
$total_price = 0;
$cart_count = 0;

if (!empty($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    
    if (!empty($ids)) {
        $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
        $cart_products = $stmt->fetchAll();

        foreach ($cart_products as $product) {
            $total_price += $product['price'] * $_SESSION['cart'][$product['id']];
        }
    }
}

$discount_amount = 0;
if (isset($_SESSION['applied_coupon']) && $total_price > 0) {
    $percent = $_SESSION['applied_coupon']['discount_percent'];
    $product_scope = $_SESSION['applied_coupon']['product_code'] ?? null;
    if ($product_scope) {
        // كوبون مرتبط بقطعة محددة: الخصم يشمل سعر تلك القطعة فقط
        foreach ($cart_products as $p) {
            if (($p['product_code'] ?? '') === $product_scope) {
                $discount_amount += ($p['price'] * $_SESSION['cart'][$p['id']] * $percent) / 100;
            }
        }
    } else {
        $discount_amount = ($total_price * $percent) / 100;
    }
}

$shipping_fee = $_SESSION['shipping_fee'] ?? 0;
$grand_total = max(0, $total_price - $discount_amount) + $shipping_fee;

$user_role = $_SESSION['role'] ?? 'user';
$current_theme = $_COOKIE['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سلة التسوق - ملكة الأناقة</title>
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

        .sidebar-nav {
            position: fixed; top: 0; right: 0; width: 260px; height: 100vh;
            background: linear-gradient(180deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            color: #ffffff; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; padding: 25px 15px;
            box-shadow: -5px 0 25px rgba(234, 88, 12, 0.2);
        }
        .sidebar-brand { color: #ffffff; font-size: 1.4rem; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 12px 15px; background: rgba(255, 255, 255, 0.15); border-radius: 12px; backdrop-filter: blur(5px); }
        .sidebar-menu { list-style: none; padding: 0; margin: 30px 0 0 0; display: flex; flex-direction: column; gap: 8px; }
        .sidebar-link { color: rgba(255, 255, 255, 0.9); text-decoration: none; padding: 12px 18px; border-radius: 10px; display: flex; align-items: center; gap: 12px; font-weight: 600; transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: #ffffff; color: #ea580c; transform: translateX(-5px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .sidebar-footer { border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 15px; }

        .cart-card { border: none; border-radius: 20px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(10px); box-shadow: 0 15px 35px rgba(234, 88, 12, 0.12); overflow: hidden; position: relative; z-index: 1; }
        .table > :not(caption) > * > * { padding: 1rem 0.75rem; background-color: transparent; }
        .cart-item-row { transition: all 0.2s ease; border-bottom: 1px solid #f1f5f9; }
        .cart-item-row:hover { background-color: rgba(254, 243, 199, 0.3); }
        .product-img { width: 70px; height: 70px; object-fit: cover; border-radius: 12px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); transition: transform 0.3s ease; }
        .cart-item-row:hover .product-img { transform: scale(1.05); }

        .qty-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #fed7aa; background-color: #fff7ed; color: #ea580c; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s ease; }
        .qty-btn:hover { background-color: #ea580c; color: #ffffff; border-color: #ea580c; }
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

<!-- القائمة الجانبية -->
<?php $active_page = 'cart'; include 'sidebar.php'; ?>

<div class="container my-5" style="position: relative; z-index: 2;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-bag-shopping text-warning fs-2"></i> سلة المحتويات
        </h3>
        <span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill shadow-sm">
            <?php echo $cart_count; ?> قطعة في السلة
        </span>
    </div>

    <?php if (empty($cart_products)): ?>
        <div class="cart-card text-center py-5 p-4">
            <div class="mb-3">
                <i class="fa-solid fa-basket-shopping fa-5x text-warning opacity-75"></i>
            </div>
            <h4 class="fw-bold text-dark">سلة التسوق فارغة حالياً!</h4>
            <p class="text-muted">استكشفي أحدث صيحات الموضة وأضيفي منتجاتك المفضلّة.</p>
            <a href="products.php" class="btn btn-orange-action mt-2">
                <i class="fa-solid fa-store me-2"></i> تصفح المنتجات الآن
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="cart-card p-4">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="text-secondary border-bottom">
                                    <th style="min-width: 200px;">المنتج</th>
                                    <th>السعر</th>
                                    <th class="text-center">الكمية</th>
                                    <th>الإجمالي</th>
                                    <th class="text-center">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_products as $product): ?>
                                    <?php $qty = $_SESSION['cart'][$product['id']]; ?>
                                    <tr class="cart-item-row">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/70'); ?>" alt="product" class="product-img">
                                                <div>
                                                    <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($product['title'] ?? 'منتج بدون عنوان'); ?></h6>
                                                    <small class="text-muted">المقاس: <?php echo htmlspecialchars($product['size'] ?? 'M'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-secondary">$<?php echo number_format($product['price'], 2); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="action" value="decrease">
                                                    <button type="submit" class="qty-btn">-</button>
                                                </form>
                                                <span class="fw-bold px-2"><?php echo $qty; ?></span>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="action" value="increase">
                                                    <button type="submit" class="qty-btn">+</button>
                                                </form>
                                            </div>
                                        </td>
                                        <td class="fw-bold" style="color: #ea580c;">$<?php echo number_format($product['price'] * $qty, 2); ?></td>
                                        <td class="text-center">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="action" value="remove">
                                                <button type="submit" class="btn-delete" title="حذف المنتج">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="cart-card p-4">
                    <h5 class="fw-bold text-dark mb-4 border-bottom pb-3">
                        <i class="fa-solid fa-receipt me-2 text-warning"></i> ملخص الطلب
                    </h5>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-ticket text-warning me-1"></i> هل لديكِ كود خصم؟</label>
                        
                        <?php if ($coupon_error): ?>
                            <div class="alert alert-danger py-2 small mb-2"><?php echo $coupon_error; ?></div>
                        <?php endif; ?>
                        <?php if ($coupon_success): ?>
                            <div class="alert alert-success py-2 small mb-2"><?php echo $coupon_success; ?></div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['applied_coupon'])): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded border">
                                <span class="fw-bold text-success"><i class="fa-solid fa-check-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['applied_coupon']['code']); ?> (<?php echo $_SESSION['applied_coupon']['discount_percent']; ?>%)<?php echo !empty($_SESSION['applied_coupon']['product_code']) ? ' — ' . htmlspecialchars($_SESSION['applied_coupon']['product_code']) : ''; ?></span>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="coupon_action" value="remove">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">إلغاء</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="coupon_action" value="apply">
                                <input type="text" name="coupon_code" class="form-control form-control-sm" placeholder="أدخلي الكود هنا" required style="text-transform: uppercase;">
                                <button type="submit" class="btn btn-warning btn-sm fw-bold px-3">تطبيق</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between mb-3 text-secondary">
                        <span>مجموع المنتجات:</span>
                        <span class="fw-bold">$<?php echo number_format($total_price, 2); ?></span>
                    </div>

                    <?php if ($discount_amount > 0): ?>
                        <div class="d-flex justify-content-between mb-3 text-success fw-bold">
                            <span>الخصم المطبق:</span>
                            <span>-$<?php echo number_format($discount_amount, 2); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-3 text-secondary">
                        <span>الشحن:</span>
                        <span class="fw-bold <?php echo $shipping_fee == 0 ? 'text-success' : 'text-dark'; ?>">
                            <?php echo $shipping_fee == 0 ? 'مجاناً' : '$' . number_format($shipping_fee, 2); ?>
                        </span>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold fs-5 text-dark">المجموع الكلي:</span>
                        <span class="fw-bold fs-3 text-orange" style="color: #ea580c;">$<?php echo number_format($grand_total, 2); ?></span>
                    </div>

                    <div>
                        <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
                            <a href="checkout.php" class="btn btn-orange-action w-100 text-center py-3 fs-6">
                                <i class="fa-solid fa-credit-card me-2"></i> متابعة إتمام الشراء
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-warning w-100 text-center fw-bold py-3 fs-6 rounded-3 shadow-sm">
                                <i class="fa-solid fa-lock me-2"></i> سَجِّلي دخولك لإتمام الشراء
                            </a>
                        <?php endif; ?>

                        <a href="products.php" class="btn btn-link w-100 text-center text-muted text-decoration-none mt-2">
                            <i class="fa-solid fa-arrow-right me-1"></i> العودة للمتجر
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>