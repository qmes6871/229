<?php
/**
 * 분기 목록 API
 * GET /api/dashboard/quarters.php
 * - 모든 분기 목록 반환 (연도별 그룹핑)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$db = new Database();

// 모든 분기 조회 (최신순)
$quarters = $db->fetchAll("
    SELECT
        id,
        year,
        quarter,
        start_date,
        end_date,
        is_current
    FROM quarters
    ORDER BY year DESC, quarter DESC
");

// 연도 목록 추출
$years = array_values(array_unique(array_column($quarters, 'year')));
sort($years, SORT_DESC);

// 현재 분기
$currentQuarter = $db->getCurrentQuarter();

successResponse([
    'quarters' => $quarters,
    'years' => $years,
    'current' => $currentQuarter
]);
