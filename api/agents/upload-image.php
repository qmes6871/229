<?php
/**
 * 프로필 이미지 업로드 API
 * POST /api/agents/upload-image.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// 인증 확인
$auth = new Auth();
$auth->requireAuth();

// 설계사 ID 확인
$agentId = isset($_POST['agent_id']) ? (int) $_POST['agent_id'] : 0;
if (!$agentId) {
    errorResponse('설계사 ID가 필요합니다.');
}

$db = new Database();

// 설계사 존재 확인
$agent = $db->fetchOne("SELECT * FROM agents WHERE id = ?", [$agentId]);
if (!$agent) {
    errorResponse('설계사를 찾을 수 없습니다.', 404);
}

// 파일 업로드 확인
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    errorResponse('이미지 파일을 선택해주세요.');
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// 파일 타입 검증
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    errorResponse('지원하지 않는 이미지 형식입니다. (JPG, PNG, GIF, WEBP만 가능)');
}

// 파일 크기 검증
if ($file['size'] > $maxSize) {
    errorResponse('파일 크기는 5MB를 초과할 수 없습니다.');
}

// 업로드 디렉토리 확인
$uploadDir = UPLOAD_PATH . '/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 파일명 생성
$extension = match($mimeType) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    default => 'jpg'
};
$filename = 'agent_' . $agentId . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// 이전 이미지 삭제
if ($agent['profile_image'] && file_exists($uploadDir . $agent['profile_image'])) {
    unlink($uploadDir . $agent['profile_image']);
}

// 파일 이동
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    errorResponse('파일 업로드에 실패했습니다.');
}

// DB 업데이트
$db->update('agents', ['profile_image' => $filename], 'id = ?', [$agentId]);

successResponse([
    'filename' => $filename,
    'url' => '/229/uploads/profiles/' . $filename
], '이미지가 업로드되었습니다.');
