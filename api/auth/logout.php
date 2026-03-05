<?php
/**
 * 로그아웃 API
 * POST /api/auth/logout.php
 */

require_once __DIR__ . '/../../includes/config.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// 클라이언트에서 토큰 삭제 처리
successResponse(null, '로그아웃 되었습니다.');
