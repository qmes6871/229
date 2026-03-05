<?php
/**
 * 설계사 삭제 API
 * DELETE /api/agents/delete.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// 인증 확인
$auth = new Auth();
$auth->requireRole(['super_admin', 'admin']);

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);

// ID 확인
$id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
if (!$id) {
    errorResponse('설계사 ID가 필요합니다.');
}

$db = new Database();

// 기존 데이터 확인
$existing = $db->fetchOne("SELECT * FROM agents WHERE id = ?", [$id]);
if (!$existing) {
    errorResponse('설계사를 찾을 수 없습니다.', 404);
}

// 소프트 삭제 vs 하드 삭제 옵션
$hardDelete = isset($input['hard']) && $input['hard'] === true;

try {
    if ($hardDelete) {
        // 하드 삭제 (연관 데이터도 CASCADE로 삭제됨)
        $db->delete('agents', 'id = ?', [$id]);
        $message = '설계사가 완전히 삭제되었습니다.';
    } else {
        // 소프트 삭제 (비활성화)
        $db->update('agents', ['is_active' => 0], 'id = ?', [$id]);
        $message = '설계사가 비활성화되었습니다.';
    }

    successResponse(null, $message);
} catch (Exception $e) {
    errorResponse('설계사 삭제에 실패했습니다.');
}
