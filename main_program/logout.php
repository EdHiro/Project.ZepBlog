<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$auth->logout();
header('Location: login.php');
exit;
?>