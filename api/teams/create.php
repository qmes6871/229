<?php
/**
 * 팀 등록 API
 * POST /api/teams/create.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// 인증 확인
$auth = new Auth();
$auth->requireAuth();

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Invalid JSON input');
}

// 필수 필드 검증
if (empty($input['name'])) {
    errorResponse('팀명을 입력해주세요.');
}

$db = new Database();

// 데이터 준비
$data = [
    'name' => sanitizeInput($input['name']),
    'sort_order' => (int) ($input['sort_order'] ?? 0),
    'is_active' => isset($input['is_active']) ? (int) $input['is_active'] : 1
];

try {
    $id = $db->insert('teams', $data);
    $team = $db->fetchOne("SELECT * FROM teams WHERE id = ?", [$id]);

    successResponse($team, '팀이 등록되었습니다.');
} catch (Exception $e) {
    errorResponse('팀 등록에 실패했습니다.');
}
