<?php
/**
 * 순위 API
 * GET /api/dashboard/ranking.php
 * Parameters:
 *   - quarter_id: 분기 ID
 *   - order_by: 정렬 기준 (total_score, early_score, monthly_score, count_score)
 *   - limit: 조회 개수
 *   - period: quarter (분기별) 또는 month (월별)
 *   - month: 월 (1-12, period=month일 때 사용)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/ScoreCalculator.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$db = new Database();

// 파라미터
$quarterId = isset($_GET['quarter_id']) ? (int) $_GET['quarter_id'] : null;
$orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'total_score';
$limit = isset($_GET['limit']) ? min((int) $_GET['limit'], 100) : 50;
$period = isset($_GET['period']) ? $_GET['period'] : 'quarter';
$month = isset($_GET['month']) ? (int) $_GET['month'] : date('n');

// 현재 분기 기본값
if (!$quarterId) {
    $quarter = $db->getCurrentQuarter();
    $quarterId = $quarter['id'] ?? null;
}

if (!$quarterId) {
    errorResponse('활성화된 분기가 없습니다.');
}

$calculator = new ScoreCalculator();

// 기간별 순위 조회
if ($period === 'month') {
    // 월별 순위 조회
    $rankings = $calculator->getMonthlyRanking($quarterId, $month, $orderBy, $limit);
} else {
    // 분기별 순위 조회
    $rankings = $calculator->getRanking($quarterId, $orderBy, $limit);
}

// 순위별 스타일 정보 추가
foreach ($rankings as &$rank) {
    $position = (int) $rank['rank'];
    $rank['style'] = match($position) {
        1 => ['class' => 'gold', 'icon' => '🥇'],
        2 => ['class' => 'silver', 'icon' => '🥈'],
        3 => ['class' => 'bronze', 'icon' => '🥉'],
        default => ['class' => 'normal', 'icon' => '']
    };

    // 숫자 포맷팅
    $rank['early_cumulative_formatted'] = number_format((float) $rank['early_cumulative']);
    $rank['monthly_cumulative_formatted'] = number_format((float) $rank['monthly_cumulative']);
    $rank['total_score_formatted'] = number_format((float) $rank['total_score'], 1);
}

// 분기 정보 (target_score 포함)
$quarterInfo = $db->fetchOne("SELECT *, COALESCE(target_score, 200) as target_score FROM quarters WHERE id = ?", [$quarterId]);

successResponse([
    'rankings' => $rankings,
    'quarter' => $quarterInfo,
    'target_score' => (int) ($quarterInfo['target_score'] ?? 200),
    'period' => $period,
    'month' => $month,
    'order_by' => $orderBy,
    'updated_at' => date('Y-m-d H:i:s')
]);
