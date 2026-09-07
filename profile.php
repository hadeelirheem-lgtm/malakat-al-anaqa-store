<?php
session_start();
require_once 'DB.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_email'] ?? '';
$message = '';
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$current_theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// جلب بيانات المستخدم الحالية من قاعدة البيانات مباشرة
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch();

$user_name = $user['name'] ?? '';
$user_role = $user['role'] ?? 'user';
$user_image = !empty($user['profile_image']) ? $user['profile_image'] : null;

// معالجة تحديث البيانات عند إرسال الفورم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_password = trim($_POST['password']);
    
    // مسار حفظ الصورة التلقائي
    $image_path = $user_image;

    // معالجة رفع الصورة الشخصية جديدة عند الضغط على الدائرة
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $imageName = $_FILES['profile_image']['name'];
        $imageTmp = $_FILES['profile_image']['tmp_name'];
        
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        $targetFile = "uploads/user_" . time() . '_' . basename($imageName);

        if (move_uploaded_file($imageTmp, $targetFile)) {
            $image_path = $targetFile;
        }
    }

    if (!empty($new_name)) {
        if (!empty($new_password)) {
            // تحديث الاسم، كلمة المرور (مشفرة)، والصورة
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ?, profile_image = ? WHERE email = ?");
            $success = $stmt->execute([$new_name, $hashed, $image_path, $user_email]);
        } else {
            // تحديث الاسم والصورة فقط
            $stmt = $pdo->prepare("UPDATE users SET name = ?, profile_image = ? WHERE email = ?");
            $success = $stmt->execute([$new_name, $image_path, $user_email]);
        }

        if ($success) {
            $_SESSION['user_name'] = $new_name;
            $user_name = $new_name;
            $user_image = $image_path;
            $message = "<div class='alert alert-success text-center'>تم تحديث البيانات والصورة بنجاح!</div>";
        } else {
            $message = "<div class='alert alert-danger text-center'>حدث خطأ أثناء التحديث.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المتجر الذكي - الملف الشخصي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .avatar-upload {
            position: relative;
            max-width: 140px;
            margin: 0 auto 15px auto;
        }
        .avatar-upload .avatar-edit {
            position: absolute;
            right: 5px;
            z-index: 1;
            bottom: 5px;
        }
        .avatar-upload .avatar-edit input {
            display: none;
        }
        .avatar-upload .avatar-edit label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            margin-bottom: 0;
            border-radius: 100%;
            background: linear-gradient(135deg, #f97316, #ea580c);
            border: 2px solid #ffffff;
            box-shadow: 0px 2px 4px 0px rgba(234, 88, 12, 0.3);
            cursor: pointer;
            color: #fff;
            transition: all 0.2s ease-in-out;
        }
        .avatar-upload .avatar-edit label:hover {
            background: #c2410c;
            transform: scale(1.08);
        }
        .avatar-preview {
            width: 130px;
            height: 130px;
            position: relative;
            border-radius: 100%;
            border: 4px solid #f97316;
            box-shadow: 0px 2px 8px 0px rgba(234, 88, 12, 0.25);
            cursor: pointer;
            overflow: hidden;
            background-color: #fff7ed;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .btn-orange-profile {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            transition: all 0.25s ease;
        }
        .btn-orange-profile:hover {
            color: #fff;
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(234, 88, 12, 0.3);
        }
        .profile-card {
            border: none;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(234, 88, 12, 0.15);
        }
        .profile-header {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            border-radius: 24px 24px 0 0;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<!-- ستايل القائمة الجانبية باللون البرتقالي الفاخر -->
<style>
    :root {
        --sidebar-width: 260px;
    }

    body {
        padding-right: var(--sidebar-width); /* إفساح مجال للقائمة الجانبية جهة اليمين */
        transition: all 0.3s ease;
    }

    /* القائمة الجانبية الثابتة */
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
        font-size: 1.5rem;
        font-weight: bold;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 15px;
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

    /* للتوافق مع الشاشات الصغيرة (الهواتف) */
    @media (max-width: 991px) {
        body {
            padding-right: 0;
        }
        .sidebar-nav {
            position: relative;
            width: 100%;
            height: auto;
        }
    }
</style>

<!-- القائمة الجانبية -->
<?php $active_page = 'profile'; include 'sidebar.php'; ?>

<div class="container my-5 col-md-6">
    <div class="card profile-card">
        <div class="card-header profile-header text-white text-center py-3 border-0">
            <h4 class="mb-0"><i class="fa-solid fa-id-card me-2"></i>الملف الشخصي</h4>
        </div>
        
        <div class="card-body p-4">
            <?php echo $message; ?>
            
            <form method="POST" enctype="multipart/form-data">
                
                <!-- زر رفع المعاينة في الدائرة نفسها -->
                <div class="avatar-upload">
                    <div class="avatar-edit">
                        <input type="file" id="imageUpload" name="profile_image" accept=".png, .jpg, .jpeg" />
                        <label for="imageUpload" title="تغيير الصورة"><i class="fa-solid fa-camera"></i></label>
                    </div>
                    <div class="avatar-preview" onclick="document.getElementById('imageUpload').click();">
                        <?php if ($user_image): ?>
                            <img id="imagePreview" src="<?php echo htmlspecialchars($user_image); ?>" alt="Profile">
                        <?php else: ?>
                            <i id="defaultIcon" class="fa-solid fa-user fa-5x text-secondary"></i>
                            <img id="imagePreview" src="" alt="Profile" style="display: none;">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mb-4">
                    <h3 class="mt-1 mb-0 fw-bold text-dark"><?php echo htmlspecialchars($user_name); ?></h3>
                    <span class="badge bg-<?php echo $user_role === 'admin' ? 'danger' : 'warning'; ?> mt-1 text-<?php echo $user_role === 'admin' ? '' : 'dark'; ?>">
                        <?php echo $user_role === 'admin' ? 'مدير النظام (Admin)' : 'مستخدم عادي'; ?>
                    </span>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">البريد الإلكتروني (غير قابل للتعديل):</label>
                    <div class="input-group">
                        <input type="email" class="form-control text-start" value="<?php echo htmlspecialchars($user_email); ?>" disabled>
                        <span class="input-group-text"><i class="fa-solid fa-envelope text-warning"></i></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">الاسم الكامل:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user text-warning"></i></span>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">كلمة المرور الجديدة (اتركيها فارغة إذا لا تريدين تغييرها):</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock text-warning"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-orange-profile w-100 py-2 fw-bold">
                    <i class="fa-solid fa-save me-1"></i> حفظ التعديلات
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // تغيير الصورة ومعاينتها فوراً عند الضغط على الدائرة واختيار صورة
    document.getElementById('imageUpload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgPreview = document.getElementById('imagePreview');
                const defaultIcon = document.getElementById('defaultIcon');
                
                imgPreview.src = e.target.result;
                imgPreview.style.display = 'block';
                if (defaultIcon) {
                    defaultIcon.style.display = 'none';
                }
            }
            reader.readAsDataURL(file);
        }
    });
</script>

</body>
</html>