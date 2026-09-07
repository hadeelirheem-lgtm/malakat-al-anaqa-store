<?php
session_start();
require_once 'DB.php'; // استدعاء الاتصال بقاعدة البيانات

$saved_email = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['email-remember']);

    if (!empty($email) && !empty($password)) {

        // 1. الاستعلام عن المستخدم من قاعدة البيانات
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 2. التحقق من وجود المستخدم وصحة كلمة المرور
        if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {

            // معالجة الـ Cookie
            if ($remember) {
                setcookie('remember_email', $email, time() + (86400 * 30), "/");
                $cookie_counter = isset($_COOKIE['login_count']) ? (int)$_COOKIE['login_count'] + 1 : 1;
                setcookie('login_count', $cookie_counter, time() + (86400 * 365), "/");
                $_SESSION['login_count'] = $cookie_counter;
            } else {
                setcookie('remember_email', '', time() - 3600, "/");
                setcookie('login_count', '', time() - 3600, "/");
                $_SESSION['login_count'] = isset($_SESSION['login_count']) ? $_SESSION['login_count'] + 1 : 1;
            }

            // 3. حفظ بيانات المستخدم ودوره في السيشن
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_login'] = date('Y-m-d H:i:s');
            $_SESSION['is_logged_in'] = true;

            // 4. التوجيه بناءً على الرتبة
            if ($user['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit();

        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة!';
        }
    } else {
        $error = 'يرجى إدخال جميع البيانات المطلوبة!';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - ملكة الأناقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* خلفية متموجة ومتحركة باللون الأبيض والبرتقالي الهادئ */
        body {
            background: linear-gradient(-45deg, #fffaf0, #ffedd5, #fef3c7, #fff5eb);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(217, 119, 6, 0.15);
            background: #ffffff;
        }

        /* القسم المخصص للفيديو بألوان برتقالية فاخرة */
        .brand-side {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .brand-side video {
            width: 100%;
            max-height: 380px;
            object-fit: contain;
            border-radius: 15px;
            filter: drop-shadow(0px 10px 20px rgba(0, 0, 0, 0.15));
        }

        .form-side {
            padding: 45px 40px;
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

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card login-card">
                <div class="row g-0">
                    
                    <!-- القسم الأيمن: فيديو المنتجات بالخلفية البرتقالية -->
                    <div class="col-md-6 brand-side text-center">
                        <div class="mb-2">
                            <span class="badge bg-white text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                                <i class="fa-solid fa-crown text-warning me-1"></i> ملكة الأناقة
                            </span>
                        </div>

                        <!-- إدراج الفيديو -->
                        <video autoplay loop muted playsinline class="my-2">
                            <source src="assets/videos/login-video.mp4" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو.
                        </video>

                        <h4 class="fw-bold text-white mt-2">تسوقي بأناقة ومميزات حصريّة</h4>
                        <p class="text-white-50 small mb-0">تشكيلة مميزة من الأزياء والإكسسوارات الفاخرة</p>
                    </div>

                    <!-- القسم الأيسر: نموذج تسجيل الدخول -->
                    <div class="col-md-6 form-side">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark">أهلاً بكِ مجدداً! ✨</h3>
                            <p class="text-muted small">يرجى إدخال بياناتك لتسجيل الدخول إلى حسابك</p>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger text-center py-2 rounded-3 small border-0 shadow-sm mb-3">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary small">البريد الإلكتروني:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control text-start" value="<?php echo htmlspecialchars($saved_email); ?>" placeholder="name@example.com" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary small">كلمة المرور:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('loginPassword', this)" tabindex="-1"><i class="fa-regular fa-eye"></i></button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input type="checkbox" name="email-remember" class="form-check-input" id="remember" <?php echo $saved_email ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-secondary small" for="remember">تذكرني </label>
                                </div>

                            </div>
                             <div class="text-end pt-1">
                             <a href="forgot-password.php" class="fw-bold text-warning small ms-1">نسيت كلمة المرور؟</a>
                        </div>

                            <button type="submit" class="btn btn-warning-custom w-100 mb-3">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> دخول للحساب
                            </button>
                        </form>

                        <div class="text-center border-top pt-3">
                            <span class="text-muted small">ليس لديكِ حساب؟</span>
                            <a href="register.php" class="fw-bold text-warning small ms-1">أنشئي حسابكِ الآن</a>
                        </div>
                    </div>

                </div>
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