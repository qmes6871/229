<?php
/**
 * 근태 관리 API
 * GET /api/performance/attendance.php - 특정 월의 근태 조회
 * POST /api/performance/attendance.php - 근태 저장 (월 1회)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

$auth = new Auth();
$db = new Database();

// 현재 분기 가져오기
$currentQuarter = $db->fetchOne("SELECT * FROM quarters WHERE is_current = 1 LIMIT 1");
if (!$currentQuarter) {
    errorResponse('현재 분기가 설정되지 않았습니다.', 400);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 특정 월의 근태 조회
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-01');
    // 해당 월의 첫째 날로 정규화
    $monthDate = substr($date, 0, 7) . '-01';

    // 모든 활성 설계사 가져오기
    $agents = $db->fetchAll("
        SELECT
            a.id, a.name, a.position, a.profile_image,
            t.name as team_name
        FROM agents a
        LEFT JOIN teams t ON a.team_id = t.id
        WHERE a.is_active = 1
        ORDER BY a.name
    ");

    // 해당 월의 근태 기록 가져오기
    $attendanceRecords = $db->fetchAll("
        SELECT agent_id, status FROM attendance
        WHERE quarter_id = ? AND attendance_date = ?
    ", [$currentQuarter['id'], $monthDate]);

    $attendanceMap = [];
    foreach ($attendanceRecords as $record) {
        $attendanceMap[$record['agent_id']] = $record['status'];
    }

    // 설계사별 근태 상태 추가
    foreach ($agents as &$agent) {
        $agent['attendance_status'] = $attendanceMap[$agent['id']] ?? null;
    }

    // 분기 전체 근태 요약
    $summary = $db->fetchAll("
        SELECT
            a.id,
            a.name,
            a.profile_image,
            t.name as team_name,
            cp.attendance_count,
            cp.attendance_score
        FROM agents a
        LEFT JOIN teams t ON a.team_id = t.id
        LEFT JOIN cumulative_performance cp ON a.id = cp.agent_id AND cp.quarter_id = ?
        WHERE a.is_active = 1
        ORDER BY cp.attendance_score DESC, a.name
    ", [$currentQuarter['id']]);

    successResponse([
        'date' => $monthDate,
        'agents' => $agents,
        'summary' => $summary,
        'quarter' => [
            'id' => $currentQuarter['id'],
            'name' => $currentQuarter['year'] . '년 ' . $currentQuarter['quarter'] . '분기'
        ]
    ]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 인증 확인
    $auth->requireAuth();

    $input = json_decode(file_get_contents('php://input'), true);

    $date = $input['date'] ?? date('Y-m-01');
    // 해당 월의 첫째 날로 정규화
    $monthDate = substr($date, 0, 7) . '-01';
    $attendanceData = $input['attendance'] ?? [];

    if (empty($attendanceData)) {
        errorResponse('근태 데이터가 없습니다.');
    }

    $successCount = 0;

    foreach ($attendanceData as $item) {
        $agentId = (int) ($item['agent_id'] ?? 0);
        $status = $item['status'] ?? ''; // absent, partial, present

        if (!$agentId || !$status) continue;

        // 기존 기록 확인
        $existing = $db->fetchOne(
            "SELECT id FROM attendance WHERE agent_id = ? AND quarter_id = ? AND attendance_date = ?",
            [$agentId, $currentQuarter['id'], $monthDate]
        );

        if ($existing) {
            // 업데이트
            $db->update('attendance', [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$existing['id']]);
        } else {
            // 새로 등록
            $db->insert('attendance', [
                'agent_id' => $agentId,
                'quarter_id' => $currentQuarter['id'],
                'attendance_date' => $monthDate,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // 근태 점수 계산 (미출근: 0, 출근: 10, 만근: 20)
        $attendanceScore = 0;
        if ($status === 'present') {
            $attendanceScore = 20;
        } elseif ($status === 'partial') {
            $attendanceScore = 10;
        } elseif ($status === 'absent') {
            $attendanceScore = 0;
        }

        // 분기 내 총 근태 횟수 및 점수 계산
        $quarterAttendance = $db->fetchAll(
            "SELECT status FROM attendance WHERE agent_id = ? AND quarter_id = ?",
            [$agentId, $currentQuarter['id']]
        );

        $totalScore = 0;
        $attendanceCount = 0;
        foreach ($quarterAttendance as $att) {
            if ($att['status'] === 'present') {
                $totalScore += 20;
                $attendanceCount++;
            } elseif ($att['status'] === 'partial') {
                $totalScore += 10;
                $attendanceCount++;
            }
        }

        // cumulative_performance 업데이트
        $existingPerf = $db->fetchOne(
            "SELECT id FROM cumulative_performance WHERE agent_id = ? AND quarter_id = ?",
            [$agentId, $currentQuarter['id']]
        );

        if ($existingPerf) {
            $db->update('cumulative_performance', [
                'attendance_count' => $attendanceCount,
                'attendance_score' => $totalScore,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$existingPerf['id']]);
        } else {
            $db->insert('cumulative_performance', [
                'agent_id' => $agentId,
                'quarter_id' => $currentQuarter['id'],
                'attendance_count' => $attendanceCount,
                'attendance_score' => $totalScore,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $successCount++;
    }

    successResponse([
        'saved_count' => $successCount
    ], '근태가 저장되었습니다.');

} else {
    errorResponse('Method not allowed', 405);
}
