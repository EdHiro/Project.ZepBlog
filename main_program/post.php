<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/content.php';

$postId = $_GET['id'] ?? 0;
$content = new Content($db, $auth);
$post = $content->getPost($postId);

// 确保$post已定义且不为空
if (empty($post)) {
    header('Location: index.php');
    exit;
}

// 解析 Editor.js JSON 内容
$postContent = '';
if (!empty($post['content'])) {
    $contentData = json_decode($post['content'], true);
    if ($contentData && isset($contentData['blocks'])) {
        foreach ($contentData['blocks'] as $block) {
            switch ($block['type']) {
                case 'header':
                    $level = $block['data']['level'] ?? 2;
                    $postContent .= sprintf('<h%d class="text-2xl font-bold mb-4 mt-6">%s</h%d>', 
                        $level, 
                        htmlspecialchars($block['data']['text']), 
                        $level
                    );
                    break;
                case 'paragraph':
                    $postContent .= sprintf('<p class="mb-4 text-gray-700">%s</p>', 
                        nl2br(htmlspecialchars($block['data']['text']))
                    );
                    break;
                case 'list':
                    $tag = $block['data']['style'] === 'ordered' ? 'ol' : 'ul';
                    $items = array_map(function($item) {
                        return sprintf('<li class="ml-4">%s</li>', htmlspecialchars($item));
                    }, $block['data']['items']);
                    $postContent .= sprintf('<%s class="list-%s mb-4 pl-4">%s</%s>', 
                        $tag, 
                        $block['data']['style'], 
                        implode('', $items), 
                        $tag
                    );
                    break;
                    
                case 'video':
                    $postContent .= sprintf(
                        '<div class="video-container mb-4">
                            <video class="w-full rounded-lg" controls>
                                <source src="%s" type="%s">
                                您的浏览器不支持视频播放。
                            </video>
                            %s
                        </div>',
                        htmlspecialchars($block['data']['file']['url'] ?? ''),
                        htmlspecialchars($block['data']['file']['type'] ?? 'video/mp4'),
                        !empty($block['data']['caption']) ? 
                            '<p class="text-center text-gray-500 mt-2">' . 
                            htmlspecialchars($block['data']['caption']) . 
                            '</p>' : ''
                    );
                    break;
                    
                case 'audio':
                    $postContent .= sprintf(
                        '<div class="audio-container mb-4">
                            <audio class="w-full" controls>
                                <source src="%s" type="%s">
                                您的浏览器不支持音频播放。
                            </audio>
                            %s
                        </div>',
                        htmlspecialchars($block['data']['file']['url'] ?? ''),
                        htmlspecialchars($block['data']['file']['type'] ?? 'audio/mpeg'),
                        !empty($block['data']['caption']) ? 
                            '<p class="text-center text-gray-500 mt-2">' . 
                            htmlspecialchars($block['data']['caption']) . 
                            '</p>' : ''
                    );
                    break;
                    
                case 'attaches':
                    $postContent .= sprintf(
                        '<div class="attachment-block mb-4 p-4 border rounded-lg bg-gray-50">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                                <a href="%s" class="text-blue-600 hover:text-blue-800" download>
                                    %s
                                </a>
                                <span class="ml-2 text-gray-500">(%s)</span>
                            </div>
                        </div>',
                        htmlspecialchars($block['data']['file']['url']),
                        htmlspecialchars($block['data']['file']['name']),
                        htmlspecialchars($block['data']['file']['size'])
                    );
                    break;
                    
                case 'mermaid':
                    if (!isset($block['data']) || !is_array($block['data']) || empty($block['data']['code'])) {
                        $postContent .= '<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">⚠️</div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">Mermaid图表内容为空或格式不正确</p>
                                </div>
                            </div>
                        </div>';
                        break;
                    }
                    
                    // 清理和验证Mermaid代码
                    $code = trim($block['data']['code']);
                    if (empty($code)) {
                        break;
                    }
                    
                    // 标准化换行符
                    $code = str_replace(['\r\n', '\r'], '\n', $code);
                    
                    // 移除可能导致语法错误的字符
                    $code = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $code);
                    $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
                    
                    $postContent .= sprintf(
                        '<div class="mermaid-diagram mb-4" data-processed="false">
                            <pre class="mermaid">%s</pre>
                            <div class="mermaid-error hidden text-red-500 text-sm mt-2"></div>
                        </div>',
                        $code
                    );
                    break;
                case 'image':
                    // 处理图片样式类
                    $imageClasses = ['max-w-full rounded-lg'];
                    if (!empty($block['data']['withBorder'])) {
                        $imageClasses[] = 'border-2 border-gray-200';
                    }
                    if (!empty($block['data']['stretched'])) {
                        $imageClasses[] = 'w-full';
                    }
                    if (!empty($block['data']['withBackground'])) {
                        $imageClasses[] = 'bg-gray-100 p-2';
                    }
                    
                    $postContent .= sprintf(
                        '<figure class="mb-4 %s">
                            <img src="%s" alt="%s" class="%s" style="%s">
                            %s
                        </figure>',
                        !empty($block['data']['withBackground']) ? 'bg-gray-100 p-4 rounded-lg' : '',
                        htmlspecialchars($block['data']['file']['url'] ?? ''),
                        htmlspecialchars($block['data']['caption'] ?? ''),
                        implode(' ', $imageClasses),
                        !empty($block['data']['stretched']) ? 'width: 100%' : '',
                        !empty($block['data']['caption']) ? 
                            '<figcaption class="text-center text-gray-500 mt-2">' . 
                            htmlspecialchars($block['data']['caption']) . 
                            '</figcaption>' : ''
                    );
                    break;
                case 'quote':
                    $postContent .= sprintf('<blockquote class="border-l-4 border-gray-300 pl-4 italic mb-4"><p>%s</p><footer>%s</footer></blockquote>',
                        htmlspecialchars($block['data']['text']),
                        htmlspecialchars($block['data']['caption'])
                    );
                    break;
                case 'code':
                    if (!isset($block['data']) || !is_array($block['data'])) {
                        break;
                    }
                    $code = isset($block['data']['code']) ? trim($block['data']['code']) : '';
                    $language = $block['data']['language'] ?? '';
                    
                    // 如果没有指定语言，尝试自动检测
                    if (empty($language) && !empty($code)) {
                        // PHP
                        if (preg_match('/^<\?php|^<\?=|function\s+\w+\s*\(|namespace\s+\w+/i', $code)) {
                            $language = 'php';
                        }
                        // JavaScript
                        else if (preg_match('/^import\s+|^export\s+|const\s+\w+\s*=|let\s+\w+\s*=|var\s+\w+\s*=|function\s*\w*\s*\(|=>\s*{/m', $code)) {
                            $language = 'javascript';
                        }
                        // Python
                        else if (preg_match('/^def\s+\w+\s*\(|^class\s+\w+:|import\s+\w+|from\s+\w+\s+import/m', $code)) {
                            $language = 'python';
                        }
                        // HTML
                        else if (preg_match('/^<!DOCTYPE\s+html|^<html|^<div|^<p>|^<script|^<style/i', $code)) {
                            $language = 'html';
                        }
                        // CSS
                        else if (preg_match('/{[^}]*background|margin|padding|font-size|color:/i', $code)) {
                            $language = 'css';
                        }
                        // SQL
                        else if (preg_match('/SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|TABLE|FROM|WHERE/i', $code)) {
                            $language = 'sql';
                        }
                        // Java
                        else if (preg_match('/public\s+class|private\s+\w+|protected\s+\w+|@Override/i', $code)) {
                            $language = 'java';
                        }
                        // 默认为纯文本
                        else {
                            $language = 'plaintext';
                        }
                    }
                    
                    $language = htmlspecialchars($language ?: 'plaintext');
                    $languageDisplay = [
                        'php' => 'PHP',
                        'javascript' => 'JavaScript',
                        'typescript' => 'TypeScript',
                        'css' => 'CSS',
                        'html' => 'HTML',
                        'sql' => 'SQL',
                        'python' => 'Python',
                        'java' => 'Java',
                        'cpp' => 'C++',
                        'csharp' => 'C#',
                        'go' => 'Go',
                        'rust' => 'Rust',
                        'ruby' => 'Ruby',
                        'swift' => 'Swift',
                        'kotlin' => 'Kotlin',
                        'plaintext' => '纯文本'
                    ][$language] ?? $language;
                    
                    $code = '';
                    if (isset($block['data']['code'])) {
                        $code = htmlspecialchars(trim($block['data']['code']));
                    }
                    $postContent .= sprintf(
                        '<div class="code-block">
                            <div class="code-header">
                                <span class="language-label">%s</span>
                                <button onclick="copyCode(this)" class="copy-button">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                                    </svg>
                                    <span>复制代码</span>
                                </button>
                            </div>
                            <pre><code class="language-%s">%s</code></pre>
                        </div>',
                        $languageDisplay,
                        $language,
                        $code
                    );
                    break;
                    
                case 'delimiter':
                    $postContent .= '<hr class="my-8 border-t-2 border-gray-300">';
                    break;
                    
                case 'table':
                    $rows = [];
                    foreach ($block['data']['content'] as $row) {
                        $cells = array_map(function($cell) {
                            return sprintf('<td class="border px-4 py-2">%s</td>', htmlspecialchars($cell));
                        }, $row);
                        $rows[] = '<tr>' . implode('', $cells) . '</tr>';
                    }
                    $postContent .= sprintf(
                        '<div class="overflow-x-auto mb-4"><table class="min-w-full border-collapse border">%s</table></div>',
                        implode('', $rows)
                    );
                    break;
                    
                case 'warning':
                    $postContent .= sprintf(
                        '<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">⚠️</div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">%s</h3>
                                    <div class="mt-2 text-sm text-yellow-700">%s</div>
                                </div>
                            </div>
                        </div>',
                        htmlspecialchars($block['data']['title']),
                        htmlspecialchars($block['data']['message'])
                    );
                    break;
                    
                case 'embed':
                    $postContent .= sprintf(
                        '<div class="embed-responsive aspect-video mb-4">%s</div>',
                        $block['data']['embed']
                    );
                    break;
                    
                case 'video':
                    $postContent .= sprintf(
                        '<div class="video-container mb-4">
                            <video class="w-full rounded-lg" controls>
                                <source src="%s" type="%s">
                                您的浏览器不支持视频播放。
                            </video>
                            %s
                        </div>',
                        htmlspecialchars($block['data']['file']['url'] ?? ''),
                        htmlspecialchars($block['data']['file']['type'] ?? 'video/mp4'),
                        !empty($block['data']['caption']) ? 
                            '<p class="text-center text-gray-500 mt-2">' . 
                            htmlspecialchars($block['data']['caption']) . 
                            '</p>' : ''
                    );
                    break;
                    
                case 'audio':
                    $postContent .= sprintf(
                        '<div class="audio-container mb-4">
                            <audio class="w-full" controls>
                                <source src="%s" type="%s">
                                您的浏览器不支持音频播放。
                            </audio>
                            %s
                        </div>',
                        htmlspecialchars($block['data']['file']['url'] ?? ''),
                        htmlspecialchars($block['data']['file']['type'] ?? 'audio/mpeg'),
                        !empty($block['data']['caption']) ? 
                            '<p class="text-center text-gray-500 mt-2">' . 
                            htmlspecialchars($block['data']['caption']) . 
                            '</p>' : ''
                    );
                    break;
                    
                case 'checklist':
                    $items = array_map(function($item) {
                        $checked = $item['checked'] ? 'checked' : '';
                        return sprintf(
                            '<div class="flex items-center mb-2">
                                <input type="checkbox" %s class="form-checkbox h-4 w-4 text-blue-600" disabled>
                                <span class="ml-2 text-gray-700">%s</span>
                            </div>',
                            $checked,
                            htmlspecialchars($item['text'])
                        );
                    }, $block['data']['items']);
                    $postContent .= '<div class="checklist mb-4">' . implode('', $items) . '</div>';
                    break;
                    
                case 'attaches':
                    $postContent .= sprintf(
                        '<div class="attachment-block mb-4 p-4 border rounded-lg bg-gray-50">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                                <a href="%s" class="text-blue-600 hover:text-blue-800" download>
                                    %s
                                </a>
                                <span class="ml-2 text-gray-500">(%s)</span>
                            </div>
                        </div>',
                        htmlspecialchars($block['data']['file']['url']),
                        htmlspecialchars($block['data']['file']['name']),
                        htmlspecialchars($block['data']['file']['size'])
                    );
                    break;
                    
                case 'mermaid':
                    if (!isset($block['data']) || !is_array($block['data']) || empty($block['data']['code'])) {
                        $postContent .= '<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">⚠️</div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">Mermaid图表内容为空或格式不正确</p>
                                </div>
                            </div>
                        </div>';
                        break;
                    }
                    
                    // 清理和验证Mermaid代码
                    $code = trim($block['data']['code']);
                    if (empty($code)) {
                        break;
                    }
                    
                    // 标准化换行符
                    $code = str_replace(['\r\n', '\r'], '\n', $code);
                    
                    // 移除可能导致语法错误的字符
                    $code = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $code);
                    $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
                    
                    $postContent .= sprintf(
                        '<div class="mermaid-diagram mb-4" data-processed="false">
                            <pre class="mermaid">%s</pre>
                            <div class="mermaid-error hidden text-red-500 text-sm mt-2"></div>
                        </div>',
                        $code
                    );
                    break;
            }
        }
    }
}

// 更新文章内容变量
$post['content'] = $postContent;

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            mermaid.initialize({
                startOnLoad: false,
                theme: 'default',
                securityLevel: 'loose',
                logLevel: 'error',
                errorHandler: function(error, id) {
                    const errorContainer = document.querySelector(`#${id} + .mermaid-error`);
                    if (errorContainer) {
                        errorContainer.textContent = `图表渲染错误: ${error.message || '未知错误'}`;
                        errorContainer.classList.remove('hidden');
                    }
                }
            });

            // 使用run代替init
            document.querySelectorAll('.mermaid-diagram').forEach((diagram, index) => {
                const mermaidPre = diagram.querySelector('.mermaid');
                if (mermaidPre) {
                    mermaidPre.id = `mermaid-${index}`;
                    mermaid.run({
                        nodes: [`#${mermaidPre.id}`]
                    }).catch(error => {
                        console.error('Mermaid渲染错误:', error);
                    });
                }
            });
        });
    </script>
    <script>
        function copyCode(button) {
            const codeBlock = button.parentElement.nextElementSibling;
            const code = codeBlock.textContent;
            navigator.clipboard.writeText(code).then(() => {
                button.textContent = '已复制!';
                setTimeout(() => {
                    button.textContent = '复制';
                }, 2000);
            });
        }

        function highlightKeywords() {
            const keywords = ['function', 'return', 'if', 'else', 'for', 'while', 'class', 'new', 'this'];
            const codeBlocks = document.querySelectorAll('pre code');
            
            codeBlocks.forEach(block => {
                let code = block.innerHTML;
                keywords.forEach(keyword => {
                    const regex = new RegExp(`\\b${keyword}\\b`, 'g');
                    code = code.replace(regex, `<span class="highlight">${keyword}</span>`);
                });
                block.innerHTML = code;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            highlightKeywords();
            document.querySelectorAll('.like-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const type = this.dataset.type;
                    fetch('like.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id, type})
                    }).then(res => res.json())
                      .then(data => {
                          if(data.success) {
                              const icon = this.querySelector('.like-icon');
                              const count = this.querySelector('.like-count');
                              if(data.action === 'like') {
                                  icon.textContent = '❤️';
                              } else {
                                  icon.textContent = '🤍';
                              }
                              count.textContent = data.count;
                          }
                      });
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化所有代码块
            document.querySelectorAll('pre code').forEach((block) => {
                // 移除多余的空格和缩进
                block.textContent = block.textContent.trim();
                // 禁用自动高亮
                block.setAttribute('data-manual', '');
                // 手动调用 Prism 高亮
                Prism.highlightElement(block);
            });

            // 修改复制功能
            function copyCode(button) {
                const pre = button.closest('.code-block').querySelector('pre');
                const code = pre.querySelector('code').textContent;
                
                navigator.clipboard.writeText(code).then(() => {
                    const originalText = button.querySelector('span').textContent;
                    button.querySelector('span').textContent = '已复制!';
                    setTimeout(() => {
                        button.querySelector('span').textContent = originalText;
                    }, 2000);
                });
            }

            // 将copyCode函数暴露到全局作用域
            window.copyCode = copyCode;
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 代码复制功能
            window.copyCode = function(button) {
                const codeBlock = button.closest('.code-block').querySelector('code');
                const code = codeBlock.textContent;
                
                navigator.clipboard.writeText(code).then(() => {
                    const originalText = button.querySelector('span').textContent;
                    button.querySelector('span').textContent = '已复制!';
                    setTimeout(() => {
                        button.querySelector('span').textContent = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('复制失败:', err);
                });
            };

            // 高亮处理
            Prism.highlightAll();
        });
    </script>
    <script>
        // 移除旧的复制函数
        function copyCode(button) {
            const textarea = document.createElement('textarea');
            const codeBlock = button.closest('.code-block').querySelector('code');
            const code = codeBlock.innerText || codeBlock.textContent;
            
            // 创建临时文本区域
            textarea.value = code;
            textarea.style.position = 'fixed';
            textarea.style.opacity = 0;
            document.body.appendChild(textarea);
            
            try {
                // 选择并复制文本
                textarea.select();
                document.execCommand('copy');
                
                // 更新按钮文本
                const span = button.querySelector('span');
                const originalText = span.textContent;
                span.textContent = '已复制!';
                setTimeout(() => {
                    span.textContent = originalText;
                }, 2000);
            } catch (err) {
                console.error('复制失败:', err);
            } finally {
                // 清理临时元素
                document.body.removeChild(textarea);
            }
        }
    </script>
    <script>
        // 定义单一的复制函数
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.width = '2em';
            textArea.style.height = '2em';
            textArea.style.padding = '0';
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';
            textArea.style.background = 'transparent';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                return true;
            } catch (err) {
                console.error('复制失败:', err);
                return false;
            } finally {
                document.body.removeChild(textArea);
            }
        }

        function copyCode(button) {
            const codeBlock = button.closest('.code-block').querySelector('code');
            const code = codeBlock.innerText || codeBlock.textContent;
            
            let success = false;
            
            // 尝试使用现代 API
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(code).then(() => {
                    updateButtonText(button, true);
                }).catch(() => {
                    // 如果现代 API 失败，使用回退方法
                    success = fallbackCopyTextToClipboard(code);
                    updateButtonText(button, success);
                });
            } else {
                // 在不支持现代 API 的环境中使用回退方法
                success = fallbackCopyTextToClipboard(code);
                updateButtonText(button, success);
            }
        }

        function updateButtonText(button, success) {
            const span = button.querySelector('span');
            const originalText = '复制代码';
            if (success) {
                span.textContent = '已复制!';
                setTimeout(() => {
                    span.textContent = originalText;
                }, 2000);
            } else {
                span.textContent = '复制失败';
                setTimeout(() => {
                    span.textContent = originalText;
                }, 2000);
            }
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            mermaid.initialize({
                startOnLoad: false,
                theme: 'default',
                securityLevel: 'loose',
                logLevel: 'error',
                errorHandler: function(error, id) {
                    const errorContainer = document.querySelector(`#${id} + .mermaid-error`);
                    if (errorContainer) {
                        errorContainer.textContent = `图表渲染错误: ${error.message || '未知错误'}`;
                        errorContainer.classList.remove('hidden');
                    }
                }
            });

            // 使用run代替init
            document.querySelectorAll('.mermaid-diagram').forEach((diagram, index) => {
                const mermaidPre = diagram.querySelector('.mermaid');
                if (mermaidPre) {
                    mermaidPre.id = `mermaid-${index}`;
                    mermaid.run({
                        nodes: [`#${mermaidPre.id}`]
                    }).catch(error => {
                        console.error('Mermaid渲染错误:', error);
                    });
                }
            });
        });
    </script>
    <title><?php echo htmlspecialchars($post['title']); ?> - <?php echo SITE_NAME; ?></title>
    <script src="/js/twnd.css.js"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .page-transition {
            animation: fadeIn 0.25s ease-in-out;
        }
        
        .code-block-container {
            position: relative;
            background-color: #f8f8f8;
            border-radius: 0.5rem;
            margin: 1rem 0;
            padding: 1rem;
            border: 1px solid #e2e8f0;
        }
        .code-toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 0.5rem;
        }
        .copy-btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }
        .copy-btn:hover {
            background-color: #2563eb;
        }
        pre code {
            display: block;
            padding: 1rem;
            overflow-x: auto;
            color: #333;
        }
        .inline-code {
            background-color: #f0f0f0;
            padding: 0.2em 0.4em;
            border-radius: 0.25em;
            font-family: monospace;
        }
        .highlight {
            background-color: #fffacd;
            padding: 0.1em 0.2em;
            border-radius: 0.2em;
        }
        /* 代码块样式优化 */
        pre {
            margin: 0;
            padding: 0;
            background: #1e1e1e;
        }
        
        code[class*="language-"] {
            font-size: 14px;
            line-height: 1.5;
            font-family: 'Fira Code', Consolas, Monaco, monospace;
            text-shadow: none;
        }

        .code-block {
            position: relative;
            margin: 1.5em 0;
            border-radius: 6px;
            overflow: hidden;
            background: #1e1e1e;
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5em 1em;
            background: #2d2d2d;
            color: #d4d4d4;
            font-size: 0.875rem;
        }

        .line-numbers .line-numbers-rows {
            border-right-color: #404040;
        }
        .code-block {
            position: relative;
            margin: 1.5em 0;
            border-radius: 6px;
            overflow: hidden;
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5em 1em;
            background: #2d2d2d;
            color: #d4d4d4;
        }

        .code-block pre {
            margin: 0 !important;
            padding: 1em !important;
            max-height: 500px;
            overflow: auto;
            background: #1e1e1e !important;
        }

        .code-block code {
            font-family: 'Fira Code', Consolas, Monaco, monospace;
            font-size: 0.9em !important;
            line-height: 1.5 !important;
            text-shadow: none !important;
        }

        .copy-button {
            padding: 0.25em 0.5em;
            font-size: 0.875rem;
            color: #d4d4d4;
            background: transparent;
            border: 1px solid #404040;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }

        .copy-button:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
    <!-- 修改 Prism.js 引入顺序和主题 -->
    <link href="https://unpkg.com/prismjs@1.29.0/themes/prism-okaidia.min.css" rel="stylesheet" />
    <script src="https://unpkg.com/prismjs@1.29.0/components/prism-core.min.js"></script>
    <script src="https://unpkg.com/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化代码高亮
            document.querySelectorAll('pre code').forEach((block) => {
                // 处理 HTML 特殊字符
                let code = block.innerHTML;
                code = code.replace(/&/g, '&amp;')
                         .replace(/<//g, '&lt;')
                         .replace(/>/g, '&gt;')
                         .replace(/"/g, '&quot;')
                         .replace(/'/g, '&#039;');
                block.innerHTML = code;
                
                // 应用高亮
                Prism.highlightElement(block);
            });

            // 代码复制功能
            window.copyCode = function(button) {
                const pre = button.closest('.code-block').querySelector('pre');
                const code = pre.querySelector('code').innerText;
                
                navigator.clipboard.writeText(code).then(() => {
                    const span = button.querySelector('span');
                    const originalText = span.textContent;
                    span.textContent = '已复制!';
                    setTimeout(() => {
                        span.textContent = originalText;
                    }, 2000);
                });
            };
        });
    </script>
</head>


<body class="page-transition">
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


    <div class="container mx-auto px-4 py-8 max-w-4xl space-y-8 mt-20">
        <article class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 animate-fade-in">
            <div class="p-6">
                <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="flex items-center mb-6 space-x-4">
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm"><?php 
                        $date = new DateTime($post['created_at'], new DateTimeZone('UTC'));
                        $date->setTimezone(new DateTimeZone(SITE_TIMEZONE));
                        echo '发布于 ' . $date->format('Y-m-d H:i');
                    ?></span>
                    <?php if (isset($post['updated_at']) && $post['updated_at'] !== $post['created_at']): ?>
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm"><?php 
                        $updateDate = new DateTime($post['updated_at'], new DateTimeZone('UTC'));
                        $updateDate->setTimezone(new DateTimeZone(SITE_TIMEZONE));
                        echo '编辑于 ' . $updateDate->format('Y-m-d H:i');
                    ?></span>
                    <?php endif; ?>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                        作者: <a href="profile.php?id=<?php echo $post['user_id']; ?>" 
                             class="hover:text-green-600"><?php echo htmlspecialchars($post['username']); ?></a>
                    </span>
                </div>
                <div class="prose max-w-none">
                    <?php echo htmlspecialchars_decode($post['content']); ?>
                </div>
            </div>

            <div class="border-t-2 border-gray-300 p-6 bg-gray-50 rounded-lg mt-8">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">评论</h2>

                <?php if ($auth->isLoggedIn()): ?>
                    <form method="post" action="comment.php" class="mb-8">
                        <input type="hidden" name="content_id" value="<?php echo $postId; ?>">
                        <input type="hidden" name="content_type" value="post">
                        <textarea name="content" placeholder="写下你的评论..." rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"></textarea>
                        <button type="submit" class="mt-3 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">提交评论</button>
                    </form>
                <?php else: ?>
                    <p class="text-gray-600 mb-6">请<a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">登录</a>后发表评论</p>
                <?php endif; ?>

                <div class="space-y-4">
                    <?php
                    $page = max(1, intval($_GET['page'] ?? 1));
                    $perPage = 4;
                    $comments = $content->getComments($post['id'], 'post', $page, $perPage);
                    // 移除重复的$auth初始化
                    $totalComments = $content->getCommentsCount($post['id'], 'post');
                    $totalPages = ceil($totalComments / $perPage);
                    ?>
                    <?php
                    function displayComment($comment, $level = 0, $postId)
                    {
                        global $auth;
                        $marginClasses = ['pl-8', 'pl-16', 'pl-24', 'pl-32'];
                        $margin = $marginClasses[min($level, 3)];
                        $bgClass = $level % 2 === 0 ? 'bg-gray-50 even:bg-white' : 'bg-gray-100 even:bg-gray-50';
                    ?>
                        <div class="p-4 rounded-lg shadow-md <?php echo $margin; ?> <?php echo $bgClass; ?> hover:shadow-md transition-shadow duration-200">
                            <div class="flex justify-between items-center mb-2">
                                <a href="profile.php?id=<?php echo $comment['user_id']; ?>" class="font-medium text-gray-800 hover:text-blue-600"><?php echo htmlspecialchars($comment['username']); ?></a>
                                <span class="text-sm text-gray-500"><?php 
$date = new DateTime($comment['created_at'], new DateTimeZone('UTC'));
$date->setTimezone(new DateTimeZone(SITE_TIMEZONE));
echo $date->format('Y-m-d H:i');
?></span>
                            </div>
                            <p class="text-gray-700 mb-2"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            <?php if ($auth->isLoggedIn()): ?>
                                <button class="text-sm px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-full" onclick="document.getElementById('reply-form-<?php echo $comment['id']; ?>').classList.toggle('hidden')">
                                    回复
                                </button>
                                <form id="reply-form-<?php echo $comment['id']; ?>" method="post" action="comment.php" class="hidden mt-2">
                                    <input type="hidden" name="content_id" value="<?php echo $postId; ?>">
                                    <input type="hidden" name="content_type" value="post">
                                    <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                    <textarea name="content" placeholder="写下你的回复..." rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"></textarea>
                                    <button type="submit" class="mt-1 px-4 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 text-sm">提交回复</button>
                                </form>
                            <?php endif; ?>
                            <?php if (!empty($comment['replies'])): ?>
                                <div class="ml-8 space-y-4 mt-6">
                                    <?php foreach ($comment['replies'] as $reply): ?>
                                        <?php displayComment($reply, $level + 1, $postId); ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php

                    }

                    foreach ($comments as $comment) {
                        displayComment($comment, 0, $post['id']);
                    }
                    ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center mt-6 space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?id=<?php echo $post['id']; ?>&page=<?php echo $page - 1; ?>" class="px-4 py-2 border rounded-lg hover:bg-gray-100">上一页</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?id=<?php echo $post['id']; ?>&page=<?php echo $i; ?>" class="px-4 py-2 border rounded-lg <?php echo $i == $page ? 'bg-blue-500 text-white' : 'hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?id=<?php echo $post['id']; ?>&page=<?php echo $page + 1; ?>" class="px-4 py-2 border rounded-lg hover:bg-gray-100">下一页</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </div>
</body>
</html>
<?php   