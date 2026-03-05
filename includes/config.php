<?php
/**
 * 299본부 성과관리 CRM - 설정 파일
 */

require_once __DIR__ . '/../vendor/autoload.php';

// 환경변수 로드
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// 에러 리포팅
if ($_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// 타임존 설정
date_default_timezone_set('Asia/Seoul');

// 세션 설정
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 상수 정의
define('APP_NAME', $_ENV['APP_NAME'] ?? '299본부 성과관리');
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'crm_299');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'default_secret_key');
define('JWT_EXPIRE', (int)($_ENV['JWT_EXPIRE'] ?? 86400));
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// CORS 헤더 (API용)
function setCorsHeaders(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// JSON 응답 헬퍼
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 성공 응답
function successResponse($data = null, string $message = '성공'): void {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

// 에러 응답
function errorResponse(string $message, int $statusCode = 400, $errors = null): void {
    $response = [
        'success' => false,
        'message' => $message
    ];
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    jsonResponse($response, $statusCode);
}

// 입력값 정리
function sanitizeInput($data): mixed {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 필수 필드 검증
function validateRequired(array $data, array $required): array {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}
