<?php
/**
 * 설계사별 실적 상세 조회 API
 * GET /api/performance/agent-detail.php?agent_id=1&quarter_id=1
 * - 특정 설계사의 분기별 모든 일일 실적 조회
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
$agentId = isset($_GET['agent_id']) ? (int) $_GET['agent_id'] : 0;
$quarterId = isset($_GET['quarter_id']) ? (int) $_GET['quarter_id'] : null;

if (!$agentId) {
    errorResponse('설계사 ID가 필요합니다.');
}

// 현재 분기 기본값
if (!$quarterId) {
    $quarter = $db->getCurrentQuarter();
    $quarterId = $quarter['id'] ?? null;
}

if (!$quarterId) {
    errorResponse('활성화된 분기가 없습니다.');
}

// 설계사 정보 조회
$agent = $db->fetchOne("
    SELECT
        a.id,
        a.name,
        a.position,
        a.profile_image,
        t.name as team_name
    FROM agents a
    LEFT JOIN teams t ON a.team_id = t.id
    WHERE a.id = ?
", [$agentId]);

if (!$agent) {
    errorResponse('설계사를 찾을 수 없습니다.');
}

// 분기 정보 조회
$quarter = $db->fetchOne("SELECT * FROM quarters WHERE id = ?", [$quarterId]);

// 해당 분기의 모든 일일 실적 조회
$performances = $db->fetchAll("
    SELECT
        id,
        performance_date,
        early_premium,
        monthly_premium,
        contract_count,
        memo,
        created_at,
        updated_at
    FROM daily_performance
    WHERE agent_id = ? AND quarter_id = ?
    ORDER BY performance_date DESC
", [$agentId, $quarterId]);

// 각 실적에 조기가동 여부 표시
foreach ($performances as &$perf) {
    $day = (int) date('j', strtotime($perf['performance_date']));
    $perf['is_early'] = ($day <= 7);
    $perf['performance_date_formatted'] = date('Y-m-d (D)', strtotime($perf['performance_date']));
    $perf['early_premium_formatted'] = number_format((float) $perf['early_premium']);
    $perf['monthly_premium_formatted'] = number_format((float) $perf['monthly_premium']);
}

// 분기 합계
$totals = $db->fetchOne("
    SELECT
        COALESCE(SUM(early_premium), 0) as total_early,
        COALESCE(SUM(monthly_premium), 0) as total_monthly,
        COALESCE(SUM(contract_count), 0) as total_count
    FROM daily_performance
    WHERE agent_id = ? AND quarter_id = ?
", [$agentId, $quarterId]);

// 이번 달 합계
$currentMonth = date('Y-m');
$monthlyTotals = $db->fetchOne("
    SELECT
        COALESCE(SUM(early_premium), 0) as total_early,
        COALESCE(SUM(monthly_premium), 0) as total_monthly,
        COALESCE(SUM(contract_count), 0) as total_count
    FROM daily_performance
    WHERE agent_id = ? AND quarter_id = ?
    AND DATE_FORMAT(performance_date, '%Y-%m') = ?
", [$agentId, $quarterId, $currentMonth]);

successResponse([
    'agent' => $agent,
    'quarter' => $quarter,
    'performances' => $performances,
    'totals' => [
        'early' => (float) $totals['total_early'],
        'early_formatted' => number_format((float) $totals['total_early']),
        'monthly' => (float) $totals['total_monthly'],
        'monthly_formatted' => number_format((float) $totals['total_monthly']),
        'count' => (int) $totals['total_count']
    ],
    'monthly_totals' => [
        'month' => $currentMonth,
        'early' => (float) $monthlyTotals['total_early'],
        'early_formatted' => number_format((float) $monthlyTotals['total_early']),
        'monthly' => (float) $monthlyTotals['total_monthly'],
        'monthly_formatted' => number_format((float) $monthlyTotals['total_monthly']),
        'count' => (int) $monthlyTotals['total_count']
    ]
]);
