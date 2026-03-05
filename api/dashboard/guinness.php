<?php
/**
 * 기네스 명예의 전당 API (역대 최고 기록)
 * GET /api/dashboard/guinness.php
 * - 설계사 등록 시 입력한 best_monthly_premium, best_monthly_count 기준
 * - 비활성 설계사도 포함 (역대 기록이므로)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$db = new Database();

// 월납보험료 역대 최고 (best_monthly_premium 기준)
$bestPremium = $db->fetchOne("
    SELECT
        a.id,
        a.name,
        a.position,
        a.profile_image,
        a.best_monthly_premium,
        a.is_active,
        t.name as team_name
    FROM agents a
    LEFT JOIN teams t ON a.team_id = t.id
    WHERE a.best_monthly_premium > 0
    ORDER BY a.best_monthly_premium DESC
    LIMIT 1
");

// 월 건수 역대 최고 (best_monthly_count 기준)
$bestCount = $db->fetchOne("
    SELECT
        a.id,
        a.name,
        a.position,
        a.profile_image,
        a.best_monthly_count,
        a.is_active,
        t.name as team_name
    FROM agents a
    LEFT JOIN teams t ON a.team_id = t.id
    WHERE a.best_monthly_count > 0
    ORDER BY a.best_monthly_count DESC
    LIMIT 1
");

// 모든 설계사의 역대 최고 기록 (버튼 클릭시)
$showAll = isset($_GET['all']) && $_GET['all'] === 'true';
$allRecords = [];

if ($showAll) {
    $allRecords = $db->fetchAll("
        SELECT
            a.id,
            a.name,
            a.position,
            a.profile_image,
            a.best_monthly_premium,
            a.best_monthly_count,
            a.is_active,
            t.name as team_name
        FROM agents a
        LEFT JOIN teams t ON a.team_id = t.id
        WHERE a.best_monthly_premium > 0 OR a.best_monthly_count > 0
        ORDER BY a.best_monthly_premium DESC
    ");
}

successResponse([
    'guinness' => [
        'premium' => $bestPremium ? [
            'id' => $bestPremium['id'],
            'name' => $bestPremium['name'],
            'position' => $bestPremium['position'],
            'profile_image' => $bestPremium['profile_image'],
            'team_name' => $bestPremium['team_name'],
            'is_active' => (bool) $bestPremium['is_active'],
            'record' => (float) $bestPremium['best_monthly_premium'],
            'record_formatted' => number_format((float) $bestPremium['best_monthly_premium']) . '원'
        ] : null,
        'count' => $bestCount ? [
            'id' => $bestCount['id'],
            'name' => $bestCount['name'],
            'position' => $bestCount['position'],
            'profile_image' => $bestCount['profile_image'],
            'team_name' => $bestCount['team_name'],
            'is_active' => (bool) $bestCount['is_active'],
            'record' => (int) $bestCount['best_monthly_count'],
            'record_formatted' => $bestCount['best_monthly_count'] . '건'
        ] : null
    ],
    'all_records' => $allRecords
]);
