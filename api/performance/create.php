<?php
/**
 * 실적 등록 API
 * POST /api/performance/create.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/ScoreCalculator.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// 인증 확인
$auth = new Auth();
$user = $auth->requireAuth();

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Invalid JSON input');
}

// 필수 필드 검증
$required = ['agent_id', 'performance_date'];
$missing = validateRequired($input, $required);

if (!empty($missing)) {
    errorResponse('필수 필드가 누락되었습니다: ' . implode(', ', $missing));
}

$db = new Database();

// 설계사 확인
$agentId = (int) $input['agent_id'];
$agent = $db->fetchOne("SELECT * FROM agents WHERE id = ? AND is_active = 1", [$agentId]);
if (!$agent) {
    errorResponse('설계사를 찾을 수 없습니다.', 404);
}

// 분기 확인
$quarterId = !empty($input['quarter_id']) ? (int) $input['quarter_id'] : null;
if (!$quarterId) {
    $quarter = $db->getCurrentQuarter();
    $quarterId = $quarter['id'] ?? null;
}

if (!$quarterId) {
    errorResponse('활성화된 분기가 없습니다.');
}

// 날짜 검증
$performanceDate = $input['performance_date'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $performanceDate)) {
    errorResponse('날짜 형식이 올바르지 않습니다. (YYYY-MM-DD)');
}

// 중복 확인
$existing = $db->fetchOne(
    "SELECT id FROM daily_performance WHERE agent_id = ? AND performance_date = ?",
    [$agentId, $performanceDate]
);

if ($existing) {
    errorResponse('해당 날짜에 이미 실적이 등록되어 있습니다. 수정을 이용해주세요.');
}

// 데이터 준비
$data = [
    'agent_id' => $agentId,
    'quarter_id' => $quarterId,
    'performance_date' => $performanceDate,
    'monthly_premium' => (float) ($input['monthly_premium'] ?? 0),
    'contract_count' => (int) ($input['contract_count'] ?? 0),
    'early_premium' => (float) ($input['early_premium'] ?? 0),
    'memo' => sanitizeInput($input['memo'] ?? ''),
    'created_by' => $user['sub']
];

try {
    $db->beginTransaction();

    $id = $db->insert('daily_performance', $data);

    // 점수 재계산
    $calculator = new ScoreCalculator();
    $calculator->calculateAndUpdate($agentId, $quarterId);

    $db->commit();

    $performance = $db->fetchOne("SELECT * FROM daily_performance WHERE id = ?", [$id]);

    successResponse($performance, '실적이 등록되었습니다.');
} catch (Exception $e) {
    $db->rollback();
    errorResponse('실적 등록에 실패했습니다.');
}
