<?php
/**
 * 팀 삭제 API
 * DELETE /api/teams/delete.php
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
$auth->requireAuth();

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);

// ID 확인
$id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
if (!$id) {
    errorResponse('팀 ID가 필요합니다.');
}

$db = new Database();

// 기존 데이터 확인
$existing = $db->fetchOne("SELECT * FROM teams WHERE id = ?", [$id]);
if (!$existing) {
    errorResponse('팀을 찾을 수 없습니다.', 404);
}

// 해당 팀에 소속된 설계사 확인
$agentCount = $db->fetchColumn("SELECT COUNT(*) FROM agents WHERE team_id = ?", [$id]);
if ($agentCount > 0) {
    errorResponse("해당 팀에 {$agentCount}명의 설계사가 소속되어 있습니다. 설계사를 다른 팀으로 이동하거나 소속을 해제한 후 삭제해주세요.");
}

try {
    $db->delete('teams', 'id = ?', [$id]);
    successResponse(null, '팀이 삭제되었습니다.');
} catch (Exception $e) {
    errorResponse('팀 삭제에 실패했습니다.');
}
