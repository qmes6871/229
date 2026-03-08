<?php
/**
 * 분기 목표 점수 설정 API
 * POST /api/settings/target-score.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// 인증 체크
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$authHeader || !preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
    errorResponse('인증이 필요합니다.', 401);
}

$db = new Database();

$input = json_decode(file_get_contents('php://input'), true);
$quarterId = $input['quarter_id'] ?? null;
$targetScore = $input['target_score'] ?? 200;

if (!$quarterId) {
    errorResponse('분기 ID가 필요합니다.');
}

// 분기 존재 확인
$quarter = $db->fetchOne("SELECT id FROM quarters WHERE id = ?", [$quarterId]);
if (!$quarter) {
    errorResponse('해당 분기를 찾을 수 없습니다.');
}

// 목표 점수 업데이트
$result = $db->execute("
    UPDATE quarters SET target_score = ? WHERE id = ?
", [$targetScore, $quarterId]);

if ($result) {
    successResponse(['message' => '목표 점수가 저장되었습니다.']);
} else {
    errorResponse('저장에 실패했습니다.');
}
