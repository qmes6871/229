<?php
/**
 * 실적 수정 API
 * PUT /api/performance/update.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/ScoreCalculator.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// ID 확인
$id = (int) ($input['id'] ?? 0);
if (!$id) {
    errorResponse('실적 ID가 필요합니다.');
}

$db = new Database();

// 기존 데이터 확인
$existing = $db->fetchOne("SELECT * FROM daily_performance WHERE id = ?", [$id]);
if (!$existing) {
    errorResponse('실적을 찾을 수 없습니다.', 404);
}

// 업데이트할 데이터 준비
$data = [];

// 월납보험료가 변경되면 조기가동도 자동 계산
if (isset($input['monthly_premium'])) {
    $monthlyPremium = (float) $input['monthly_premium'];
    $data['monthly_premium'] = $monthlyPremium;

    // 조기가동 자동 계산: 1~7일이면 월납보험료가 조기가동
    $day = (int) date('d', strtotime($existing['performance_date']));
    $data['early_premium'] = ($day >= 1 && $day <= 7) ? $monthlyPremium : 0;
}
if (isset($input['contract_count'])) {
    $data['contract_count'] = (int) $input['contract_count'];
}
if (isset($input['event_score'])) {
    $data['event_score'] = (float) $input['event_score'];
}
if (isset($input['memo'])) {
    $data['memo'] = sanitizeInput($input['memo']);
}

if (empty($data)) {
    errorResponse('수정할 데이터가 없습니다.');
}

try {
    $db->beginTransaction();

    $db->update('daily_performance', $data, 'id = ?', [$id]);

    // 점수 재계산
    $calculator = new ScoreCalculator();
    $calculator->calculateAndUpdate($existing['agent_id'], $existing['quarter_id']);

    $db->commit();

    $performance = $db->fetchOne("SELECT * FROM daily_performance WHERE id = ?", [$id]);

    successResponse($performance, '실적이 수정되었습니다.');
} catch (Exception $e) {
    $db->rollback();
    errorResponse('실적 수정에 실패했습니다.');
}
