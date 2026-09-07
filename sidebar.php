<?php
if (!isset($cart_count)) {
    $cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
}
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
?>

<style>
    /* ===== سلوك القائمة على الجوال (أصغر من 992px) ===== */
    .sidebar-toggle-btn {
        display: none;
        position: fixed;
        top: 12px;
        right: 12px;
        z-index: 2000;
        width: 48px;
        height: 48px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: #fff;
        font-size: 1.3rem;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(234, 88, 12, 0.4);
    }
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1499;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sidebar-overlay.show { opacity: 1; }

    @media (max-width: 991.98px) {
        .sidebar-toggle-btn { display: flex; align-items: center; justify-content: center; }
        body { padding-right: 0 !important; }
        .sidebar-nav {
            position: fixed !important;
            top: 0;
            right: -280px;
            width: 260px !important;
            height: 100vh !important;
            z-index: 1500;
            transition: right 0.3s ease;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.25);
            overflow-y: auto;
        }
        .sidebar-nav.open { right: 0 !important; }
        .main-content { margin-right: 0 !important; }
    }
</style>

<!-- زر القائمة للجوال -->
<button type="button" class="sidebar-toggle-btn" onclick="toggleSidebar(true)" aria-label="القائمة">
    <i class="fa-solid fa-bars"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar-nav" id="sidebarNav">
    <div>
        <!-- زر إغلاق للجوال -->
        <button type="button" class="sidebar-close-btn" onclick="closeSidebar()" aria-label="إغلاق">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <a href="index.php" class="sidebar-brand">
            <i class="fa-solid fa-crown text-warning fs-3"></i>
            <span>ملكة الأناقة</span>
        </a>

        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link<?php echo ($active_page ?? '') === 'index' ? ' active' : ''; ?>"><i class="fa-solid fa-house"></i> الرئيسية</a></li>
            <li><a href="products.php" class="sidebar-link<?php echo ($active_page ?? '') === 'products' ? ' active' : ''; ?>"><i class="fa-solid fa-bag-shopping"></i> المنتجات</a></li>
            <li><a href="reviews.php" class="sidebar-link<?php echo ($active_page ?? '') === 'reviews' ? ' active' : ''; ?>"><i class="fa-solid fa-comments"></i> آراء العملاء</a></li>

            <?php if (!$is_admin): ?>
                <li>
                    <a href="cart.php" class="sidebar-link<?php echo ($active_page ?? '') === 'cart' ? ' active' : ''; ?>">
                        <i class="fa-solid fa-cart-shopping"></i> السلة
                        <span class="badge bg-white text-danger rounded-pill ms-auto fw-bold"><?php echo $cart_count; ?></span>
                    </a>
                </li>
                <li>
                    <a href="wishlist.php" class="sidebar-link<?php echo ($active_page ?? '') === 'wishlist' ? ' active' : ''; ?>">
                        <i class="fa-solid fa-heart"></i> المفضلة
                        <?php if ($wishlist_count > 0): ?>
                            <span class="badge bg-white text-danger rounded-pill ms-auto fw-bold"><?php echo $wishlist_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($is_admin): ?>
                <li class="mt-3 border-top pt-3"><span class="text-white-50 small fw-bold px-3">لوحة التحكم:</span></li>
                <li><a href="admin.php" class="sidebar-link<?php echo ($active_page ?? '') === 'admin' ? ' active' : ''; ?>"><i class="fa-solid fa-sliders"></i> إدارة المنتجات</a></li>
                <li><a href="admin_orders.php" class="sidebar-link<?php echo ($active_page ?? '') === 'admin_orders' ? ' active' : ''; ?>"><i class="fa-solid fa-boxes-packing"></i> طلبات الشراء</a></li>
                <li><a href="admin_coupons.php" class="sidebar-link<?php echo ($active_page ?? '') === 'admin_coupons' ? ' active' : ''; ?>"><i class="fa-solid fa-ticket"></i> اكواد الخصم</a></li>
                <li><a href="archived_products.php" class="sidebar-link<?php echo ($active_page ?? '') === 'archived' ? ' active' : ''; ?>"><i class="fa-solid fa-box-archive"></i> المنتجات المؤرشفة</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="sidebar-footer">
        <?php if ($logged_in): ?>
            <div class="d-flex flex-column gap-2">
                <a href="profile.php" class="sidebar-link">
                    <i class="fa-solid fa-circle-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'حسابي'); ?></span>
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm w-100 fw-bold rounded-3 py-2">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> تسجيل خروج
                </a>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn btn-light w-100 fw-bold rounded-3 py-2 text-dark">
                <i class="fa-solid fa-right-to-bracket text-warning me-1"></i> تسجيل الدخول
            </a>
        <?php endif; ?>
    </div>
</aside>

<style>
    .sidebar-close-btn {
        display: none;
        position: absolute;
        top: 12px;
        left: 12px;
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        font-size: 1rem;
        cursor: pointer;
    }
    @media (max-width: 991.98px) {
        .sidebar-close-btn { display: flex; align-items: center; justify-content: center; }
    }
</style>

<script>
    function toggleSidebar(open) {
        const nav = document.getElementById('sidebarNav');
        const overlay = document.getElementById('sidebarOverlay');
        if (nav) {
            if (open === undefined) open = !nav.classList.contains('open');
            if (open) { nav.classList.add('open'); if (overlay) overlay.classList.add('show'); }
            else { nav.classList.remove('open'); if (overlay) overlay.classList.remove('show'); }
        }
    }
    function closeSidebar() { toggleSidebar(false); }
    document.addEventListener('DOMContentLoaded', function () {
        // إغلاق القائمة عند الضغط على أي رابط داخلي
        document.querySelectorAll('#sidebarNav .sidebar-link').forEach(function (l) {
            l.addEventListener('click', function () { closeSidebar(); });
        });
    });
</script>
