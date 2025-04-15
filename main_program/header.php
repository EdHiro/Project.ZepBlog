<?php
/**
 * 公共头部文件，包含页面切换动画效果
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/js/twnd.css.js"></script>
    <style>
        /* 基础主题变量 */
        :root {
            --bg-color: #ffffff;
            --text-color: #111827;
            --primary-color: #3b82f6;
            --page-transition: 0.3s;
        }
        
        /* 页面过渡动画 */
        @keyframes pageEnter {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes contentFadeIn {
            from {
                opacity: 0;
                transform: scale(0.98) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* 页面淡入淡出动画 */
        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOutPage {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }

        /* 应用页面动画 */
        body {
            animation: pageEnter 0.3s ease-out;
            background-color: var(--bg-color);
            color: var(--text-color);
            opacity: 0;
            animation-fill-mode: forwards;
            animation: fadeInPage var(--page-transition) ease-out forwards;
        }

        body.page-leaving {
            animation: fadeOutPage var(--page-transition) ease-out forwards;
        }

        /* 内容区域动画 */
        main {
            animation: contentFadeIn 0.4s ease-out 0.1s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        /* 组件渐入效果 */
        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
            opacity: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* 交互动效 */
        .hover-lift {
            transition: transform 0.2s ease-out;
        }

        .hover-lift:hover {
            transform: translateY(-2px);
        }

        /* 卡片动画 */
        .card-animation {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-animation:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* 保留原有样式 */
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
            border: 2px solid var(--primary-color);
        }
        
        .default-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
            border: 2px solid var(--primary-color);
        }

        /* 添加全局页面过渡动画 */
        body {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            will-change: opacity, transform;
        }

        body.loaded {
            opacity: 1;
            transform: translateY(0);
        }

        body.leaving {
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
        }

        /* 确保其他动画不受影响 */
        .content-transition {
            transition: all 0.3s ease-out;
        }
    </style>
    <script>
        // 确保页面加载完成后再显示内容
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '1';
        });

        document.addEventListener('DOMContentLoaded', function() {
            // 处理所有链接点击
            document.addEventListener('click', function(e) {
                // 查找最近的链接或按钮元素
                const link = e.target.closest('a, button[onclick*="location"]');
                if (!link) return;

                // 获取目标 URL
                const url = link.href || (link.getAttribute('onclick') || '').match(/location\.href='([^']+)'/)?.[1];
                if (!url || url.startsWith('#') || url.includes('javascript:')) return;

                // 阻止默认跳转
                e.preventDefault();

                // 添加淡出动画
                document.body.classList.add('page-leaving');

                // 等待动画完成后跳转
                setTimeout(() => {
                    window.location.href = url;
                }, 300); // 与 CSS 动画时长匹配
            });

            // 确保页面加载时显示
            document.body.style.opacity = '';
        });

        // 防止页面后退时的闪烁
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.body.classList.remove('page-leaving');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // 页面加载完成后显示
            requestAnimationFrame(() => {
                document.body.classList.add('loaded');
            });

            // 处理所有链接和按钮的点击
            document.addEventListener('click', function(e) {
                const target = e.target.closest('a, button[onclick*="location"]');
                if (!target) return;

                const url = target.href || (target.getAttribute('onclick') || '').match(/location\.href='([^']+)'/)?.[1];
                if (!url || url.startsWith('#') || url.includes('javascript:')) return;

                e.preventDefault();
                document.body.classList.add('leaving');

                setTimeout(() => {
                    window.location.href = url;
                }, 300);
            });
        });

        // 处理浏览器后退
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.body.classList.remove('leaving');
                requestAnimationFrame(() => {
                    document.body.classList.add('loaded');
                });
            }
        });

        // 处理页面离开
        window.addEventListener('beforeunload', function() {
            document.body.classList.add('leaving');
        });
    </script>
</head>
<body>