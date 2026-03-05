<?php
/**
 * 설계사 등록 API
 * POST /api/agents/create.php
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
$required = ['name'];
$missing = validateRequired($input, $required);

if (!empty($missing)) {
    errorResponse('필수 필드가 누락되었습니다: ' . implode(', ', $missing));
}

$db = new Database();

// 데이터 준비
$data = [
    'name' => sanitizeInput($input['name']),
    'team_id' => !empty($input['team_id']) ? (int) $input['team_id'] : null,
    'phone' => sanitizeInput($input['phone'] ?? ''),
    'join_date' => !empty($input['join_date']) ? $input['join_date'] : null,
    'position' => sanitizeInput($input['position'] ?? '설계사'),
    'prev_quarter_avg' => (float) ($input['prev_quarter_avg'] ?? 0),
    'is_active' => isset($input['is_active']) ? (int) $input['is_active'] : 1,
    'sort_order' => (int) ($input['sort_order'] ?? 0)
];

try {
    $id = $db->insert('agents', $data);

    // 현재 분기에 누적 실적 레코드 생성
    $quarter = $db->getCurrentQuarter();
    if ($quarter) {
        $db->insert('cumulative_performance', [
            'agent_id' => $id,
            'quarter_id' => $quarter['id']
        ]);
    }

    $agent = $db->fetchOne("SELECT * FROM agents WHERE id = ?", [$id]);

    successResponse($agent, '설계사가 등록되었습니다.');
} catch (Exception $e) {
    errorResponse('설계사 등록에 실패했습니다.');
}
