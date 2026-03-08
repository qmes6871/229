<?php
/**
 * 명예의 전당 API
 * GET /api/dashboard/hall-of-fame.php
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

// 각 부문별 1위 조회
$categories = [
    'early' => ['field' => 'early_score', 'label' => '조기가동 MVP', 'cumulative' => 'early_cumulative'],
    'monthly' => ['field' => 'monthly_score', 'label' => '월납 MVP', 'cumulative' => 'monthly_cumulative'],
    'count' => ['field' => 'count_score', 'label' => '건수 MVP', 'cumulative' => 'total_count'],
    'event' => ['field' => 'event_score', 'label' => '이벤트 MVP', 'cumulative' => 'event_cumulative'],
    'three_w' => ['field' => 'three_w_score', 'label' => '3W MVP', 'cumulative' => 'three_w_weeks'],
    'total' => ['field' => 'total_score', 'label' => '종합 MVP', 'cumulative' => null]
];

$hallOfFame = [];

// 랜덤 설계사용 - 활성 설계사 목록
$allAgents = $db->fetchAll("
    SELECT a.id, a.name, a.profile_image, a.position, t.name as team_name
    FROM agents a
    LEFT JOIN teams t ON a.team_id = t.id
    WHERE a.is_active = 1
");

foreach ($categories as $key => $category) {
    $sql = "
        SELECT
            cp.*,
            a.name,
            a.profile_image,
            a.position,
            t.name as team_name
        FROM cumulative_performance cp
        JOIN agents a ON cp.agent_id = a.id
        LEFT JOIN teams t ON a.team_id = t.id
        WHERE cp.quarter_id = ? AND a.is_active = 1
        ORDER BY cp.{$category['field']} DESC
        LIMIT 1
    ";

    $top = $db->fetchOne($sql, [$quarterId]);

    // 점수가 0이거나 데이터가 없으면 랜덤 설계사 선택
    if (!$top || (float) $top[$category['field']] <= 0) {
        // 랜덤 설계사 선택
        if (!empty($allAgents)) {
            $randomAgent = $allAgents[array_rand($allAgents)];
            $hallOfFame[$key] = [
                'category' => $key,
                'label' => $category['label'],
                'agent_id' => $randomAgent['id'],
                'name' => $randomAgent['name'],
                'profile_image' => $randomAgent['profile_image'],
                'position' => $randomAgent['position'],
                'team_name' => $randomAgent['team_name'],
                'score' => 0,
                'value' => 0,
                'is_pending' => true  // 달성예정 표시
            ];
        }
    } else {
        $hallOfFame[$key] = [
            'category' => $key,
            'label' => $category['label'],
            'agent_id' => $top['agent_id'],
            'name' => $top['name'],
            'profile_image' => $top['profile_image'],
            'position' => $top['position'],
            'team_name' => $top['team_name'],
            'score' => (float) $top[$category['field']],
            'value' => $category['cumulative'] ? (float) $top[$category['cumulative']] : (float) $top['total_score'],
            'is_pending' => false
        ];
    }
}

// 분기 정보
$quarterInfo = $db->fetchOne("SELECT * FROM quarters WHERE id = ?", [$quarterId]);

successResponse([
    'hall_of_fame' => $hallOfFame,
    'quarter' => $quarterInfo
]);
