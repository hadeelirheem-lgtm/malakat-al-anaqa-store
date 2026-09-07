<?php
session_start();
require_once 'DB.php';

// التحقق من أن المستخدم أدمن
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// إضافة كوبون جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount_percent = floatval($_POST['discount_percent']);
    $product_code = strtoupper(trim($_POST['product_code'] ?? ''));

    if (!empty($code) && $discount_percent > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_percent, product_code, is_active) VALUES (:code, :discount_percent, :product_code, 1)");
            $stmt->execute([
                'code' => $code,
                'discount_percent' => $discount_percent,
                'product_code' => $product_code !== '' ? $product_code : null
            ]);
            $message = "تمت إضافة كود الخصم بنجاح!";
        } catch (PDOException $e) {
            $error = "كود الخصم موجود مسبقاً أو حدث خطأ في البيانات!";
        }
    } else {
        $error = "يرجى إدخال كود صالح ونسبة خصم أكبر من 0!";
    }
}

// تغيير حالة الكوبون (تفعيل / تعطيل)
if (isset($_GET['toggle'])) {
    $coupon_id = intval($_GET['toggle']);
    $stmt = $pdo->prepare("UPDATE coupons SET is_active = NOT is_active WHERE id = :id");
    $stmt->execute(['id' => $coupon_id]);
    header("Location: admin_coupons.php");
    exit();
}

// حذف كوبون
if (isset($_GET['delete'])) {
    $coupon_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = :id");
    $stmt->execute(['id' => $coupon_id]);
    header("Location: admin_coupons.php");
    exit();
}

// جلب جميع الكوبونات
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY id DESC");
$coupons = $stmt->fetchAll();

$user_role = $_SESSION['role'] ?? 'admin';
$cart_count = !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$current_theme = $_COOKIE['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة أكواد الخصم - ملكة الأناقة</title>
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
            box-shadow: -5px 0 25px rgba(234, 88, 12, 0.2);
        }
        .sidebar-brand { color: #fff; font-size: 1.4rem; font-weight: bold; text-decoration: none; padding: 12px; background: rgba(255,255,255,0.15); border-radius: 12px; display: flex; align-items: center; gap: 10px; }
        .sidebar-menu { list-style: none; padding: 0; margin-top: 30px; display: flex; flex-direction: column; gap: 8px; }
        .sidebar-link { color: rgba(255, 255, 255, 0.9); text-decoration: none; padding: 12px 18px; border-radius: 10px; display: flex; align-items: center; gap: 12px; font-weight: 600; transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: #ffffff; color: #ea580c; transform: translateX(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .sidebar-footer { border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px; }

        .admin-card { border: none; border-radius: 20px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(10px); box-shadow: 0 15px 35px rgba(234, 88, 12, 0.12); }
        .btn-orange-action { background: linear-gradient(135deg, #f97316, #ea580c); color: #ffffff; border: none; border-radius: 12px; padding: 10px 24px; font-weight: bold; transition: all 0.3s ease; }
        .btn-orange-action:hover { background: linear-gradient(135deg, #ea580c, #c2410c); color: #ffffff; }

        @media (max-width: 991px) { body { padding-right: 0; } .sidebar-nav { position: relative; width: 100%; height: auto; } }
    </style>
</head>
<body>

<!-- القائمة الجانبية كاملة كما هي -->
<?php $active_page = 'admin_coupons'; include 'sidebar.php'; ?>

<div class="container my-5" style="position: relative; z-index: 2;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-ticket text-warning me-2"></i> إدارة أظرف وأكواد الخصم</h3>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- نموذج إضافة كود خصم جديد (عريض في الأعلى) -->
        <div class="col-12">
            <div class="admin-card p-4">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="fa-solid fa-plus-circle text-warning me-1"></i> إضافة كود خصم جديد</h5>
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-bold small">كود الخصم (رمز الأناقة):</label>
                        <input type="text" name="code" class="form-control text-start" placeholder="مثال: ELEGANCE10" required style="text-transform: uppercase;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">نسبة الخصم (%):</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="1" max="100" name="discount_percent" class="form-control text-start" placeholder="15" required>
                            <span class="input-group-text"><i class="fa-solid fa-percent"></i></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">كود المنتج (SKU) <span class="text-muted">— اتركه فارغاً ليكون كوبون عام على كل السلة</span>:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-tag"></i></span>
                            <input type="text" name="product_code" class="form-control text-start" placeholder="مثال: SKU-0001" style="text-transform: uppercase;">
                        </div>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" name="add_coupon" class="btn btn-orange-action w-100">
                            <i class="fa-solid fa-save me-1"></i> حفظ الكود
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- جدول الأكواد المتاحة (تحت) -->
        <div class="col-12">
            <div class="admin-card p-4">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="fa-solid fa-list text-warning me-1"></i> الكوبونات المتاحة</h5>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr class="text-secondary">
                                <th>الكود</th>
                                <th>الخصم</th>
                                <th>المنتج (SKU)</th>
                                <th>الحالة</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">لا توجد أكواد خصم مضافة حالياً.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><span class="badge bg-light text-dark border px-3 py-2"><?php echo htmlspecialchars($coupon['code']); ?></span></td>
                                        <td class="fw-bold text-success">%<?php echo $coupon['discount_percent']; ?></td>
                                        <td>
                                            <?php if (!empty($coupon['product_code'])): ?>
                                                <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1"><?php echo htmlspecialchars($coupon['product_code']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">عام (كل السلة)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($coupon['is_active']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">مفعل</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-1">معطل</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="admin_coupons.php?toggle=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-outline-warning me-1" title="تغيير الحالة">
                                                <i class="fa-solid fa-power-off"></i>
                                            </a>
                                            <a href="admin_coupons.php?delete=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنتِ متأكدة من حذف هذا الكود؟');" title="حذف">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>