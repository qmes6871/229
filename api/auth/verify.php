<?php
/**
 * 토큰 검증 API
 * GET /api/auth/verify.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$auth = new Auth();
$user = $auth->requireAuth();

successResponse([
    'user' => [
        'id' => $user['sub'],
        'username' => $user['username'],
        'name' => $user['name'],
        'role' => $user['role']
    ]
], '유효한 토큰입니다.');
