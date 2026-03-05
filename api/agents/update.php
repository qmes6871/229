<?php
/**
 * 설계사 수정 API
 * PUT /api/agents/update.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

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
    errorResponse('설계사 ID가 필요합니다.');
}

$db = new Database();

// 기존 데이터 확인
$existing = $db->fetchOne("SELECT * FROM agents WHERE id = ?", [$id]);
if (!$existing) {
    errorResponse('설계사를 찾을 수 없습니다.', 404);
}

// 업데이트할 데이터 준비
$data = [];

if (isset($input['name'])) {
    $data['name'] = sanitizeInput($input['name']);
}
if (array_key_exists('team_id', $input)) {
    $data['team_id'] = !empty($input['team_id']) ? (int) $input['team_id'] : null;
}
if (isset($input['phone'])) {
    $data['phone'] = sanitizeInput($input['phone']);
}
if (array_key_exists('join_date', $input)) {
    $data['join_date'] = !empty($input['join_date']) ? $input['join_date'] : null;
}
if (isset($input['position'])) {
    $data['position'] = sanitizeInput($input['position']);
}
if (isset($input['prev_quarter_avg'])) {
    $data['prev_quarter_avg'] = (float) $input['prev_quarter_avg'];
}
if (isset($input['best_monthly_premium'])) {
    $data['best_monthly_premium'] = (float) $input['best_monthly_premium'];
}
if (isset($input['best_monthly_count'])) {
    $data['best_monthly_count'] = (int) $input['best_monthly_count'];
}
if (isset($input['is_active'])) {
    $data['is_active'] = (int) $input['is_active'];
}
if (isset($input['sort_order'])) {
    $data['sort_order'] = (int) $input['sort_order'];
}

if (empty($data)) {
    errorResponse('수정할 데이터가 없습니다.');
}

try {
    $db->update('agents', $data, 'id = ?', [$id]);
    $agent = $db->fetchOne("SELECT * FROM agents WHERE id = ?", [$id]);

    successResponse($agent, '설계사 정보가 수정되었습니다.');
} catch (Exception $e) {
    errorResponse('설계사 수정에 실패했습니다.');
}
