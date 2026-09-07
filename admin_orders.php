<?php
session_start();
require_once 'DB.php';

// التأكد من تسجيل الدخول وأن الحساب Admin
if (!isset($_SESSION['is_logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit();
}

// تعريف المتغيرات لتفادي التحذيرات (Warnings)
$user_role = $_SESSION['role'] ?? '';
$cart_count = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// تغيير حالة الطلب عند الضغط على الزر
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $new_status, 'id' => $order_id]);
    header("Location: admin_orders.php");
    exit();
}

// جلب جميع الطلبات مرتبة من الأحدث للأقدم
$stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
$orders = $stmt->fetchAll();

// فصل الطلبات: الواردة (لم تُوصَّل بعد) والمُسلَّمة (تم التوصيل)
$delivered_orders = array();
$pending_orders = array();
foreach ($orders as $o) {
    if (($o['status'] ?? '') === 'تم التوصيل') {
        $delivered_orders[] = $o;
    } else {
        $pending_orders[] = $o;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلبات - ملكة الأناقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root { --sidebar-width: 260px; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #fffaf0, #ffedd5, #fef3c7, #fff5eb);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            min-height: 100vh;
            margin: 0;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* القائمة الجانبية */
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

        /* حاوية المحتوى لتفادي تداخل السايد بار */
        .main-content {
            margin-right: 260px;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .custom-card {
            width: 100%;
            max-width: 1250px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 30px;
        }

        @media (max-width: 991px) {
            .sidebar-nav { position: relative; width: 100%; height: auto; }
            .main-content { margin-right: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<!-- القائمة الجانبية -->
<?php $active_page = 'admin_orders'; include 'sidebar.php'; ?>

<!-- المحتوى الرئيسي -->
<main class="main-content">
    <!-- تبويبات الطلبات -->
    <div class="custom-card">
        <ul class="nav nav-pills mb-4 gap-2" id="ordersTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active shadow-sm fw-bold" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pendingPane" type="button" role="tab">
                    <i class="fa-solid fa-inbox me-1"></i> الطلبات الواردة
                    <span class="badge bg-warning text-dark ms-1"><?php echo count($pending_orders); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="delivered-tab" data-bs-toggle="pill" data-bs-target="#deliveredPane" type="button" role="tab">
                    <i class="fa-solid fa-circle-check me-1"></i> تم توصيلها
                    <span class="badge bg-success ms-1"><?php echo count($delivered_orders); ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="ordersTabContent">

        <!-- ====== تبويب الطلبات الواردة ====== -->
        <div class="tab-pane fade show active" id="pendingPane" role="tabpanel">
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <h2 class="fw-bold text-dark m-0"><i class="fa-solid fa-boxes-packing text-warning me-2"></i>طلبات الزبائن الواردة</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>المشتري</th>
                            <th>رقم الجوال</th>
                            <th>العنوان</th>
                            <th>المنتجات المطلوبة</th>
                            <th>المبلغ الإجمالي</th>
                            <th>طريقة الدفع</th>
                            <th>الإشعار</th>
                            <th>الفاتورة</th>
                            <th>الحالة</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pending_orders) > 0): ?>
                            <?php foreach ($pending_orders as $order): ?>
                                <?php include 'admin_orders_row.php'; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center py-4 text-muted">لا توجد طلبات واردة حالياً.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ====== تبويب تم توصيلها ====== -->
        <div class="tab-pane fade" id="deliveredPane" role="tabpanel">
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <h2 class="fw-bold text-dark m-0"><i class="fa-solid fa-circle-check text-success me-2"></i>الطلبات التي تم توصيلها</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-success">
                        <tr>
                            <th>#</th>
                            <th>المشتري</th>
                            <th>رقم الجوال</th>
                            <th>العنوان</th>
                            <th>المنتجات المطلوبة</th>
                            <th>المبلغ الإجمالي</th>
                            <th>طريقة الدفع</th>
                            <th>الإشعار</th>
                            <th>الفاتورة</th>
                            <th>الحالة</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($delivered_orders) > 0): ?>
                            <?php foreach ($delivered_orders as $order): ?>
                                <?php include 'admin_orders_row.php'; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center py-4 text-muted">لا توجد طلبات مُسلَّمة بعد.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>