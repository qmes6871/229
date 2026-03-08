<?php
/**
 * 299본부 성과관리 CRM - 점수 계산 클래스
 *
 * 점수 계산 공식:
 * - 조기가동점: 1만원당 0.5점 (월납에 추가점수)
 * - 월납점: 1만원당 0.5점
 * - 건수점: 1건당 0.5점
 * - 3W점: 1주당 0.5점
 * - 성장점: 직전분기 평균의 10% 성장당 1점
 * - 이벤트점: 1건당 1점
 * - 근태점: 출근 5점, 만근 10점
 * - 종합점수: 모든 점수 합산
 */

class ScoreCalculator {
    private Database $db;
    private array $settings;

    public function __construct() {
        $this->db = new Database();
        $this->loadSettings();
    }

    // 설정값 로드
    private function loadSettings(): void {
        $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings");
        $this->settings = [];
        foreach ($rows as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    // 설정값 가져오기
    private function getSetting(string $key, mixed $default = 0): mixed {
        return $this->settings[$key] ?? $default;
    }

    // 조기가동점 계산 (1만원당 0.5점)
    public function calculateEarlyScore(float $earlyPremium): float {
        $pointPer10000 = (float) $this->getSetting('early_score_per_10000', 0.5);
        return round(($earlyPremium / 10000) * $pointPer10000, 2);
    }

    // 월납점 계산 (1만원당 0.5점)
    public function calculateMonthlyScore(float $monthlyPremium): float {
        $pointPer10000 = (float) $this->getSetting('monthly_score_per_10000', 0.5);
        return round(($monthlyPremium / 10000) * $pointPer10000, 2);
    }

    // 건수점 계산 (1건당 0.5점)
    public function calculateCountScore(int $count): float {
        $pointPerCount = (float) $this->getSetting('count_score_per_count', 0.5);
        return round($count * $pointPerCount, 2);
    }

    // 3W점 계산 (1주당 0.5점)
    public function calculateThreeWScore(int $weeks): float {
        $pointPerWeek = (float) $this->getSetting('three_w_score_per_week', 0.5);
        return round($weeks * $pointPerWeek, 2);
    }

    // 성장점 계산 (10% 성장당 1점, 0.1점 미만은 0으로 처리)
    public function calculateGrowthScore(float $prevAvg, float $currentMonthly): float {
        if ($prevAvg <= 0) {
            return 0;
        }
        $growthPercent = (($currentMonthly - $prevAvg) / $prevAvg) * 100;
        if ($growthPercent <= 0) {
            return 0;
        }
        $pointPer10Percent = (float) $this->getSetting('growth_score_per_10percent', 1.0);
        $score = round(($growthPercent / 10) * $pointPer10Percent, 2);
        // 0.1점 미만은 0으로 처리
        return $score >= 0.1 ? $score : 0;
    }

    // 근태점 계산 (출근 5점, 만근 10점)
    public function calculateAttendanceScore(string $status): float {
        return match($status) {
            'present' => (float) $this->getSetting('attendance_present_score', 10),
            'partial' => (float) $this->getSetting('attendance_partial_score', 5),
            default => 0
        };
    }

    // 3W 주차 자동 계산 (월~일 기준, 주당 3건 이상이면 1주 달성, 연속 달성만 카운트)
    // 반환: ['current' => 현재 연속 주차, 'best' => 역대 최대 연속 주차(역사)]
    public function calculateThreeWWeeksData(int $agentId, int $quarterId): array {
        // 분기 내 모든 주차별 건수 합계 계산 (YEARWEEK: 월요일 시작 기준 mode 1)
        $weeklyContracts = $this->db->fetchAll("
            SELECT
                YEARWEEK(performance_date, 1) as year_week,
                SUM(contract_count) as weekly_count
            FROM daily_performance
            WHERE agent_id = ? AND quarter_id = ?
            GROUP BY YEARWEEK(performance_date, 1)
            ORDER BY year_week ASC
        ", [$agentId, $quarterId]);

        if (empty($weeklyContracts)) {
            return ['current' => 0, 'best' => 0];
        }

        // 분기 시작일과 현재 날짜 기준으로 모든 주차 목록 생성
        $quarter = $this->db->fetchOne("SELECT start_date, end_date FROM quarters WHERE id = ?", [$quarterId]);
        if (!$quarter) {
            return ['current' => 0, 'best' => 0];
        }

        $startDate = new DateTime($quarter['start_date']);
        $endDate = new DateTime($quarter['end_date']);
        $today = new DateTime();

        // 분기 종료일과 오늘 중 더 이른 날짜까지만 체크
        $checkEndDate = ($today < $endDate) ? $today : $endDate;

        // 달성한 주차들의 year_week를 배열로 변환
        $achievedWeeks = [];
        foreach ($weeklyContracts as $row) {
            if ((int)$row['weekly_count'] >= 3) {
                $achievedWeeks[] = $row['year_week'];
            }
        }

        // 분기 시작부터 현재까지의 모든 주차 목록 생성
        $allWeeks = [];
        $current = clone $startDate;
        while ($current <= $checkEndDate) {
            $yearWeek = $current->format('oW'); // ISO 8601 연도 + 주차
            // MySQL YEARWEEK(date, 1)과 동일한 형식으로 변환
            $yearWeek = (int)($current->format('o') . $current->format('W'));
            if (!in_array($yearWeek, $allWeeks)) {
                $allWeeks[] = $yearWeek;
            }
            $current->modify('+1 day');
        }
        sort($allWeeks);

        // 연속 달성 계산
        $currentStreak = 0;
        $bestStreak = 0;
        $tempStreak = 0;

        foreach ($allWeeks as $week) {
            if (in_array($week, $achievedWeeks)) {
                $tempStreak++;
                if ($tempStreak > $bestStreak) {
                    $bestStreak = $tempStreak;
                }
            } else {
                $tempStreak = 0;
            }
        }

        // 현재 연속 = 가장 마지막 주차부터 역순으로 연속된 달성 수
        $reversedWeeks = array_reverse($allWeeks);
        foreach ($reversedWeeks as $week) {
            if (in_array($week, $achievedWeeks)) {
                $currentStreak++;
            } else {
                break;
            }
        }

        return ['current' => $currentStreak, 'best' => $bestStreak];
    }

    // 기존 호환성을 위한 래퍼 함수 (현재 연속 주차 반환)
    public function calculateThreeWWeeks(int $agentId, int $quarterId): int {
        $data = $this->calculateThreeWWeeksData($agentId, $quarterId);
        return $data['current'];
    }

    // 분기 내 경과 월수 계산
    public function getElapsedMonthsInQuarter(int $quarterId): int {
        $quarter = $this->db->fetchOne("SELECT * FROM quarters WHERE id = ?", [$quarterId]);
        if (!$quarter) {
            return 1;
        }

        $startDate = new DateTime($quarter['start_date']);
        $today = new DateTime();
        $endDate = new DateTime($quarter['end_date']);

        // 오늘이 분기 종료일 이후면 3개월 전체
        if ($today > $endDate) {
            return 3;
        }

        // 오늘이 분기 시작일 이전이면 1개월
        if ($today < $startDate) {
            return 1;
        }

        // 경과 월수 계산 (최소 1, 최대 3)
        $months = ($today->format('Y') - $startDate->format('Y')) * 12
                + ($today->format('n') - $startDate->format('n')) + 1;

        return max(1, min(3, $months));
    }

    // 현재 월 평균 월납보험료 계산
    public function getCurrentMonthlyAverage(int $agentId, int $quarterId): float {
        $elapsedMonths = $this->getElapsedMonthsInQuarter($quarterId);

        $result = $this->db->fetchOne("
            SELECT COALESCE(SUM(monthly_premium), 0) as total_monthly
            FROM daily_performance
            WHERE agent_id = ? AND quarter_id = ?
        ", [$agentId, $quarterId]);

        $totalMonthly = (float) ($result['total_monthly'] ?? 0);

        return $totalMonthly / $elapsedMonths;
    }

    // 특정 설계사의 모든 점수 계산 및 업데이트
    public function calculateAndUpdate(int $agentId, int $quarterId): array {
        // 일일 실적 합계 조회
        $dailySum = $this->db->fetchOne("
            SELECT
                COALESCE(SUM(early_premium), 0) as early_cumulative,
                COALESCE(SUM(monthly_premium), 0) as monthly_cumulative,
                COALESCE(SUM(contract_count), 0) as total_count,
                COALESCE(SUM(event_score), 0) as event_cumulative
            FROM daily_performance
            WHERE agent_id = ? AND quarter_id = ?
        ", [$agentId, $quarterId]);

        // 설계사 정보 조회 (전분기 평균)
        $agent = $this->db->fetchOne("SELECT prev_quarter_avg FROM agents WHERE id = ?", [$agentId]);

        // 누적 실적 조회 또는 생성
        $cumulative = $this->db->fetchOne("
            SELECT * FROM cumulative_performance
            WHERE agent_id = ? AND quarter_id = ?
        ", [$agentId, $quarterId]);

        // 3W 주차 자동 계산 (월~일 기준, 주당 3건 이상 연속 달성)
        $threeWData = $this->calculateThreeWWeeksData($agentId, $quarterId);
        $threeWWeeks = $threeWData['current']; // 현재 연속 주차
        $threeWBest = $threeWData['best'];     // 역대 최대 연속 주차(역사)
        $attendanceCount = $cumulative['attendance_count'] ?? 0;

        // 근태 상태 조회 (월별 가장 최근 기록)
        $attendanceRecord = $this->db->fetchOne("
            SELECT status FROM attendance
            WHERE agent_id = ? AND quarter_id = ?
            ORDER BY attendance_date DESC
            LIMIT 1
        ", [$agentId, $quarterId]);
        $attendanceStatus = $attendanceRecord['status'] ?? '';

        // 점수 계산
        $earlyScore = $this->calculateEarlyScore((float) $dailySum['early_cumulative']);
        $monthlyScore = $this->calculateMonthlyScore((float) $dailySum['monthly_cumulative']);
        $countScore = $this->calculateCountScore((int) $dailySum['total_count']);
        $threeWScore = $this->calculateThreeWScore($threeWWeeks);

        // 성장점: 직전분기 평균의 10% 성장당 1점
        // 경과 월수에 따른 현재 분기 평균과 비교 (1월차: 1개월, 2월차: 2개월 평균, 3월차: 3개월 평균)
        $currentAvg = $this->getCurrentMonthlyAverage($agentId, $quarterId);
        $growthScore = $this->calculateGrowthScore(
            (float) ($agent['prev_quarter_avg'] ?? 0),
            $currentAvg
        );
        $attendanceScore = $this->calculateAttendanceScore($attendanceStatus);
        $eventScore = (float) $dailySum['event_cumulative'];

        // 종합점수
        $totalScore = $earlyScore + $monthlyScore + $countScore + $threeWScore + $growthScore + $attendanceScore + $eventScore;

        // 데이터 준비
        $data = [
            'agent_id' => $agentId,
            'quarter_id' => $quarterId,
            'early_cumulative' => $dailySum['early_cumulative'],
            'monthly_cumulative' => $dailySum['monthly_cumulative'],
            'total_count' => $dailySum['total_count'],
            'event_cumulative' => $dailySum['event_cumulative'],
            'three_w_weeks' => $threeWWeeks,
            'three_w_best' => $threeWBest,
            'attendance_count' => $attendanceCount,
            'early_score' => $earlyScore,
            'monthly_score' => $monthlyScore,
            'count_score' => $countScore,
            'three_w_score' => $threeWScore,
            'growth_score' => $growthScore,
            'attendance_score' => $attendanceScore,
            'event_score' => $eventScore,
            'total_score' => $totalScore,
            'last_calculated' => date('Y-m-d H:i:s')
        ];

        // 저장 (UPSERT)
        if ($cumulative) {
            $updateData = $data;
            unset($updateData['agent_id'], $updateData['quarter_id']);
            $this->db->update('cumulative_performance', $updateData, 'id = ?', [$cumulative['id']]);
        } else {
            $this->db->insert('cumulative_performance', $data);
        }

        return $data;
    }

    // 특정 분기의 모든 설계사 점수 재계산
    public function recalculateAll(int $quarterId): int {
        $agents = $this->db->fetchAll("SELECT id FROM agents WHERE is_active = 1");
        $count = 0;

        foreach ($agents as $agent) {
            $this->calculateAndUpdate($agent['id'], $quarterId);
            $count++;
        }

        return $count;
    }

    // 순위 조회 (분기별)
    public function getRanking(int $quarterId, string $orderBy = 'total_score', int $limit = 50): array {
        $allowedColumns = [
            'total_score', 'early_score', 'monthly_score', 'count_score',
            'three_w_score', 'growth_score', 'attendance_score', 'event_score',
            'early_cumulative', 'monthly_cumulative', 'total_count', 'event_cumulative'
        ];

        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'total_score';
        }

        $results = $this->db->fetchAll("
            SELECT
                cp.*,
                a.name,
                a.team_id,
                a.profile_image,
                a.position,
                a.join_date,
                a.prev_quarter_avg,
                a.best_monthly_premium,
                a.best_monthly_count,
                a.best_three_w,
                t.name as team_name,
                RANK() OVER (ORDER BY cp.{$orderBy} DESC) as `rank`
            FROM cumulative_performance cp
            JOIN agents a ON cp.agent_id = a.id
            LEFT JOIN teams t ON a.team_id = t.id
            WHERE cp.quarter_id = ? AND a.is_active = 1
            ORDER BY cp.{$orderBy} DESC
            LIMIT ?
        ", [$quarterId, $limit]);

        // 경과 월수 계산
        $elapsedMonths = $this->getElapsedMonthsInQuarter($quarterId);

        // 각 설계사의 추가 정보 계산
        foreach ($results as &$row) {
            // 성장률 계산 (경과 월수 기준 평균, 0 미만은 0으로 표시)
            $prevAvg = (float) ($row['prev_quarter_avg'] ?? 0);
            $currentMonthly = (float) ($row['monthly_cumulative'] ?? 0);
            $currentAvg = $currentMonthly / $elapsedMonths;
            if ($prevAvg > 0) {
                $growthRate = round((($currentAvg - $prevAvg) / $prevAvg) * 100, 1);
                $row['growth_rate'] = $growthRate >= 0.1 ? $growthRate : 0;
            } else {
                $row['growth_rate'] = 0;
            }

            // 근태상태 조회 (최신 월 기준)
            $attendance = $this->db->fetchOne("
                SELECT status FROM attendance
                WHERE agent_id = ? AND quarter_id = ?
                ORDER BY attendance_date DESC
                LIMIT 1
            ", [$row['agent_id'], $quarterId]);

            $statusMap = [
                'absent' => '미출근',
                'partial' => '출근',
                'present' => '만근'
            ];
            $row['attendance_status'] = $attendance ? ($statusMap[$attendance['status']] ?? '-') : '-';
        }

        return $results;
    }

    // 월별 순위 조회
    public function getMonthlyRanking(int $quarterId, int $month, string $orderBy = 'total_score', int $limit = 50): array {
        $allowedColumns = [
            'total_score', 'early_score', 'monthly_score', 'count_score', 'event_score',
            'early_cumulative', 'monthly_cumulative', 'total_count', 'event_cumulative'
        ];

        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'total_score';
        }

        // 분기 정보 조회
        $quarter = $this->db->fetchOne("SELECT year FROM quarters WHERE id = ?", [$quarterId]);
        $year = $quarter['year'] ?? date('Y');

        // 월별 실적 합계 계산
        $results = $this->db->fetchAll("
            SELECT
                a.id as agent_id,
                a.name,
                a.team_id,
                a.profile_image,
                a.position,
                a.join_date,
                a.prev_quarter_avg,
                a.best_monthly_premium,
                a.best_monthly_count,
                a.best_three_w,
                t.name as team_name,
                COALESCE(SUM(dp.early_premium), 0) as early_cumulative,
                COALESCE(SUM(dp.monthly_premium), 0) as monthly_cumulative,
                COALESCE(SUM(dp.contract_count), 0) as total_count,
                COALESCE(SUM(dp.event_score), 0) as event_cumulative
            FROM agents a
            LEFT JOIN teams t ON a.team_id = t.id
            LEFT JOIN daily_performance dp ON a.id = dp.agent_id
                AND dp.quarter_id = ?
                AND MONTH(dp.performance_date) = ?
                AND YEAR(dp.performance_date) = ?
            WHERE a.is_active = 1
            GROUP BY a.id, a.name, a.team_id, a.profile_image, a.position, a.join_date, a.prev_quarter_avg, a.best_monthly_premium, a.best_monthly_count, a.best_three_w, t.name
        ", [$quarterId, $month, $year]);

        // 점수 계산
        foreach ($results as &$row) {
            $row['early_score'] = $this->calculateEarlyScore((float) $row['early_cumulative']);
            $row['monthly_score'] = $this->calculateMonthlyScore((float) $row['monthly_cumulative']);
            $row['count_score'] = $this->calculateCountScore((int) $row['total_count']);
            $row['event_score'] = (float) $row['event_cumulative'];

            // cumulative_performance에서 3W, 성장, 근태 점수 가져오기
            $cpData = $this->db->fetchOne("
                SELECT three_w_weeks, three_w_score, growth_score, attendance_score, event_score as cp_event_score
                FROM cumulative_performance
                WHERE agent_id = ? AND quarter_id = ?
            ", [$row['agent_id'], $quarterId]);

            $row['three_w_weeks'] = $cpData['three_w_weeks'] ?? 0;
            $row['three_w_score'] = (float) ($cpData['three_w_score'] ?? 0);
            $row['growth_score'] = (float) ($cpData['growth_score'] ?? 0);
            $row['attendance_score'] = (float) ($cpData['attendance_score'] ?? 0);

            // 이벤트 점수는 cumulative_performance 값 사용 (관리자가 설정한 값)
            if ($cpData && (float)$cpData['cp_event_score'] > 0) {
                $row['event_score'] = (float) $cpData['cp_event_score'];
            }

            // 성장률 계산 (0 미만은 0으로 표시)
            $prevAvg = (float) ($row['prev_quarter_avg'] ?? 0);
            $currentMonthly = (float) ($row['monthly_cumulative'] ?? 0);
            if ($prevAvg > 0) {
                $growthRate = round((($currentMonthly - $prevAvg) / $prevAvg) * 100, 1);
                $row['growth_rate'] = $growthRate >= 0.1 ? $growthRate : 0;
            } else {
                $row['growth_rate'] = 0;
            }

            // 근태상태 조회
            $attendance = $this->db->fetchOne("
                SELECT status FROM attendance
                WHERE agent_id = ? AND quarter_id = ?
                ORDER BY attendance_date DESC
                LIMIT 1
            ", [$row['agent_id'], $quarterId]);

            $statusMap = [
                'absent' => '미출근',
                'partial' => '출근',
                'present' => '만근'
            ];
            $row['attendance_status'] = $attendance ? ($statusMap[$attendance['status']] ?? '-') : '-';

            // 종합점수 (모든 점수 합산)
            $row['total_score'] = $row['early_score'] + $row['monthly_score'] + $row['count_score'] +
                                  $row['three_w_score'] + $row['growth_score'] + $row['attendance_score'] + $row['event_score'];
        }

        // 정렬
        usort($results, function($a, $b) use ($orderBy) {
            return $b[$orderBy] <=> $a[$orderBy];
        });

        // 순위 부여
        $rank = 0;
        $prevScore = null;
        foreach ($results as $index => &$row) {
            if ($prevScore !== $row[$orderBy]) {
                $rank = $index + 1;
            }
            $row['rank'] = $rank;
            $prevScore = $row[$orderBy];
        }

        // 제한
        return array_slice($results, 0, $limit);
    }

    // 3W 주차 업데이트
    public function updateThreeWWeeks(int $agentId, int $quarterId, int $weeks): bool {
        return $this->db->execute("
            INSERT INTO cumulative_performance (agent_id, quarter_id, three_w_weeks)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE three_w_weeks = ?
        ", [$agentId, $quarterId, $weeks, $weeks]);
    }
}
