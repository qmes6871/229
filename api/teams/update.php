<?php
/**
 * 팀 수정 API
 * PUT /api/teams/update.php
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
    errorResponse('팀 ID가 필요합니다.');
}

$db = new Database();

// 기존 데이터 확인
$existing = $db->fetchOne("SELECT * FROM teams WHERE id = ?", [$id]);
if (!$existing) {
    errorResponse('팀을 찾을 수 없습니다.', 404);
}

// 업데이트할 데이터 준비
$data = [];

if (isset($input['name'])) {
    $data['name'] = sanitizeInput($input['name']);
}
if (isset($input['sort_order'])) {
    $data['sort_order'] = (int) $input['sort_order'];
}
if (isset($input['is_active'])) {
    $data['is_active'] = (int) $input['is_active'];
}

if (empty($data)) {
    errorResponse('수정할 데이터가 없습니다.');
}

try {
    $db->update('teams', $data, 'id = ?', [$id]);
    $team = $db->fetchOne("SELECT * FROM teams WHERE id = ?", [$id]);

    successResponse($team, '팀 정보가 수정되었습니다.');
} catch (Exception $e) {
    errorResponse('팀 수정에 실패했습니다.');
}
