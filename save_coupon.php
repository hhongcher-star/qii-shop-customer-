<?php
session_start();

// Accept discount and store in session for current order only
if (isset($_POST['discount'])) {
    $discount = floatval($_POST['discount']);
    if ($discount < 0) $discount = 0;

    // Bind discount to the current order if available
    if (!empty($_SESSION['current_order'])) {
        $ord = $_SESSION['current_order'];
        if (!isset($_SESSION['coupon_discount']) || !is_array($_SESSION['coupon_discount'])) {
            $_SESSION['coupon_discount'] = [];
        }
        $_SESSION['coupon_discount'][$ord] = $discount;
    } else {
        // Fallback: global (not recommended but safe fallback)
        $_SESSION['coupon_discount'] = $discount;
    }

    header('Content-Type: text/plain; charset=UTF-8');
    echo "saved";
    exit;
}

http_response_code(400);
header('Content-Type: text/plain; charset=UTF-8');
echo "missing discount";
?>
