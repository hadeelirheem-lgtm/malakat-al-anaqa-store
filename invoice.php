<?php
session_start();
require_once 'DB.php';

// التحقق من وجود رقم الطلب في الرابط
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("رقم الطلب غير محدد.");
}

$order_id = intval($_GET['id']);

// جلب تفاصيل الطلب من قاعدة البيانات
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die("الطلب غير موجود.");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة رقم #<?php echo $order['id']; ?> - ملكة الأناقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .invoice-card {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .invoice-header {
            border-bottom: 2px solid #f97316;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .table th {
            background-color: #fff3ed !important;
            color: #ea580c;
        }
        
        /* تنسيقات خاصة بالطباعة */
        @media print {
            body {
                background-color: #fff;
            }
            .no-print {
                display: none !important;
            }
            .invoice-card {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- أزرار الإجراءات (تختفي عند الطباعة) -->
    <div class="max-w-800 text-end my-3 no-print style-actions">
        <button onclick="window.print();" class="btn btn-warning fw-bold text-dark shadow-sm">
            <i class="fa-solid fa-print me-1"></i> طباعة الفاتورة / حفظ PDF
        </button>
        <button onclick="window.close();" class="btn btn-secondary fw-bold shadow-sm ms-2">
            إغلاق
        </button>
    </div>

    <!-- كرت الفاتورة -->
    <div class="invoice-card">
        <!-- ترويسة الفاتورة -->
        <div class="invoice-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-dark m-0"><i class="fa-solid fa-crown text-warning me-2"></i>ملكة الأناقة</h2>
                <small class="text-muted">متجر الأزياء والأناقة</small>
            </div>
            <div class="text-end">
                <h4 class="fw-bold text-primary m-0">فاتورة شراء</h4>
                <span class="badge bg-dark fs-6 mt-1">#<?php echo sprintf('%05d', $order['id']); ?></span>
            </div>
        </div>

        <!-- تفاصيل الزبون والطلب -->
        <div class="row mb-4">
            <div class="col-6">
                <h6 class="fw-bold text-muted mb-2">معلومات العميل:</h6>
                <p class="mb-1"><strong>الاسم:</strong> <?php echo htmlspecialchars($order['user_name'] ?? $order['name'] ?? 'زائر'); ?></p>
                <p class="mb-1"><strong>رقم الجوال:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                <p class="mb-1"><strong>العنوان:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
            </div>
            <div class="col-6 text-end">
                <h6 class="fw-bold text-muted mb-2">تفاصيل الفاتورة:</h6>
                <p class="mb-1"><strong>طريقة الدفع:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                <p class="mb-1"><strong>حالة الطلب:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
            </div>
        </div>

        <!-- جدول المنتجات -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>تفاصيل المنتجات المطلوبة</th>
                        <th class="text-center">المبلغ الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">1</td>
                        <td>
                            <span class="fw-bold"><?php echo htmlspecialchars($order['order_items']); ?></span>
                        </td>
                        <td class="text-center fw-bold text-danger">$<?php echo number_format($order['total_price'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ملخص الحساب -->
        <div class="row justify-content-end">
            <div class="col-6">
                <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between mb-2">
                        <span>المجموع الفرعي:</span>
                        <strong>$<?php echo number_format($order['total_price'], 2); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>التوصيل:</span>
                        <strong class="text-success">مجاني / شامل</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold">
                        <span>الإجمالي الكلي:</span>
                        <span class="text-danger">$<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- التذييل -->
        <div class="text-center mt-5 pt-4 border-top text-muted small">
            <p class="mb-1">شكراً لتسوقك من متجر <strong>ملكة الأناقة</strong>!</p>
            <p class="m-0">في حال وجود أي استفسار، يرجى التواصل معنا عبر الهاتف أو المتجر مباشرة.</p>
        </div>
    </div>
</div>

</body>
</html>