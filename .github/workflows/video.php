<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/content.php';

$videoId = $_GET['id'] ?? 0;
$contentObj = new Content($db, $auth);
$video = $contentObj->getVideo($videoId);

if (!$video) {
    header('Location: index.php');
    exit;
}

// 获取视频质量信息
$qualities = $video['qualities'] ?? [];

// 获取默认画质
$defaultQuality = isset($video['default_quality']) ? (int)$video['default_quality'] : 720;

// 增加视频观看计数
$contentObj->incrementViewCount($videoId);

require_once __DIR__ . '/header.php';
?>
    <title><?php echo htmlspecialchars($video['title']); ?> - <?php echo SITE_NAME; ?></title>
    <!-- 删除原有播放器相关代码 -->
    <!-- 添加 Plyr 资源 -->
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
    <script src="/js/twnd.css.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // 确保全局player变量存在且是Plyr实例时才销毁
    if (window.player && window.player instanceof Plyr) {
        window.player.destroy();
    }

    try {
        const playerElement = document.getElementById('player');
        if (!playerElement) {
            console.error('Player element not found');
            return;
        }

        // 初始化 Plyr 播放器
        window.player = new Plyr('#player', {
            controls: [
                'play-large', 
                'play', 
                'progress', 
                'current-time',
                'duration',
                'mute', 
                'volume',
                'captions',
                'settings',
                'pip',
                'fullscreen'
            ],
            settings: ['captions', 'quality', 'speed', 'loop'],
            quality: {
                default: <?php echo $defaultQuality; ?>,
                options: Object.keys(<?php echo json_encode($qualities); ?>).map(Number).sort((a,b) => b-a),
                forced: true,
                onChange: function(quality) {
                    try {
                        if (!window.player) {
                            console.error('Player instance not found');
                            return;
                        }

                        const currentTime = window.player.currentTime;
                        const isPaused = window.player.paused;
                        const sources = <?php echo json_encode($qualities); ?>;
                        const newSource = sources[quality];

                        if (!newSource) {
                            console.error('Source not found for quality:', quality);
                            return;
                        }

                        // 更新视频源
                        const videoElement = window.player.media;
                        if (videoElement) {
                            const currentSource = videoElement.querySelector('source');
                            if (currentSource) {
                                currentSource.src = newSource;
                                videoElement.load();
                                videoElement.addEventListener('loadedmetadata', function onLoaded() {
                                    videoElement.currentTime = currentTime;
                                    if (!isPaused) {
                                        videoElement.play().catch(e => console.error('Play failed:', e));
                                    }
                                    videoElement.removeEventListener('loadedmetadata', onLoaded);
                                });
                            }
                        }
                    } catch (error) {
                        console.error('Error during quality change:', error);
                    }
                }
            },
            resetOnEnd: true,
            keyboard: { focused: true, global: true },
            tooltips: { controls: true, seek: true },
            i18n: {
                restart: '重新播放',
                rewind: '后退 {seektime} 秒',
                play: '播放',
                pause: '暂停',
                forward: '前进 {seektime} 秒',
                played: '已播放',
                buffered: '已缓冲',
                currentTime: '当前时间',
                duration: '持续时间',
                volume: '音量',
                mute: '静音',
                unmute: '取消静音',
                enableCaptions: '启用字幕',
                disableCaptions: '禁用字幕',
                enterFullscreen: '进入全屏',
                exitFullscreen: '退出全屏',
                frameTitle: '播放器',
                captions: '字幕',
                settings: '设置',
                speed: '速度',
                normal: '正常',
                quality: '画质',
                loop: '循环',
                start: '开始',
                end: '结束',
                all: '全部',
                reset: '重置',
                disabled: '禁用',
                advertisement: '广告'
            }
        });

        // 视频结束时保存观看记录
        window.player.on('ended', () => {
            // 这里可以添加观看记录相关代码
        });
    } catch (error) {
        console.error('Error initializing player:', error.message);
    }
});
</script>
    <style>
        .video-container {
            --plyr-color-main: #3b82f6;
            --plyr-control-radius: 8px;
            max-width: 100%;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="min-h-screen bg-gray-100 transition-all duration-300">
        <nav class="bg-white shadow-lg sticky top-0 z-50 transition-all duration-300 hover:shadow-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <div class="flex items-center">
                        <h1 class="text-blue-600 text-3xl font-bold" onclick="location.href='index.php'"><?php echo SITE_NAME; ?></h1>
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

        <main class="container mx-auto p-4">
            <div class="bg-white rounded-lg shadow-md p-8 mt-4 transition-all duration-300 hover:shadow-lg">
                <h1 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($video['title']); ?></h1>
                <div class="flex justify-between items-center mb-4">
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                        <?php echo date('Y-m-d', strtotime($video['created_at']) + date('Z', strtotime($video['created_at']))); ?>
                    </span>
                    <span class="text-sm text-gray-500">
                        观看次数: <?php echo $video['view_count'] ?? 0; ?>
                    </span>
                </div>
                
                <!-- 修改视频播放器容器 -->
                <div class="video-container mb-6">
                    <video id="player" playsinline controls>
                        <?php if (!empty($qualities)): ?>
                            <?php foreach ($qualities as $quality => $url): ?>
                            <source src="<?php echo htmlspecialchars($url); ?>" type="video/mp4" size="<?php echo intval($quality); ?>">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <source src="<?php echo htmlspecialchars($video['video_url']); ?>" type="video/mp4">
                        <?php endif; ?>
                        <p>您的浏览器不支持 HTML5 视频播放</p>
                    </video>
                </div>
                
                <div class="prose max-w-none mb-8">
                    <?php echo nl2br(htmlspecialchars($video['description'])); ?>
                </div>
                
                <div class="border-t-2 border-gray-300 p-6 bg-gray-50 rounded-lg mt-8">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">评论</h2>

                <?php if ($auth->isLoggedIn()): ?>
                    <form id="comment-form" class="mb-8" onsubmit="event.preventDefault()">
                        <input type="hidden" name="content_id" value="<?php echo $video['id']; ?>">
                        <input type="hidden" name="content_type" value="video">
                        <textarea name="content" placeholder="写下你的评论..." rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"></textarea>
                        <button type="submit" class="mt-3 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">提交评论</button>
                    </form>
                    <script>
document.getElementById('comment-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('comment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // 创建新评论元素
            const commentHtml = `
                <div class="group relative border-l-2 border-blue-100 pl-6 py-4 hover:bg-gray-50 transition-all duration-300 ease-out">
                    <div class="flex items-start gap-4">
                        <div class="bg-blue-500 w-10 h-10 rounded-full flex items-center justify-center text-white font-medium">
                            ${result.comment.username.charAt(0)}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-baseline gap-3 mb-2">
                                <a href="profile.php?id=${result.comment.user_id}" class="font-semibold text-gray-800 hover:text-blue-600 transition-colors">
                                    ${result.comment.username}
                                </a>
                                <span class="text-xs text-gray-400">
                                    ${new Date().toLocaleString('zh-CN', {month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'})}
                                </span>
                            </div>
                            <p class="text-gray-700 leading-relaxed mb-3">
                                ${result.comment.content}
                            </p>
                            <div class="flex items-center gap-4 text-sm">
                                <button class="like-btn flex items-center gap-1 text-gray-500 hover:text-blue-600 transition-colors" data-id="${result.comment.id}" data-type="comment">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 8.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span class="like-count">0</span>
                                </button>
                                <button class="text-gray-500 hover:text-blue-600 transition-colors" onclick="toggleReplyForm('${result.comment.id}')">回复</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // 将新评论添加到评论列表的顶部
            const commentsList = document.querySelector('.space-y-4');
            commentsList.insertAdjacentHTML('afterbegin', commentHtml);
            
            // 清空表单
            form.reset();
        } else {
            alert(result.message || '评论发送失败');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('评论发送失败，请稍后重试');
    }
});
</script>
                <?php else: ?>
                    <p class="text-gray-600 mb-6">请<a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">登录</a>后发表评论</p>
                <?php endif; ?>

                <div class="space-y-4">
                    <?php
                    $page = max(1, intval($_GET['page'] ?? 1));
                    $perPage = 4;
                    $comments = $contentObj->getComments($video['id'], 'video', $page, $perPage);
                    $totalComments = $contentObj->getCommentsCount($video['id'], 'video');
                    $totalPages = ceil($totalComments / $perPage);
                    ?>
                    <?php
                    function displayComment($comment, $level = 0, $videoId)
                    {
                        global $auth;
                        $borderClass = ['border-l-2 border-blue-100', 'border-l-2 border-blue-200', 'border-l-2 border-blue-300'];
                        $avatarColors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500'];
                        $initials = mb_substr($comment['username'], 0, 1);
                        ?>
                        <div class="group relative <?php echo $borderClass[min($level, 2)]; ?> pl-6 py-4 hover:bg-gray-50 transition-all duration-300 ease-out">
                            <div class="flex items-start gap-4">
                                <div class="<?php echo $avatarColors[array_rand($avatarColors)]; ?> w-10 h-10 rounded-full flex items-center justify-center text-white font-medium">
                                    <?php echo $initials; ?>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-baseline gap-3 mb-2">
                                        <a href="profile.php?id=<?php echo $comment['user_id']; ?>" 
                                           class="font-semibold text-gray-800 hover:text-blue-600 transition-colors">
                                            <?php echo htmlspecialchars($comment['username']); ?>
                                        </a>
                                        <span class="text-xs text-gray-400">
                                            <?php echo date('m-d H:i', strtotime($comment['created_at']) + (new DateTimeZone(SITE_TIMEZONE))->getOffset(new DateTime())); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-700 leading-relaxed mb-3">
                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                    </p>
                                    <div class="flex items-center gap-4 text-sm">
                                        <button class="like-btn flex items-center gap-1 text-gray-500 hover:text-blue-600 transition-colors" 
                                                data-id="<?php echo $comment['id']; ?>" data-type="comment">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 8.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            <span class="like-count"><?php echo $comment['like_count'] ?? 0; ?></span>
                                        </button>
                                        <?php if ($auth->isLoggedIn()): ?>
                                            <button class="text-gray-500 hover:text-blue-600 transition-colors" 
                                                    onclick="toggleReplyForm('<?php echo $comment['id']; ?>')">
                                                        回复
                                                    </button>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Reply form with smooth transition -->
                                        <div id="reply-form-<?php echo $comment['id']; ?>" 
                                             class="overflow-hidden transition-all duration-300 ease-in-out max-h-0">
                                            <form method="post" action="comment.php" class="pt-4">
                                                <!-- Existing form fields -->
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($comment['replies'])): ?>
                                <div class="ml-6 space-y-4 mt-4">
                                    <?php foreach ($comment['replies'] as $reply): ?>
                                        <?php displayComment($reply, $level + 1, $videoId); ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                        }

                        foreach ($comments as $comment) {
                            displayComment($comment, 0, $video['id']);
                        }
                        ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="flex justify-center mt-6 space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?id=<?php echo $video['id']; ?>&page=<?php echo $page - 1; ?>" class="px-4 py-2 border rounded-lg hover:bg-gray-100">上一页</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?id=<?php echo $video['id']; ?>&page=<?php echo $i; ?>" class="px-4 py-2 border rounded-lg <?php echo $i == $page ? 'bg-blue-500 text-white' : 'hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?id=<?php echo $video['id']; ?>&page=<?php echo $page + 1; ?>" class="px-4 py-2 border rounded-lg hover:bg-gray-100">下一页</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php   