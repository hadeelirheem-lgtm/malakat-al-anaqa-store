<?php
session_start();
require_once 'DB.php';

// التحقق من صلاحيات الإدمن
if (!isset($_SESSION['is_logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// تعريف المتغيرات لتفادي التحذيرات (Warnings)
$user_role = $_SESSION['role'] ?? '';
$cart_count = 0;

// حساب عدد عناصر السلة إن وجدت بالجلسة
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// استعادة المنتج من الأرشيف
if (isset($_GET['restore_id'])) {
    $restore_id = intval($_GET['restore_id']);
    $stmt = $pdo->prepare("UPDATE products SET is_archived = 0 WHERE id = ?");
    $stmt->execute([$restore_id]);
    header("Location: archived_products.php");
    exit();
}

// الحذف النهائي
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: archived_products.php");
    exit();
}

// جلب المنتجات المؤرشفة
$stmt = $pdo->query("SELECT * FROM products WHERE is_archived = 1 ORDER BY id DESC");
$archived_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأرشيف - ملكة الأناقة</title>
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

        /* القائمة الجانبية ثابتة في اليمين */
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
        .sidebar-link:hover, .sidebar-link.active { background: #ffffff; color: #ea580c; transform: translateX(-5px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }

        /* منطقة المحتوى الرئيسية لتجنب التداخل مع السايد بار */
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
            max-width: 1000px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(217, 119, 6, 0.15);
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
<?php $active_page = 'archived'; include 'sidebar.php'; ?>

<!-- حاوية المحتوى الرئيسية بالمنتصف -->
<main class="main-content">
    <div class="custom-card">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <h2><i class="fa-solid fa-box-archive me-2 text-warning"></i>المنتجات المؤرشفة</h2>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-3">
                <i class="fa-solid fa-arrow-right me-1"></i> العودة لإدارة المنتجات
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th>الصورة</th>
                        <th>اسم المنتج</th>
                        <th>السعر</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archived_products as $product): ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($product['image']); ?>" width="50" height="50" class="rounded object-fit-cover shadow-sm"></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($product['title']); ?></td>
                        <td class="text-success fw-bold">$<?php echo number_format($product['price'], 2); ?></td>
                        <td>
                            <a href="archived_products.php?restore_id=<?php echo $product['id']; ?>" class="btn btn-success btn-sm me-1 shadow-sm">
                                <i class="fa-solid fa-rotate-left"></i> استعادة
                            </a>
                            <a href="archived_products.php?delete_id=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm shadow-sm" onclick="return confirm('هل أنتِ متأكدة من الحذف النهائي؟');">
                                <i class="fa-solid fa-trash"></i> حذف نهائي
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($archived_products)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">لا توجد منتجات مؤرشفة حالياً</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>