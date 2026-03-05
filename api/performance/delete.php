<?php
/**
 * 실적 삭제 API
 * DELETE /api/performance/delete.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/ScoreCalculator.php';

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
    errorResponse('실적 ID가 필요합니다.');
}

$db = new Database();

// 기존 데이터 확인
$existing = $db->fetchOne("SELECT * FROM daily_performance WHERE id = ?", [$id]);
if (!$existing) {
    errorResponse('실적을 찾을 수 없습니다.', 404);
}

try {
    $db->beginTransaction();

    $agentId = $existing['agent_id'];
    $quarterId = $existing['quarter_id'];

    $db->delete('daily_performance', 'id = ?', [$id]);

    // 점수 재계산
    $calculator = new ScoreCalculator();
    $calculator->calculateAndUpdate($agentId, $quarterId);

    $db->commit();

    successResponse(null, '실적이 삭제되었습니다.');
} catch (Exception $e) {
    $db->rollback();
    errorResponse('실적 삭제에 실패했습니다.');
}
