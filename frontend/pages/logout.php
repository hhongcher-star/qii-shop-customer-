<?php
require_once __DIR__ . '/../../app/customers.php';
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
qii_ensure_customer_tables($pdo);
qii_logout_customer($pdo);
header('Location: index.php');
exit;
?>
