<?php
session_start();
require_once 'DB.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$coupon_error = '';
$coupon_success = '';

// معالجة كود الخصم في صفحة الدفع
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

// التوجيه إلى السلة إن كانت فارغة (إلا إذا تم إتمام الطلب للتو)
$last_order_id = $_GET['order_id'] ?? null;

if (empty($_SESSION['cart']) && !$last_order_id) {
    header("Location: cart.php");
    exit();
}

$grand_total = 0;
$total_price = 0;
$shipping_fee = 0;
$discount_amount = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    $cart_products = $stmt->fetchAll();

    $items_details = [];

    foreach ($cart_products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $total_price += $product['price'] * $qty;
        $items_details[] = $product['title'] . " (العدد: " . $qty . ")";
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
    $order_items_str = implode(' | ', $items_details);
}

$user_role = $_SESSION['role'] ?? 'user';
$cart_count = !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$current_theme = $_COOKIE['theme'] ?? 'light';

// بيانات التحويل البنكي (يضبطها الأدمن من لوحة التحكم)
$payment_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    foreach ($stmt->fetchAll() as $row) {
        $payment_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // في حال غياب الجدول لا يتعطل إتمام الدفع
}

// معالجة تأكيد الدفع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['cart']) && !isset($_POST['coupon_action'])) {
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $payment_method = $_POST['payment_method'];
    $receipt_path = null;
    $user_name = $_SESSION['user_name'] ?? 'زائر';

    if ($payment_method === 'online' && isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $receiptName = $_FILES['receipt_image']['name'];
        $receiptTmp = $_FILES['receipt_image']['tmp_name'];
        
        if (!is_dir('uploads/receipts')) {
            mkdir('uploads/receipts', 0777, true);
        }
        
        $targetFile = "uploads/receipts/receipt_" . time() . '_' . basename($receiptName);
        if (move_uploaded_file($receiptTmp, $targetFile)) {
            $receipt_path = $targetFile;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO orders (user_name, phone, address, total_price, payment_method, receipt_path, order_items) VALUES (:user_name, :phone, :address, :total_price, :payment_method, :receipt_path, :order_items)");
    
    $stmt->execute([
        'user_name' => $user_name,
        'phone' => $phone,
        'address' => $address,
        'total_price' => $grand_total,
        'payment_method' => $payment_method === 'online' ? 'محفظة / بنك' : 'الدفع عند الاستلام',
        'receipt_path' => $receipt_path,
        'order_items' => $order_items_str
    ]);

    $last_id = $pdo->lastInsertId();

    $_SESSION['cart'] = [];
    unset($_SESSION['applied_coupon']);
    
    header("Location: checkout.php?order_id=" . $last_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملكة الأناقة - إتمام الدفع</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root { --sidebar-width: 260px; }
        body {
            padding-right: 260px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #fffaf0, #ffedd5, #fef3c7, #fff5eb);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            min-height: 100vh;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .sidebar-nav {
            position: fixed; top: 0; right: 0; width: 260px; height: 100vh;
            background: linear-gradient(180deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            color: #ffffff; z-index: 1000; display: flex; flex-direction: column;
            justify-content: space-between; padding: 25px 15px;
        }
        .sidebar-brand { color: #fff; font-size: 1.4rem; font-weight: bold; text-decoration: none; padding: 12px; background: rgba(255,255,255,0.15); border-radius: 12px; display: flex; align-items: center; gap: 10px; }
        .sidebar-menu { list-style: none; padding: 0; margin-top: 30px; display: flex; flex-direction: column; gap: 8px; }
        .sidebar-link { color: rgba(255, 255, 255, 0.9); text-decoration: none; padding: 12px 18px; border-radius: 10px; display: flex; align-items: center; gap: 12px; font-weight: 600; }
        .sidebar-link:hover, .sidebar-link.active { background: #ffffff; color: #ea580c; }
        
        .checkout-card { border: none; border-radius: 20px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); box-shadow: 0 15px 35px rgba(234, 88, 12, 0.12); overflow: hidden; }
        .checkout-header { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; padding: 20px 25px; }
        .payment-option-card { border: 2px solid #e5e7eb; border-radius: 14px; padding: 15px 20px; cursor: pointer; background: #ffffff; transition: all 0.2s ease; }
        .payment-option-card.active-option { border-color: #ea580c; background: #fff3ed; }
        .btn-orange-submit { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #ffffff; border: none; border-radius: 14px; font-weight: bold; }
        .btn-orange-submit:hover { background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%); color: white; }

        @media (max-width: 991px) { body { padding-right: 0; } .sidebar-nav { position: relative; width: 100%; height: auto; } }
    </style>
</head>
<body>

<!-- القائمة الجانبية -->
<?php $active_page = 'cart'; include 'sidebar.php'; ?>

<div class="container my-5 col-12 col-lg-8" style="position: relative; z-index: 2;">
    <?php if ($last_order_id): ?>
        <div class="checkout-card p-5 text-center shadow-lg">
            <i class="fa-solid fa-circle-check fa-5x text-success mb-4"></i>
            <h2 class="fw-bold text-dark mb-3">تمت عملية الشراء بنجاح!</h2>
            <p class="lead text-muted mb-4">تم حفظ طلبكِ رقم <strong>#<?php echo htmlspecialchars($last_order_id); ?></strong> بنجاح، وسيظهر لدى الإدارة لمراجعته والتواصل معكِ فوراً.</p>
            
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="invoice.php?id=<?php echo $last_order_id; ?>" target="_blank" class="btn btn-warning btn-lg fw-bold px-4 py-3 text-dark rounded-3 shadow-sm">
                    <i class="fa-solid fa-file-invoice me-2"></i>عرض وطباعة الفاتورة
                </a>
                
                <a href="products.php" class="btn btn-orange-submit btn-lg px-5 py-3 text-decoration-none">
                    <i class="fa-solid fa-store me-2"></i> العودة للمتجر
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="checkout-card shadow-lg">
            <div class="checkout-header d-flex align-items-center justify-content-between">
                <h4 class="fw-bold mb-0"><i class="fa-solid fa-truck-fast me-2"></i> إتمام طلب الشراء</h4>
                <span class="badge bg-white text-dark rounded-pill fw-bold fs-6">$<?php echo number_format($grand_total, 2); ?></span>
            </div>
            
            <div class="p-4 p-md-5">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-location-dot text-warning me-2"></i> بيانات التوصيل</h5>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">رقم الهاتف للتواصل:</label>
                            <input type="text" name="phone" class="form-control form-control-lg" required placeholder="05XXXXXXXX">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">عنوان التوصيل بالتفصيل:</label>
                            <textarea name="address" class="form-control" rows="3" required placeholder="المدينة، الحي، اسم الشارع، رقم العمارة..."></textarea>
                        </div>
                    </div>

                    <div class="mb-4 border-top pt-4">
                        <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-wallet text-warning me-2"></i> طريقة الدفع</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="payment-option-card active-option w-100 d-flex align-items-center gap-3" id="opt-cod">
                                    <input type="radio" name="payment_method" value="cod" checked onchange="toggleReceiptUpload(false)">
                                    <div>
                                        <div class="fw-bold text-dark"><i class="fa-solid fa-hand-holding-dollar text-success me-1"></i> الدفع عند الاستلام</div>
                                        <small class="text-muted">تسليم المبلغ نقداً للمندوب عند وصول الشحنة</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="payment-option-card w-100 d-flex align-items-center gap-3" id="opt-online">
                                    <input type="radio" name="payment_method" value="online" onchange="toggleReceiptUpload(true)">
                                    <div>
                                        <div class="fw-bold text-dark"><i class="fa-solid fa-building-columns text-primary me-1"></i> تحويل بنكي / محفظة</div>
                                        <small class="text-muted">رفع صورة من إيصال التحويل مباشرة</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- صندوق أرقام التحويل وإرفاق الصورة -->
                        <div id="receipt-upload-box" class="mt-4 p-3 bg-light rounded-3 border border-warning d-none">
                            <div class="alert alert-warning mb-3">
                                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-circle-info text-warning me-1"></i> يرجى تحويل المبلغ المالي على الحسابات التالية:</h6>
                                <ul class="mb-0 text-dark small fw-bold ps-3">
                                    <li>محفظة جوال باي / بال باي: <span class="text-danger"><?php echo htmlspecialchars($payment_settings['wallet_phone'] ?? 'غير مضبوط بعد'); ?></span></li>
                                    <li>رقم الحساب البنكي (IBAN): <span class="text-danger"><?php echo htmlspecialchars($payment_settings['bank_iban'] ?? 'غير مضبوط بعد'); ?></span></li>
                                   
                                </ul>
                            </div>

                            <label class="form-label fw-bold small"><i class="fa-solid fa-paperclip text-warning me-1"></i> إرفاق صورة إيصال التحويل:</label>
                            <input type="file" name="receipt_image" accept="image/*" class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-orange-submit w-100 py-3 fs-5 mt-2">
                        <i class="fa-solid fa-check-circle me-2"></i> تأكيد وإرسال الطلب ($<?php echo number_format($grand_total, 2); ?>)
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleReceiptUpload(show) {
    const box = document.getElementById('receipt-upload-box');
    const optCod = document.getElementById('opt-cod');
    const optOnline = document.getElementById('opt-online');
    
    if (show) {
        box.classList.remove('d-none');
        optOnline.classList.add('active-option');
        optCod.classList.remove('active-option');
    } else {
        box.classList.add('d-none');
        optCod.classList.add('active-option');
        optOnline.classList.remove('active-option');
    }
}
</script>

</body>
</html>