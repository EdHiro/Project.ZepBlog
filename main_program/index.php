<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/content.php';

// 设置页面标题
$pageTitle = '首页';

// 初始化 Content 对象
$contentObj = new Content($db, $auth);

require_once __DIR__ . '/header.php';
?>
    <nav class="bg-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <div class="flex items-center">
                <h1 class="text-blue-600 text-3xl font-bold" onclick="location.href='index.php'"><?php echo SITE_NAME; ?></h1>
            </div>
            <div class="flex space-x-6">
                <?php if ($auth->isLoggedIn()): ?>
                    <?php if ($auth->isAdmin()): ?>
                        <button onclick="location.href='admin.php'" class="px-6 py-3 bg-green-600 text-white hover:bg-green-700 rounded-lg transition-all duration-300 font-medium">
                            后台管理
                        </button>
                    <?php endif; ?>
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

<main class="min-h-screen bg-gray-100">
    <!-- 功能区 -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 筛选和排序 -->
        <div class="mb-8 flex justify-between items-center">
            <div class="flex gap-4">
                <button id="show-all" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 active">全部</button>
                <button id="show-posts" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">文章</button>
                <button id="show-videos" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">视频</button>
            </div>
            <select id="sort-select" 
                    class="px-4 py-2 border rounded-lg text-gray-600"
                    aria-label="排序方式">
                <option value="latest">最新发布</option>
                <option value="popular">最多观看</option>
            </select>
        </div>

        <!-- 内容网格 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="content-grid">
            <?php 
            // 获取内容并添加明确的类型标记
            $posts = array_map(function($post) use ($contentObj) {
                return array_merge($post, [
                    'type' => 'posts', // 改为复数形式，以匹配按钮 ID
                    'view_count' => $contentObj->getLikesCount($post['id'], 'post')
                ]);
            }, $contentObj->getAllPosts());

            $videos = array_map(function($video) {
                return array_merge($video, [
                    'type' => 'videos' // 改为复数形式，以匹配按钮 ID
                ]);
            }, $contentObj->getAllVideos());

            $allContent = array_merge($posts, $videos);
            usort($allContent, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            foreach ($allContent as $item): 
                $isPost = $item['type'] === 'posts';
            ?>
                <div class="content-item <?php echo $item['type']; ?>"
                     data-type="<?php echo $item['type']; ?>"
                     data-date="<?php echo $item['created_at']; ?>"
                     data-views="<?php echo $item['view_count'] ?? 0; ?>">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden h-full flex flex-col hover:shadow-lg transition-shadow duration-300">
                        <?php if (!$isPost && isset($item['thumbnail_url'])): ?>
                            <div class="relative aspect-video bg-gray-100">
                                <img src="<?php echo htmlspecialchars($item['thumbnail_url']); ?>" 
                                     alt="视频封面" 
                                     class="w-full h-full object-cover">
                                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-20">
                                    <div class="w-16 h-16 flex items-center justify-center rounded-full bg-blue-600 bg-opacity-75">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 卡片内容 -->
                        <div class="p-6 flex-1 flex flex-col">
                            <h3 class="text-xl font-bold mb-2 hover:text-blue-600 transition-colors">
                                <a href="<?php echo $isPost ? 'post.php?id=' : 'video.php?id='; ?><?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </h3>
                            
                            <p class="text-gray-600 mb-4 flex-1">
                                <?php 
                                    if ($isPost) {
                                        $content = $item['content'];
                                        // 检查是否为JSON格式
                                        $decoded = json_decode($content, true);
                                        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['blocks'])) {
                                            // 处理EditorJS格式
                                            $text = '';
                                            foreach ($decoded['blocks'] as $block) {
                                                if ($block['type'] === 'paragraph') {
                                                    // 移除HTML标签，保留纯文本
                                                    $blockText = strip_tags($block['data']['text']);
                                                    // 移除特殊格式标记
                                                    $blockText = preg_replace('/<[^>]*>/', '', $blockText);
                                                    $text .= $blockText . ' ';
                                                }
                                            }
                                        } else {
                                            // 处理普通HTML内容
                                            $text = strip_tags($content);
                                            // 移除特殊格式标记
                                            $text = preg_replace('/<[^>]*>/', '', $text);
                                        }
                                        // 移除多余空格并截取
                                        $text = trim(preg_replace('/\s+/', ' ', $text));
                                        echo htmlspecialchars(mb_substr($text, 0, 150)) . '...';
                                    } else {
                                        echo htmlspecialchars(mb_substr($item['description'], 0, 150)) . '...';
                                    }
                                ?>
                            </p>
                            
                            <!-- 卡片底部信息 -->
                            <div class="mt-auto flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-500">
                                        <?php echo date('Y-m-d', strtotime($item['created_at'])); ?>
                                    </span>
                                    <span class="text-sm text-gray-500 flex items-center">
                                        <?php echo $isPost ? '👁 ' : '▶ '; ?>
                                        <?php echo number_format($item['view_count']); ?>
                                    </span>
                                </div>
                                <a href="<?php echo $isPost ? 'post.php?id=' : 'video.php?id='; ?><?php echo $item['id']; ?>" 
                                   class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                    <?php echo $isPost ? '阅读' : '观看'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const grid = document.getElementById('content-grid');
        const filterButtons = document.querySelectorAll('#show-all, #show-posts, #show-videos');
        const sortSelect = document.getElementById('sort-select');
        
        // 改进的筛选功能
        function filterContent(type) {
            const items = document.querySelectorAll('.content-item');
            items.forEach(item => {
                // 先移除之前的过渡样式
                item.style.transition = 'none';
                item.style.opacity = '1';
                item.style.transform = 'none';

                // 根据类型显示或隐藏
                if (type === 'all' || item.dataset.type === type) {
                    item.style.display = '';
                    requestAnimationFrame(() => {
                        item.style.transition = 'all 0.3s ease-out';
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    });
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // 改进的排序功能
        function sortContent(criteria) {
            const visibleItems = Array.from(document.querySelectorAll('.content-item:not([style*="display: none"])'));
            
            // 排序逻辑
            visibleItems.sort((a, b) => {
                if (criteria === 'latest') {
                    return new Date(b.dataset.date) - new Date(a.dataset.date);
                }
                return parseInt(b.dataset.views) - parseInt(a.dataset.views);
            });

            // 重新排列元素
            visibleItems.forEach((item, index) => {
                // 先移除过渡效果
                item.style.transition = 'none';
                grid.appendChild(item);
                
                // 添加排序动画
                requestAnimationFrame(() => {
                    item.style.transition = 'all 0.3s ease-out';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                });
            });
        }

        // 按钮点击处理
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // 更新按钮样式
                filterButtons.forEach(btn => {
                    btn.classList.remove('bg-blue-500', 'text-white');
                    btn.classList.add('bg-white', 'text-gray-600');
                });
                
                button.classList.remove('bg-white', 'text-gray-600');
                button.classList.add('bg-blue-500', 'text-white');
                
                // 先应用筛选
                const type = button.id.split('-')[1];
                filterContent(type);
                
                // 然后应用当前排序
                setTimeout(() => {
                    sortContent(sortSelect.value);
                }, 50);
            });
        });

        // 排序选择处理
        sortSelect.addEventListener('change', (e) => {
            sortContent(e.target.value);
        });

        // 添加基本样式
        const style = document.createElement('style');
        style.textContent = `
            .content-item {
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.3s ease-out;
            }
            .content-item.visible {
                opacity: 1;
                transform: translateY(0);
            }
        `;
        document.head.appendChild(style);

        // 初始化显示
        setTimeout(() => {
            filterContent('all');
            document.querySelectorAll('.content-item').forEach(item => {
                item.classList.add('visible');
            });
        }, 0);
    });
</script>
</body>
</html>