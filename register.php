<?php
session_start();
require_once 'DB.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($name) && !empty($email) && !empty($password)) {
        
        // 1. التأكد من أن البريد غير مستخدم سابقاً
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "البريد الإلكتروني مستخدم بالفعل!";
        } else {
            // 2. تشفير كلمة المرور وإدخال المستخدم الجديد في قاعدة البيانات (دور افتراضي: user)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
            
            if ($stmt->execute([$name, $email, $hashed_password])) {
                $success = "تم إنشاء الحساب بنجاح! يمكنكِ الآن تسجيل الدخول.";
            } else {
                $error = "حدث خطأ أثناء إنشاء الحساب، حاول مرة أخرى.";
            }
        }
    } else {
        $error = "يرجى تعبئة جميع الحقول المطلوبة.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد - ملكة الأناقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* خلفية برتقالية فاتحة متحركة (نفس صفحة تسجيل الدخول) */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #fffaf0, #ffedd5, #fef3c7, #fff5eb);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* عناصر متحركة عائمة (أيقونات أزياء ترتفع للأعلى) */
        .bg-float {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            overflow: hidden;
            pointer-events: none;
        }

        .bg-float i {
            position: absolute;
            bottom: -70px;
            opacity: 0;
            color: rgba(234, 88, 12, 0.55);
            text-shadow: 0 0 18px rgba(251, 146, 60, 0.9);
            animation: floatUp linear infinite;
        }

        .bg-float i:nth-child(1) { left: 8%;  animation-duration: 11s; animation-delay: 0s;   font-size: 2.2rem; color: rgba(234, 88, 12, 0.75); }
        .bg-float i:nth-child(2) { left: 22%; animation-duration: 14s; animation-delay: 1.5s; font-size: 1.5rem; }
        .bg-float i:nth-child(3) { left: 37%; animation-duration: 12s; animation-delay: 3s;   font-size: 2rem; color: rgba(251, 146, 60, 0.9); }
        .bg-float i:nth-child(4) { left: 55%; animation-duration: 15s; animation-delay: 0.8s; font-size: 1.8rem; }
        .bg-float i:nth-child(5) { left: 70%; animation-duration: 13s; animation-delay: 2.5s; font-size: 1.6rem; }
        .bg-float i:nth-child(6) { left: 86%; animation-duration: 16s; animation-delay: 4s;   font-size: 2.4rem; color: rgba(234, 88, 12, 0.75); }

        @keyframes floatUp {
            0%   { transform: translateY(0) rotate(0deg); opacity: 0; }
            10%  { opacity: 0.9; }
            100% { transform: translateY(-115vh) rotate(45deg); opacity: 0; }
        }

        .register-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(217, 119, 6, 0.15);
            background: #ffffff;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
        }

        .form-control:focus {
            border-color: #ea580c;
            box-shadow: 0 0 0 0.25rem rgba(234, 88, 12, 0.15);
        }

        .btn-warning-custom {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            font-size: 1.05rem;
            transition: all 0.3s ease;
        }

        .btn-warning-custom:hover {
            background: linear-gradient(135deg, #ea580c, #c2410c);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(234, 88, 12, 0.3);
        }

        .input-group-text {
            border-radius: 10px;
            background-color: #fff7ed;
            border-color: #cbd5e1;
            color: #ea580c;
        }
    </style>
</head>
<body>

<div class="bg-float" aria-hidden="true">
    <i class="fa-solid fa-shirt"></i>
    <i class="fa-solid fa-heart"></i>
    <i class="fa-solid fa-crown"></i>
    <i class="fa-solid fa-bag-shopping"></i>
    <i class="fa-solid fa-gem"></i>
    <i class="fa-solid fa-sparkles"></i>
</div>

<div class="container my-5 col-md-6 col-lg-5 col-xl-4" style="position: relative; z-index: 3;">
    <div class="card register-card">
        
        <!-- الهيدر البرتقالي الفاخر -->
        <div class="card-header-custom">
            <span class="badge bg-white text-dark px-3 py-2 rounded-pill fw-bold shadow-sm mb-2">
                <i class="fa-solid fa-crown text-warning me-1"></i> ملكة الأناقة
            </span>
            <h4 class="fw-bold mb-1">إنشاء حساب جديد</h4>
            <p class="text-white-50 small mb-0">انضمي إلينا لتجربة تسوق فريدة ومميزة</p>
        </div>

        <div class="card-body p-4">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center py-2 rounded-3 small border-0 shadow-sm mb-3">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success text-center py-2 rounded-3 small border-0 shadow-sm mb-3">
                    <i class="fa-solid fa-circle-check me-1"></i> <?php echo $success; ?>
                    <br><a href="login.php" class="fw-bold text-success text-decoration-underline mt-1 d-inline-block">تسجيل الدخول الآن</a>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small">الاسم الكامل:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="name" class="form-control" placeholder="ادخل اسمك..." required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small">البريد الإلكتروني:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control text-start" placeholder="name@example.com" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary small">كلمة المرور:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" id="regPassword" class="form-control" placeholder="••••••••" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('regPassword', this)" tabindex="-1"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>

                <button type="submit" class="btn btn-warning-custom w-100 mb-3">
                    <i class="fa-solid fa-user-plus me-2"></i> إنشاء الحساب
                </button>
            </form>

            <div class="text-center border-top pt-3">
                <span class="text-muted small">لديكِ حساب بالفعل؟</span>
                <a href="login.php" class="fw-bold text-warning small ms-1">تسجيل الدخول</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword(inputId, btn) {
        var input = document.getElementById(inputId);
        var icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-regular', 'fa-eye');
            icon.classList.add('fa-regular', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-regular', 'fa-eye-slash');
            icon.classList.add('fa-regular', 'fa-eye');
        }
    }
</script>
</body>
</html>