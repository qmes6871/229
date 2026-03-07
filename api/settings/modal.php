<?php
/**
 * 모달 콘텐츠 API
 * GET /api/settings/modal.php - 모달 콘텐츠 조회
 * POST /api/settings/modal.php - 모달 콘텐츠 수정 (관리자 전용)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 모달 콘텐츠 조회 (로그인 불필요)
    $eventContent = $db->getSetting('modal_event_content');
    $awardContent = $db->getSetting('modal_award_content');

    successResponse([
        'event' => $eventContent ?? '',
        'award' => $awardContent ?? ''
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 관리자 인증 필요
    $auth = new Auth();
    if (!$auth->checkAuth()) {
        errorResponse('인증이 필요합니다.', 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? '';
    $content = $input['content'] ?? '';

    if (!in_array($type, ['event', 'award'])) {
        errorResponse('유효하지 않은 모달 타입입니다.');
    }

    $settingKey = $type === 'event' ? 'modal_event_content' : 'modal_award_content';
    $db->setSetting($settingKey, $content);

    successResponse(['message' => '모달 콘텐츠가 저장되었습니다.']);
} else {
    errorResponse('Method not allowed', 405);
}
