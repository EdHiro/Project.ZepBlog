<?php
// 数据库配置
define('DB_PATH', __DIR__ . '/db.db');

// 确保数据库文件存在
if (!file_exists(DB_PATH)) {
    file_put_contents(DB_PATH, '');
}

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 检查并创建表结构
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        $db->exec(file_get_contents(__DIR__ . '/database.sql'));
    }
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 会话配置
session_start();

// 网站基本配置
try {
    $siteSettings = $db->query("SELECT site_name, site_description, timezone FROM site_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    define('SITE_NAME', $siteSettings['site_name'] ?? 'ZTC博客');
    define('SITE_URL', 'http://localhost');
    define('SITE_DESCRIPTION', $siteSettings['site_description'] ?? '我的个人博客');
    // 设置默认时区
define('SITE_TIMEZONE', $siteSettings['timezone'] ?? 'Asia/Shanghai');
date_default_timezone_set('UTC');
} catch (PDOException $e) {
    define('SITE_NAME', 'ZTC博客');
    define('SITE_URL', 'http://localhost');
    define('SITE_DESCRIPTION', '我的个人博客');
    define('SITE_TIMEZONE', 'Asia/Shanghai');
    date_default_timezone_set('UTC');
}
?>