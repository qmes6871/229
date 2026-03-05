<?php
/**
 * 로그인 API
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Invalid JSON input');
}

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

// 필수 필드 검증
if (empty($username) || empty($password)) {
    errorResponse('아이디와 비밀번호를 입력해주세요.');
}

$auth = new Auth();
$result = $auth->login($username, $password);

if ($result['success']) {
    successResponse([
        'token' => $result['token'],
        'user' => $result['user']
    ], '로그인 성공');
} else {
    errorResponse($result['message'], 401);
}
