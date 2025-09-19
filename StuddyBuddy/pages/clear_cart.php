<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();
check_csrf();

unset($_SESSION['cart']);
flash('ok', 'Cart cleared.');
header('Location: cart.php');
