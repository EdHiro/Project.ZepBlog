<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/content.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => '请先登录']);
        exit;
    }

    $contentId = $_POST['content_id'] ?? 0;
    $contentType = $_POST['content_type'] ?? '';
    $commentContent = $_POST['content'] ?? '';
    
    if ($contentId && $contentType && $commentContent) {
        $parentId = $_POST['parent_id'] ?? null;
        if ($content->addComment($contentId, $contentType, $commentContent, $parentId)) {
            // 获取新添加的评论信息
            $newComment = [
                'id' => $db->lastInsertId(),
                'content' => $commentContent,
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'created_at' => date('Y-m-d H:i:s'),
                'like_count' => 0,
                'parent_id' => $parentId,
                'replies' => []
            ];
            
            echo json_encode(['success' => true, 'comment' => $newComment]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => '评论发送失败']);
    exit;
}

echo json_encode(['success' => false, 'message' => '无效的请求方法']);
exit;
?>