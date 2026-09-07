<?php
session_start();
require_once 'DB.php';

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$current_theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

$success_msg = '';
$error_msg = '';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// معالجة رد الأدمن على رأي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    if (!$is_admin) {
        $error_msg = "غير مصرح لك بالرد على الآراء.";
    } else {
        $review_id = intval($_POST['review_id'] ?? 0);
        $reply = trim($_POST['admin_reply'] ?? '');

        if ($review_id <= 0) {
            $error_msg = "رأي غير صالح.";
        } elseif (empty($reply)) {
            $error_msg = "يرجى كتابة الرد قبل النشر.";
        } else {
            $rep = $pdo->prepare("UPDATE reviews SET admin_reply = :reply WHERE id = :id");
            if ($rep->execute(['reply' => $reply, 'id' => $review_id])) {
                $success_msg = "تم نشر ردك على رأي العميلة بنجاح ✨";
            } else {
                $error_msg = "حدث خطأ أثناء حفظ الرد، يرجى المحاولة لاحقاً.";
            }
        }
    }
}

// معالجة حذف الرأي (للأدمن فقط)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    if (!$is_admin) {
        $error_msg = "غير مصرح لك بحذف الآراء.";
    } else {
        $review_id = intval($_POST['review_id'] ?? 0);
        if ($review_id <= 0) {
            $error_msg = "رأي غير صالح.";
        } else {
            $del = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
            if ($del->execute(['id' => $review_id])) {
                $success_msg = "تم حذف الرأي بنجاح.";
                header("Location: reviews.php");
                exit();
            } else {
                $error_msg = "حدث خطأ أثناء حذف الرأي.";
            }
        }
    }
}

// معالجة حذف الرد (للأدمن فقط)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reply'])) {
    if (!$is_admin) {
        $error_msg = "غير مصرح لك بحذف الرد.";
    } else {
        $review_id = intval($_POST['review_id'] ?? 0);
        if ($review_id > 0) {
            $del = $pdo->prepare("UPDATE reviews SET admin_reply = NULL WHERE id = :id");
            if ($del->execute(['id' => $review_id])) {
                $success_msg = "تم حذف الرد بنجاح.";
                header("Location: reviews.php");
                exit();
            }
        }
    }
}

// معالجة إضافة رأي جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
        $error_msg = "يرجى تسجيل الدخول أولاً لتتمكن من إضافة تقييمك.";
    } else {
        $user_name = trim($_SESSION['user_name'] ?? 'عميل أنيق');
        $rating = intval($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        // جلب صورة المستخدم الحالية إن وُجدت
        $profile_image = null;
        $user_email = $_SESSION['user_email'] ?? '';
        if ($user_email) {
            $pu = $pdo->prepare("SELECT profile_image FROM users WHERE email = :email");
            $pu->execute(['email' => $user_email]);
            $urow = $pu->fetch();
            if ($urow && !empty($urow['profile_image'])) {
                $profile_image = $urow['profile_image'];
            }
        }

        if ($rating < 1 || $rating > 5) {
            $error_msg = "يرجى تحديد تقييم صحيح بالنجوم.";
        } elseif (empty($comment)) {
            $error_msg = "يرجى كتابة رأيك في المتجر.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO reviews (user_name, rating, comment, profile_image) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$user_name, $rating, $comment, $profile_image])) {
                $success_msg = "شكراً لك! تم نشر رأيك بنجاح ✨";
            } else {
                $error_msg = "حدث خطأ أثناء حفظ التقييم، يرجى المحاولة لاحقاً.";
            }
        }
    }
}

// جلب جميع الآراء من قاعدة البيانات (الأحدث أولاً)
$stmt_reviews = $pdo->query("SELECT * FROM reviews ORDER BY id DESC");
$reviews = $stmt_reviews->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ماذا قالوا عنا | ملكة الأناقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root { --sidebar-width: 260px; }
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

        .bg-shape { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.35; animation: float 8s ease-in-out infinite alternate; z-index: 0; }
        .shape-1 { width: 320px; height: 320px; background: #fb923c; top: -40px; left: -40px; }
        .shape-2 { width: 280px; height: 280px; background: #ea580c; bottom: -40px; right: var(--sidebar-width); animation-delay: -4s; }

        @keyframes float { 0% { transform: translateY(0) scale(1); } 100% { transform: translateY(30px) scale(1.08); } }

        /* القائمة الجانبية Sidebar */
        .sidebar-nav {
            position: fixed; top: 0; right: 0; width: var(--sidebar-width); height: 100vh;
            background: linear-gradient(180deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
            color: #ffffff; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; padding: 25px 15px;
            box-shadow: -5px 0 25px rgba(234, 88, 12, 0.2);
        }
        .sidebar-brand { color: #ffffff; font-size: 1.4rem; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 12px 15px; background: rgba(255, 255, 255, 0.15); border-radius: 12px; backdrop-filter: blur(5px); }
        .sidebar-menu { list-style: none; padding: 0; margin: 30px 0 0 0; display: flex; flex-direction: column; gap: 8px; }
        .sidebar-link { color: rgba(255, 255, 255, 0.9); text-decoration: none; padding: 12px 18px; border-radius: 10px; display: flex; align-items: center; gap: 12px; font-weight: 600; transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: #ffffff; color: #ea580c; transform: translateX(-5px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .sidebar-footer { border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 15px; }

        /* ستايل البطاقات */
        .custom-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(234, 88, 12, 0.08);
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 1.8rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }

        .review-card {
            transition: all 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(234, 88, 12, 0.15);
        }

        .avatar-circle {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            overflow: hidden;
            flex-shrink: 0;
        }
        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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
<?php $active_page = 'reviews'; include 'sidebar.php'; ?>

<!-- المحتوى الرئيسي -->
<div class="container my-5" style="position: relative; z-index: 2;">

    <div class="text-center mb-4">
        <h2 class="fw-bold text-dark"><i class="fa-solid fa-quote-right text-warning me-2"></i> ماذا قالوا عنا؟</h2>
        <p class="text-secondary">شاركي آرائك وتقييماتك لمتجرنا لمساعدة زبائناتنا الأنيقات!</p>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success rounded-3 fs-6 p-2 mb-4"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger rounded-3 fs-6 p-2 mb-4"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- نموذج كتابة التقييم (عريض في الأعلى) -->
    <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
        <div class="card custom-card p-4 mb-5">
            <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> اتركِ رأيك</h4>
            <form method="POST" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-secondary">الاسم:</label>
                    <input type="text" class="form-control rounded-3" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-secondary">التقييم بالنجوم:</label>
                    <div class="star-rating pt-1">
                        <input type="radio" id="star5" name="rating" value="5" checked><label for="star5" title="5 نجوم"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 نجوم"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 نجوم"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 نجوم"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="نجمة واحدة"><i class="fa-solid fa-star"></i></label>
                    </div>
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" name="submit_review" class="btn btn-warning text-white fw-bold py-2 rounded-3 shadow-sm">
                        <i class="fa-solid fa-paper-plane me-1"></i> نشر التقييم
                    </button>
                </div>
                <div class="col-12">
                    <label for="comment" class="form-label fw-bold small text-secondary">رأيك أو تجربتك:</label>
                    <textarea name="comment" id="comment" rows="3" class="form-control rounded-3" placeholder="اكتبي تجربتك معنا بكل أمانة..." required></textarea>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- عرض قائمة الآراء والتقييمات -->
    <?php if (empty($reviews)): ?>
        <div class="card custom-card p-5 text-center">
            <i class="fa-solid fa-comments text-muted fs-1 mb-3"></i>
            <h5 class="text-secondary fw-bold">لا يوجد تقييمات حتى الآن</h5>
            <p class="text-muted small"><?php echo ($is_admin ? 'لا توجد آراء لعرضها.' : 'كوني الأولى في مشاركة رأيك وتجربتك معنا!'); ?></p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($reviews as $rev): ?>
                <div class="card custom-card review-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-circle">
                                <?php if (!empty($rev['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($rev['profile_image']); ?>" alt="<?php echo htmlspecialchars($rev['user_name']); ?>">
                                <?php else: ?>
                                    <?php echo mb_substr(htmlspecialchars($rev['user_name']), 0, 1, 'UTF-8'); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($rev['user_name']); ?></h6>
                                <small class="text-muted fs-7"><?php echo date('Y-m-d', strtotime($rev['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="text-warning">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $rev['rating']): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star text-muted"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <p class="text-secondary mb-0 px-2 mt-2" style="white-space: pre-line;">
                        "<?php echo htmlspecialchars($rev['comment']); ?>"
                    </p>

                    <?php if (!empty($rev['admin_reply'])): ?>
                        <div class="bg-warning-subtle rounded-3 p-3 mt-3 ms-2 me-2" style="border-inline-start: 4px solid #f97316;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid fa-crown text-warning"></i>
                                <span class="fw-bold text-dark small">رد إدارة ملكة الأناقة:</span>
                            </div>
                            <p class="mb-0 text-secondary ps-1" style="white-space: pre-line;"><?php echo htmlspecialchars($rev['admin_reply']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_admin): ?>
                        <hr class="my-3">
                        <div class="px-1">
                            <h6 class="fw-bold text-dark small mb-2"><i class="fa-solid fa-reply text-warning me-1"></i> رد إدارة الموقع:</h6>
                            <form method="POST" class="row g-2 align-items-end">
                                <div class="col">
                                    <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                                    <textarea name="admin_reply" rows="2" class="form-control form-control-sm rounded-3"
                                              placeholder="<?php echo empty($rev['admin_reply']) ? 'اكتبي ردك على هذه العميلة...' : ''; ?>"><?php echo htmlspecialchars($rev['admin_reply'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" name="submit_reply" class="btn btn-warning text-white fw-bold btn-sm rounded-3 px-3">
                                        <i class="fa-solid fa-check me-1"></i> نشر الرد
                                    </button>
                                </div>
                            </form>

                            <div class="d-flex gap-2 mt-3 flex-wrap">
                                <?php if (!empty($rev['admin_reply'])): ?>
                                    <form method="POST" onsubmit="return confirm('هل تريد حذف هذا الرد؟');">
                                        <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                                        <button type="submit" name="delete_reply" class="btn btn-sm btn-outline-warning rounded-3">
                                            <i class="fa-solid fa-trash-can me-1"></i> حذف الرد
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف رأي العميلة نهائياً؟');">
                                    <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                                    <button type="submit" name="delete_review" class="btn btn-sm btn-outline-danger rounded-3">
                                        <i class="fa-solid fa-trash-can me-1"></i> حذف الرأي
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>