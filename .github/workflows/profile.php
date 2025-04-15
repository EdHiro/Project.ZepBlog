<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/content.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_GET['id'];
$user = $auth->getUserById($user_id);
$contentObj = new Content($db, $auth);
$user_posts = $contentObj->getPostsByUser($user_id);
$user_videos = $contentObj->getVideosByUser($user_id);

$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $auth->isLoggedIn() && $_SESSION['user_id'] == $user_id) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    if (empty($username) || empty($email)) {
        $error = '用户名和邮箱不能为空';
    } else {
        if ($auth->updateUser($user_id, $username, $email)) {
            $success = '个人资料已更新';
            $_SESSION['username'] = $username;
            $user = $auth->getUserById($user_id); // 刷新用户信息
        } else {
            $error = '更新失败，请重试';
        }
    }
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_GET['id'];
$user = $auth->getUserById($user_id);

if (!$user) {
    header('Location: index.php');
    exit;
}

$is_following = false;
if ($auth->isLoggedIn()) {
    $current_user_id = $_SESSION['user_id'];
    $is_following = $auth->isFollowing($current_user_id, $user_id);
}

$follower_count = $auth->getFollowerCount($user_id);
$following_count = $auth->getFollowingCount($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $auth->isLoggedIn()) {
    $action = $_POST['action'] ?? '';
    $current_user_id = $_SESSION['user_id'];
    
    if ($action === 'follow') {
        $auth->followUser($current_user_id, $user_id);
        $is_following = true;
        $follower_count = $auth->getFollowerCount($user_id);
    } elseif ($action === 'unfollow') {
        $auth->unfollowUser($current_user_id, $user_id);
        $is_following = false;
        $follower_count = $auth->getFollowerCount($user_id);
    }
}
require_once __DIR__ . '/header.php';
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - <?php echo SITE_NAME; ?></title>
    <script src="/js/twnd.css.js"></script>
</head>
<body>
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center">
                    <h1 class="text-blue-600 text-3xl font-bold"><?php echo SITE_NAME; ?></h1>
                </div>
                <div class="flex space-x-6">
                    <?php if ($auth->isLoggedIn()): ?>
                        <button onclick="location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'" class="px-6 py-3 text-blue-600 hover:text-blue-800 rounded-lg transition-all duration-300 font-medium">
                            <?php echo $_SESSION['username']; ?>
                        </button>
                        <button onclick="location.href='logout.php'" class="px-6 py-3 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition-all duration-300 font-medium">
                            退出
                        </button>
                    <?php else: ?>
                        <button onclick="location.href='login.php'" class="px-6 py-3 text-blue-600 hover:text-blue-800 rounded-lg transition-all duration-300 font-medium">
                            登录
                        </button>
                        <button onclick="location.href='register.php'" class="px-6 py-3 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition-all duration-300 font-medium">
                            注册
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-8 mb-8 bg-cover bg-center relative overflow-hidden" style="background-image: url('<?php echo !empty($user['background_image']) ? 'uploads/' . $user['background_image'] : 'https://placehold.co/1200x400?text=Background+Image'; ?>')">
            <div class="absolute inset-0 bg-opacity-50 backdrop-blur-sm"></div>
            <div class="flex items-center space-x-6 relative z-10">
                <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($user['avatar']); ?>" alt="头像" class="w-full h-full object-cover">
                    <?php else: ?>
                        <img src="https://placehold.co/96x96?text=<?php echo mb_substr($user['username'], 0, 1); ?>" alt="头像" class="w-full h-full object-cover">
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($user['username']); ?></h1>
                        <?php if ($auth->isLoggedIn() && $_SESSION['user_id'] == $user_id): ?>
                            <a href="profile_edit.php?id=<?php echo $user_id; ?>" class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition-all duration-300 font-medium">
                                编辑
                            </a>
                        <?php elseif ($auth->isLoggedIn() && $_SESSION['user_id'] != $user_id): ?>
                            <form method="POST" action="profile.php?id=<?php echo $user_id; ?>">
                                <?php if ($is_following): ?>
                                    <input type="hidden" name="action" value="unfollow">
                                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition">取消关注</button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="follow">
                                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">关注</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                    <p class="text-gray-600">注册于 <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></p>
                    <div class="flex space-x-4 mt-2">
                        <span class="text-gray-700">粉丝: <?php echo $follower_count; ?></span>
                        <span class="text-gray-700">关注: <?php echo $following_count; ?></span>
                    </div>
                    <?php if (!empty($user['bio'])): ?>
                        <p class="mt-2 text-gray-700"><?php echo htmlspecialchars($user['bio']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($user['website']) || !empty($user['github']) || !empty($user['twitter'])): ?>
                        <div class="flex space-x-4 mt-4">
                            <?php if (!empty($user['website'])): ?>
                                <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" class="text-blue-600 hover:underline">网站</a>
                            <?php endif; ?>
                            <?php if (!empty($user['github'])): ?>
                                <a href="https://github.com/<?php echo htmlspecialchars($user['github']); ?>" target="_blank" class="text-blue-600 hover:underline">GitHub</a>
                            <?php endif; ?>
                            <?php if (!empty($user['twitter'])): ?>
                                <a href="https://twitter.com/<?php echo htmlspecialchars($user['twitter']); ?>" target="_blank" class="text-blue-600 hover:underline">Twitter</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-2xl font-bold mb-4">文章</h2>
                <?php if (!empty($user_posts)): ?>
                    <?php foreach ($user_posts as $post): ?>
                        <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="text-gray-600 mb-4"><?php echo substr(htmlspecialchars($post['content']), 0, 100); ?>...</p>
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="text-blue-600 hover:underline">阅读更多</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">暂无文章</p>
                <?php endif; ?>
            </div>

            <div>
                <h2 class="text-2xl font-bold mb-4">视频</h2>
                <?php if (!empty($user_videos)): ?>
                    <?php foreach ($user_videos as $video): ?>
                        <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($video['title']); ?></h3>
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($video['description']); ?></p>
                            <a href="video.php?id=<?php echo $video['id']; ?>" class="text-blue-600 hover:underline">观看视频</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">暂无视频</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>