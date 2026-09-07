<?php
session_start();

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
}

// التوجيه بعد الإضافة: زر القلب (stay=1) يضيف ويبقى في نفس الصفحة
if (isset($_GET['stay'])) {
    header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.php'));
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.php'));
} else {
    header("Location: cart.php");
}
exit();
?>