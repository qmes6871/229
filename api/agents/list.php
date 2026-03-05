<?php
/**
 * 설계사 목록 API
 * GET /api/agents/list.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$db = new Database();

// 파라미터
$teamId = isset($_GET['team_id']) ? (int) $_GET['team_id'] : null;
$isActive = isset($_GET['is_active']) ? (int) $_GET['is_active'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 쿼리 빌드
$sql = "
    SELECT
        a.*,
        t.name as team_name
    FROM agents a
    LEFT JOIN teams t ON a.team_id = t.id
    WHERE 1=1
";
$params = [];

if ($isActive !== null) {
    $sql .= " AND a.is_active = ?";
    $params[] = $isActive;
}

if ($teamId) {
    $sql .= " AND a.team_id = ?";
    $params[] = $teamId;
}

if ($search) {
    $sql .= " AND (a.name LIKE ? OR a.phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " ORDER BY a.sort_order ASC, a.name ASC";

$agents = $db->fetchAll($sql, $params);

// 팀 목록도 함께 반환
$teams = $db->fetchAll("SELECT * FROM teams WHERE is_active = 1 ORDER BY sort_order ASC");

successResponse([
    'agents' => $agents,
    'teams' => $teams,
    'total' => count($agents)
]);
