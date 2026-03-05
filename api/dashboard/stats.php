<?php
/**
 * 통계 API
 * GET /api/dashboard/stats.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$db = new Database();

// 파라미터
$quarterId = isset($_GET['quarter_id']) ? (int) $_GET['quarter_id'] : null;

// 현재 분기 기본값
if (!$quarterId) {
    $quarter = $db->getCurrentQuarter();
    $quarterId = $quarter['id'] ?? null;
}

if (!$quarterId) {
    errorResponse('활성화된 분기가 없습니다.');
}

// 전체 통계
$stats = $db->fetchOne("
    SELECT
        COUNT(DISTINCT cp.agent_id) as total_agents,
        COALESCE(SUM(cp.early_cumulative), 0) as total_early,
        COALESCE(SUM(cp.monthly_cumulative), 0) as total_monthly,
        COALESCE(SUM(cp.total_count), 0) as total_contracts,
        COALESCE(AVG(cp.total_score), 0) as avg_score,
        COALESCE(MAX(cp.total_score), 0) as max_score
    FROM cumulative_performance cp
    JOIN agents a ON cp.agent_id = a.id
    WHERE cp.quarter_id = ? AND a.is_active = 1
", [$quarterId]);

// 오늘 실적
$today = date('Y-m-d');
$todayStats = $db->fetchOne("
    SELECT
        COUNT(DISTINCT dp.agent_id) as agents_with_performance,
        COALESCE(SUM(dp.early_premium), 0) as today_early,
        COALESCE(SUM(dp.monthly_premium), 0) as today_monthly,
        COALESCE(SUM(dp.contract_count), 0) as today_contracts
    FROM daily_performance dp
    WHERE dp.performance_date = ?
", [$today]);

// 팀별 통계
$teamStats = $db->fetchAll("
    SELECT
        t.id as team_id,
        t.name as team_name,
        COUNT(DISTINCT cp.agent_id) as member_count,
        COALESCE(SUM(cp.early_cumulative), 0) as team_early,
        COALESCE(SUM(cp.monthly_cumulative), 0) as team_monthly,
        COALESCE(SUM(cp.total_count), 0) as team_contracts,
        COALESCE(AVG(cp.total_score), 0) as team_avg_score
    FROM teams t
    LEFT JOIN agents a ON a.team_id = t.id AND a.is_active = 1
    LEFT JOIN cumulative_performance cp ON cp.agent_id = a.id AND cp.quarter_id = ?
    WHERE t.is_active = 1
    GROUP BY t.id, t.name
    ORDER BY team_avg_score DESC
", [$quarterId]);

// 분기 정보
$quarterInfo = $db->fetchOne("SELECT * FROM quarters WHERE id = ?", [$quarterId]);

// 숫자 포맷팅
$stats['total_early_formatted'] = number_format((float) $stats['total_early']);
$stats['total_monthly_formatted'] = number_format((float) $stats['total_monthly']);
$stats['total_contracts_formatted'] = number_format((int) $stats['total_contracts']);
$stats['avg_score_formatted'] = number_format((float) $stats['avg_score'], 1);

$todayStats['today_early_formatted'] = number_format((float) $todayStats['today_early']);
$todayStats['today_monthly_formatted'] = number_format((float) $todayStats['today_monthly']);

successResponse([
    'overview' => $stats,
    'today' => $todayStats,
    'teams' => $teamStats,
    'quarter' => $quarterInfo,
    'updated_at' => date('Y-m-d H:i:s')
]);
