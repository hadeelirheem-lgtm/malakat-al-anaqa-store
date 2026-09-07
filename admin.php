<?php
session_start();
require_once 'DB.php';

// حفظ قيمة الشحن في Session أو DB
$shipping_msg = '';
if (isset($_POST['update_shipping'])) {
    $_SESSION['shipping_fee'] = (float)$_POST['shipping_fee'];
    $shipping_msg = "<div class='alert alert-success text-center border-0 shadow-sm rounded-3 py-2 my-2'><i class='fa-solid fa-circle-check me-1'></i> تم تحديث سعر الشحن بنجاح!</div>";
}
$current_shipping = $_SESSION['shipping_fee'] ?? 0; // 0 يعني مجاناً

// معالجة حفظ بيانات التحويل البنكي
$payment_msg = '';
if (isset($_POST['save_payment'])) {
    $vals = [
        'bank_iban' => trim($_POST['bank_iban'] ?? ''),
        'wallet_phone' => trim($_POST['wallet_phone'] ?? ''),
        'paypal_email' => trim($_POST['paypal_email'] ?? ''),
    ];
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v");
    foreach ($vals as $k => $v) {
        $stmt->execute(['k' => $k, 'v' => $v]);
    }
    $payment_msg = "<div class='alert alert-success text-center border-0 shadow-sm rounded-3 py-2 my-2'><i class='fa-solid fa-circle-check me-1'></i> تم حفظ بيانات التحويل البنكي بنجاح!</div>";
}

// جلب بيانات التحويل الحالية لعرضها في النموذج
$payment_settings = [];
$stmt_pay = $pdo->query("SELECT setting_key, setting_value FROM settings");
foreach ($stmt_pay->fetchAll() as $row) {
    $payment_settings[$row['setting_key']] = $row['setting_value'];
}

// التأكد من تسجيل الدخول وأن رتبة المستخدم admin
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// معالجة طلب الأرشيف
if (isset($_GET['archive_id'])) {
    $archive_id = intval($_GET['archive_id']);
    $stmt = $pdo->prepare("UPDATE products SET is_archived = 1 WHERE id = ?");
    $stmt->execute([$archive_id]);
    header("Location: admin.php");
    exit();
}

$message = '';
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$current_theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// معالجة إضافة منتج جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_shipping']) && !isset($_POST['save_payment'])) {
    $title = trim($_POST['title'] ?? '');
    $price = $_POST['price'] ?? 0;
    $size = $_POST['size'] ?? 'M';
    $category = $_POST['category'] ?? 'عام';
    $description = trim($_POST['description'] ?? '');
    $product_code = trim($_POST['product_code'] ?? '');

    // إذا لم يُدخل الادمن كودًا، نولِّد واحدًا تلقائيًا
    if ($product_code === '') {
        $max = $pdo->query("SELECT MAX(id) FROM products")->fetchColumn();
        $product_code = "SKU-" . str_pad((string)((int)$max + 1), 4, "0", STR_PAD_LEFT);
    }

    // معالجة رفع الصورة
    $imageName = $_FILES['image']['name'] ?? '';
    $imageTmp = $_FILES['image']['tmp_name'] ?? '';
    $targetDir = "uploads/";
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $targetFile = $targetDir . time() . '_' . basename($imageName);

    if (move_uploaded_file($imageTmp, $targetFile)) {
        // حفظ البيانات في قاعدة البيانات (غير مؤرشف افتراضياً)
        $stmt = $pdo->prepare("INSERT INTO products (title, description, price, size, image, category, product_code, is_archived) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        if ($stmt->execute([$title, $description, $price, $size, $targetFile, $category, $product_code])) {
            $message = "<div class='alert alert-success text-center border-0 shadow-sm rounded-3 py-2'><i class='fa-solid fa-circle-check me-1'></i> تم إضافة المنتج بنجاح!</div>";
        } else {
            $message = "<div class='alert alert-danger text-center border-0 shadow-sm rounded-3 py-2'><i class='fa-solid fa-triangle-exclamation me-1'></i> حدث خطأ أثناء الحفظ في قاعدة البيانات.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger text-center border-0 shadow-sm rounded-3 py-2'><i class='fa-solid fa-triangle-exclamation me-1'></i> فشل رفع الصورة.</div>";
    }
}

// جلب المنتجات غير المؤرشفة لعرضها في الجدول
$stmt_products = $pdo->query("SELECT * FROM products WHERE is_archived = 0 ORDER BY id DESC");
$active_products = $stmt_products->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - إدارة المنتجات | ملكة الأناقة</title>
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

        .admin-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(217, 119, 6, 0.15);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }

        .admin-card-header {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
        }

        .form-control:focus, .form-select:focus {
            border-color: #ea580c;
            box-shadow: 0 0 0 0.25rem rgba(234, 88, 12, 0.15);
        }

        .input-group-text {
            border-radius: 10px;
            background-color: #fff7ed;
            border-color: #cbd5e1;
            color: #ea580c;
        }

        .btn-orange {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-orange:hover {
            background: linear-gradient(135deg, #ea580c, #c2410c);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(234, 88, 12, 0.3);
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
<?php $active_page = 'admin'; include 'sidebar.php'; ?>

<!-- محتوى الصفحة الرئيسي -->
<div class="container my-5" style="position: relative; z-index: 2;">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">

            <!-- كارت إعدادات الشحن والتوصيل -->
            <div class="card admin-card mb-4">
                <div class="admin-card-header bg-secondary">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-truck-fast me-2"></i>إعدادات الشحن والتوصيل</h5>
                </div>
                <div class="card-body p-4">
                    <?= $shipping_msg; ?>
                    <form method="POST" class="row align-items-center g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-secondary small">تكلفة الشحن ($):</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                <input type="number" step="0.01" min="0" name="shipping_fee" class="form-control" value="<?php echo $current_shipping; ?>" placeholder="0 تعني الشحن مجاني">
                            </div>
                            <small class="text-muted mt-1 d-block">ضعي القيمة كـ 0 ليكون الشحن **مجاناً**، أو أدخلي المبلغ المطلوب (مثلاً 5.00).</small>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" name="update_shipping" class="btn btn-orange w-100">
                                <i class="fa-solid fa-floppy-disk me-1"></i> حفظ الشحن
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- كارت بيانات التحويل البنكي -->
            <div class="card admin-card mb-4">
                <div class="admin-card-header bg-info">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-building-columns me-2"></i>بيانات التحويل البنكي</h5>
                </div>
                <div class="card-body p-4">
                    <?= $payment_msg; ?>
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">رقم الحساب البنكي (IBAN):</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-building-columns"></i></span>
                                <input type="text" name="bank_iban" class="form-control text-start" value="<?php echo htmlspecialchars($payment_settings['bank_iban'] ?? ''); ?>" placeholder="PSXX XXXX XXXX ...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">رقم جوال باي / كاش:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-mobile-screen"></i></span>
                                <input type="text" name="wallet_phone" class="form-control text-start" value="<?php echo htmlspecialchars($payment_settings['wallet_phone'] ?? ''); ?>" placeholder="059XXXXXXX">
                            </div>
                        </div>
                  
                        <div class="col-12 d-grid mt-1">
                            <button type="submit" name="save_payment" class="btn btn-orange w-100">
                                <i class="fa-solid fa-floppy-disk me-1"></i> حفظ بيانات التحويل
                            </button>
                        </div>
                    </form>
                    <small class="text-muted d-block mt-2">
                        <i class="fa-solid fa-circle-info me-1"></i> ستظهر هذه البيانات للمستخدم عند اختيار "تحويل بنكي / محفظة" في صفحة إتمام الدفع.
                    </small>
                </div>
            </div>

            <!-- كارت إضافة منتج جديد -->
            <div class="card admin-card mb-5">
                <div class="admin-card-header">
                    <h4 class="fw-bold mb-1"><i class="fa-solid fa-plus-circle me-2"></i>إضافة منتج جديد</h4>
                    <p class="small text-white-50 mb-0">أدخلي تفاصيل القطعة الجديدة لإضافتها إلى المتجر</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?= $message; ?>

                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">قسم المنتج:</label>
                            <select name="category" class="form-select" required>
                                <option value="" disabled selected>اختر القسم...</option>
                                <option value="نسائي">نسائي</option>
                                <option value="رجالي">رجالي</option>
                                <option value="أطفال">أطفال</option>
                                <option value="ميك أب">ميك أب</option>
                                <option value="إكسسوارات">إكسسوارات</option>
                                 <option value="شنط">شنط</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">اسم المنتج:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-tag"></i></span>
                                <input type="text" name="title" class="form-control" placeholder="مثال: فستان سهرة فاخر" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">كود المنتج (SKU) <span class="text-muted">- اتركه فارغاً للتوليد التلقائي</span>:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
                                <input type="text" name="product_code" class="form-control" placeholder="مثال: SKU-0001">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary small">السعر ($):</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                    <input type="number" step="0.01" name="price" class="form-control text-start" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary small">المقاس:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-ruler-vertical"></i></span>
                                    <select name="size" class="form-select">
                                        <option value="S">Small (S)</option>
                                        <option value="M" selected>Medium (M)</option>
                                        <option value="L">Large (L)</option>
                                        <option value="XL">X-Large (XL)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">الوصف:</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="أدخلي وصف المنتج والتفاصيل..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary small">صورة المنتج:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-image"></i></span>
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-orange w-100 fs-6">
                            <i class="fa-solid fa-upload me-2"></i> إضافة المنتج للمتجر
                        </button>
                    </form>
                </div>
            </div>

            <!-- كارت إدارة المنتجات والأرشيف -->
            <div class="card admin-card">
                <div class="admin-card-header bg-dark d-flex justify-content-between align-items-center px-4">
                    <h5 class="fw-bold mb-0 text-white"><i class="fa-solid fa-list-check me-2"></i>المنتجات المعروضة حالياً</h5>
                    <a href="archived_products.php" class="btn btn-warning btn-sm text-dark fw-bold">
                        <i class="fa-solid fa-box-archive me-1"></i> الذهاب للأرشيف
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>الصورة</th>
                                    <th>اسم المنتج</th>
                                    <th>الكود</th>
                                    <th>السعر</th>
                                    <th>المقاس</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_products as $product): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" width="50" height="50" class="rounded-3 object-fit-cover shadow-sm">
                                    </td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($product['title']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($product['product_code'] ?? '—'); ?></span></td>
                                    <td class="text-success fw-bold">$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($product['size']); ?></span></td>
                                    <td>
                                        <!-- زر الأرشيف متصل برابط admin.php?archive_id -->
                                        <a href="admin.php?archive_id=<?php echo $product['id']; ?>" 
                                           class="btn btn-warning btn-sm fw-bold shadow-sm" 
                                           onclick="return confirm('هل أنتِ متأكدة من نقل هذا المنتج للأرشيف؟ لن يظهر للمستخدمين بعد الآن.');">
                                            <i class="fa-solid fa-box-archive me-1"></i> أرشفة
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if (empty($active_products)): ?>
                                <tr>
                                    <td colspan="6" class="text-muted py-4">لا توجد منتجات معروضة في المتجر حالياً.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>