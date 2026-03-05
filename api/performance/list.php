<?php
/**
 * 실적 목록 API
 * GET /api/performance/list.php
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
$agentId = isset($_GET['agent_id']) ? (int) $_GET['agent_id'] : null;
$quarterId = isset($_GET['quarter_id']) ? (int) $_GET['quarter_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// 현재 분기 기본값
if (!$quarterId) {
    $quarter = $db->getCurrentQuarter();
    $quarterId = $quarter['id'] ?? null;
}

// 쿼리 빌드
$sql = "
    SELECT
        dp.*,
        a.name as agent_name,
        a.team_id,
        t.name as team_name
    FROM daily_performance dp
    JOIN agents a ON dp.agent_id = a.id
    LEFT JOIN teams t ON a.team_id = t.id
    WHERE 1=1
";
$params = [];

if ($quarterId) {
    $sql .= " AND dp.quarter_id = ?";
    $params[] = $quarterId;
}

if ($agentId) {
    $sql .= " AND dp.agent_id = ?";
    $params[] = $agentId;
}

if ($date) {
    $sql .= " AND dp.performance_date = ?";
    $params[] = $date;
}

if ($startDate) {
    $sql .= " AND dp.performance_date >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $sql .= " AND dp.performance_date <= ?";
    $params[] = $endDate;
}

$sql .= " ORDER BY dp.performance_date DESC, a.name ASC";

$performances = $db->fetchAll($sql, $params);

// 합계 계산
$totals = [
    'monthly_premium' => 0,
    'contract_count' => 0,
    'early_premium' => 0
];

foreach ($performances as $p) {
    $totals['monthly_premium'] += (float) $p['monthly_premium'];
    $totals['contract_count'] += (int) $p['contract_count'];
    $totals['early_premium'] += (float) $p['early_premium'];
}

successResponse([
    'performances' => $performances,
    'totals' => $totals,
    'count' => count($performances)
]);
