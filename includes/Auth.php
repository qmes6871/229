<?php
/**
 * 299본부 성과관리 CRM - JWT 인증 클래스
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class Auth {
    private Database $db;
    private string $secret;
    private int $expire;

    public function __construct() {
        $this->db = new Database();
        $this->secret = JWT_SECRET;
        $this->expire = JWT_EXPIRE;
    }

    // 로그인 처리
    public function login(string $username, string $password): array {
        $admin = $this->db->fetchOne(
            "SELECT * FROM admins WHERE username = ? AND is_active = 1",
            [$username]
        );

        if (!$admin || !password_verify($password, $admin['password'])) {
            return ['success' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'];
        }

        // 마지막 로그인 시간 업데이트
        $this->db->update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$admin['id']]);

        // JWT 토큰 생성
        $token = $this->generateToken($admin);

        return [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'name' => $admin['name'],
                'role' => $admin['role']
            ]
        ];
    }

    // JWT 토큰 생성
    public function generateToken(array $user): string {
        $issuedAt = time();
        $expire = $issuedAt + $this->expire;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role']
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    // JWT 토큰 검증
    public function verifyToken(string $token): ?array {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    // Authorization 헤더에서 토큰 추출
    public function getTokenFromHeader(): ?string {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    // 인증 미들웨어
    public function requireAuth(): array {
        $token = $this->getTokenFromHeader();

        if (!$token) {
            errorResponse('인증 토큰이 필요합니다.', 401);
        }

        $payload = $this->verifyToken($token);

        if (!$payload) {
            errorResponse('유효하지 않거나 만료된 토큰입니다.', 401);
        }

        return $payload;
    }

    // 특정 역할 필요
    public function requireRole(string|array $roles): array {
        $user = $this->requireAuth();

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (!in_array($user['role'], $roles)) {
            errorResponse('권한이 없습니다.', 403);
        }

        return $user;
    }

    // 비밀번호 해시 생성
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    // 비밀번호 검증
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    // 쿠키에서 토큰 가져오기 (웹 페이지용)
    public function getTokenFromCookie(): ?string {
        return $_COOKIE['auth_token'] ?? null;
    }

    // 인증 상태 확인 (웹 페이지용)
    public function checkAuth(): ?array {
        $token = $this->getTokenFromCookie() ?? $this->getTokenFromHeader();

        if (!$token) {
            return null;
        }

        return $this->verifyToken($token);
    }

    // 웹 페이지용 인증 필수
    public function requireWebAuth(): array {
        $user = $this->checkAuth();

        if (!$user) {
            header('Location: /admin/login.php');
            exit;
        }

        return $user;
    }
}
