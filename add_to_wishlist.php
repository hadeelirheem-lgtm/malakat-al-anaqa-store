<?php
session_start();

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }

    if (isset($_SESSION['wishlist'][$product_id])) {
        unset($_SESSION['wishlist'][$product_id]);
    } else {
        $_SESSION['wishlist'][$product_id] = 1;
    }
}

header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.php'));
exit();
?>