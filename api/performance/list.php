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

// 합계 계산 (선택된 날짜 기준)
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

// 월별 합계 계산 (해당 월 전체)
$monthlyTotals = null;
$agentMonthlyStats = [];

if ($date) {
    $yearMonth = substr($date, 0, 7); // YYYY-MM
    $monthStart = $yearMonth . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    $monthlyTotals = $db->fetchOne("
        SELECT
            COALESCE(SUM(early_premium), 0) as early_premium,
            COALESCE(SUM(monthly_premium), 0) as monthly_premium,
            COALESCE(SUM(contract_count), 0) as contract_count
        FROM daily_performance
        WHERE quarter_id = ? AND performance_date BETWEEN ? AND ?
    ", [$quarterId, $monthStart, $monthEnd]);

    // 설계사별 월간 누적 통계
    $agentStats = $db->fetchAll("
        SELECT
            dp.agent_id,
            COALESCE(SUM(dp.early_premium), 0) as monthly_early,
            COALESCE(SUM(dp.monthly_premium), 0) as monthly_total,
            COALESCE(SUM(dp.contract_count), 0) as monthly_count
        FROM daily_performance dp
        WHERE dp.quarter_id = ? AND dp.performance_date BETWEEN ? AND ?
        GROUP BY dp.agent_id
    ", [$quarterId, $monthStart, $monthEnd]);

    foreach ($agentStats as $stat) {
        $agentMonthlyStats[$stat['agent_id']] = $stat;
    }
}

// 설계사별 3W 주차 (분기 기준)
$agentThreeW = [];
// 설계사별 분기 누적 실적
$agentQuarterStats = [];
if ($quarterId) {
    $quarterStats = $db->fetchAll("
        SELECT agent_id, three_w_weeks, monthly_cumulative, total_count
        FROM cumulative_performance
        WHERE quarter_id = ?
    ", [$quarterId]);

    foreach ($quarterStats as $stat) {
        $agentThreeW[$stat['agent_id']] = $stat['three_w_weeks'];
        $agentQuarterStats[$stat['agent_id']] = [
            'monthly_cumulative' => $stat['monthly_cumulative'],
            'total_count' => $stat['total_count']
        ];
    }
}

successResponse([
    'performances' => $performances,
    'totals' => $totals,
    'monthly_totals' => $monthlyTotals,
    'agent_monthly_stats' => $agentMonthlyStats,
    'agent_three_w' => $agentThreeW,
    'agent_quarter_stats' => $agentQuarterStats,
    'count' => count($performances)
]);
