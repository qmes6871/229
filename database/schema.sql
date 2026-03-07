-- 299본부 성과관리 CRM 데이터베이스 스키마

CREATE DATABASE IF NOT EXISTS crm_299 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crm_299;

-- 관리자 테이블
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'manager') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 팀 테이블
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    leader_id INT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 설계사 테이블
CREATE TABLE agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    team_id INT NULL,
    phone VARCHAR(20) NULL,
    join_date DATE NULL,
    position VARCHAR(50) DEFAULT '설계사',
    profile_image VARCHAR(255) NULL,
    prev_quarter_avg DECIMAL(12,2) DEFAULT 0,
    best_monthly_premium DECIMAL(15,2) DEFAULT 0,
    best_monthly_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 분기 테이블
CREATE TABLE quarters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    quarter TINYINT NOT NULL,
    name VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_quarter (year, quarter)
) ENGINE=InnoDB;

-- 일일 실적 테이블
CREATE TABLE daily_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    quarter_id INT NOT NULL,
    performance_date DATE NOT NULL,
    monthly_premium DECIMAL(15,2) DEFAULT 0 COMMENT '월납보험료',
    contract_count INT DEFAULT 0 COMMENT '계약건수',
    early_premium DECIMAL(15,2) DEFAULT 0 COMMENT '조기보험료',
    event_score DECIMAL(10,2) DEFAULT 0 COMMENT '이벤트점수',
    memo TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
    FOREIGN KEY (quarter_id) REFERENCES quarters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily (agent_id, performance_date)
) ENGINE=InnoDB;

-- 누적 실적 및 점수 테이블
CREATE TABLE cumulative_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    quarter_id INT NOT NULL,
    early_cumulative DECIMAL(15,2) DEFAULT 0 COMMENT '조기 누적',
    monthly_cumulative DECIMAL(15,2) DEFAULT 0 COMMENT '월납 누적',
    total_count INT DEFAULT 0 COMMENT '총 건수',
    event_cumulative DECIMAL(10,2) DEFAULT 0 COMMENT '이벤트 누적',
    three_w_weeks INT DEFAULT 0 COMMENT '3W 주차',
    attendance_count INT DEFAULT 0 COMMENT '출근일수',
    early_score DECIMAL(10,2) DEFAULT 0 COMMENT '조기가동점',
    monthly_score DECIMAL(10,2) DEFAULT 0 COMMENT '월납점',
    count_score DECIMAL(10,2) DEFAULT 0 COMMENT '건수점',
    three_w_score DECIMAL(10,2) DEFAULT 0 COMMENT '3W점',
    growth_score DECIMAL(10,2) DEFAULT 0 COMMENT '성장점',
    attendance_score DECIMAL(10,2) DEFAULT 0 COMMENT '근태점',
    event_score DECIMAL(10,2) DEFAULT 0 COMMENT '이벤트점',
    total_score DECIMAL(10,2) DEFAULT 0 COMMENT '종합점수',
    last_calculated TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
    FOREIGN KEY (quarter_id) REFERENCES quarters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cumulative (agent_id, quarter_id)
) ENGINE=InnoDB;

-- 명예의 전당
CREATE TABLE hall_of_fame (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    quarter_id INT NOT NULL,
    category ENUM('early', 'monthly', 'count', 'total') NOT NULL COMMENT '부문',
    rank_position TINYINT NOT NULL COMMENT '순위',
    score DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
    FOREIGN KEY (quarter_id) REFERENCES quarters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_fame (quarter_id, category, rank_position)
) ENGINE=InnoDB;

-- 시스템 설정
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    description VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 출근 기록
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'half', 'vacation') DEFAULT 'present',
    check_in_time TIME NULL,
    check_out_time TIME NULL,
    memo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (agent_id, attendance_date)
) ENGINE=InnoDB;

-- 초기 데이터

-- 기본 관리자 계정 (admin / prime299!)
INSERT INTO admins (username, password, name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '관리자', 'super_admin');

-- 현재 분기 설정
INSERT INTO quarters (year, quarter, name, start_date, end_date, is_current) VALUES
(2026, 1, '2026년 1분기', '2026-01-01', '2026-03-31', 1);

-- 기본 설정값
INSERT INTO settings (setting_key, setting_value, description) VALUES
('early_score_divisor', '10000', '조기가동점 나눔값'),
('monthly_score_divisor', '10000', '월납점 나눔값'),
('three_w_10_score', '2.0', '3W 10주 이상 점수'),
('three_w_5_score', '0.5', '3W 5주 이상 점수'),
('three_w_2_score', '0.2', '3W 2주 이상 점수'),
('growth_rate', '0.3', '성장점 비율'),
('attendance_full_score', '20', '만근 점수'),
('working_days_per_month', '22', '월 근무일수'),
('refresh_interval', '5000', '대시보드 새로고침 간격(ms)'),
('current_quarter_id', '1', '현재 분기 ID'),
('modal_event_content', '', '이벤트 확인 모달 내용'),
('modal_award_content', '', '분기시상 모달 내용');

-- 인덱스
CREATE INDEX idx_daily_date ON daily_performance(performance_date);
CREATE INDEX idx_daily_quarter ON daily_performance(quarter_id);
CREATE INDEX idx_cumulative_score ON cumulative_performance(total_score DESC);
CREATE INDEX idx_agents_active ON agents(is_active);
