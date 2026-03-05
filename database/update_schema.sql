-- 299본부 성과관리 CRM - 스키마 업데이트
-- 실행 전 백업 필수

-- attendance 테이블에 quarter_id 컬럼 추가
ALTER TABLE attendance ADD COLUMN quarter_id INT NULL AFTER agent_id;

-- attendance 테이블의 status 컬럼 수정 (partial 추가)
ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'absent', 'partial', 'half', 'vacation') DEFAULT 'present';

-- 기존 데이터 마이그레이션: 현재 분기 ID로 설정
UPDATE attendance SET quarter_id = (SELECT id FROM quarters WHERE is_current = 1 LIMIT 1) WHERE quarter_id IS NULL;

-- 외래키 추가 (선택사항)
-- ALTER TABLE attendance ADD FOREIGN KEY (quarter_id) REFERENCES quarters(id) ON DELETE CASCADE;

-- 인덱스 추가
CREATE INDEX IF NOT EXISTS idx_attendance_quarter ON attendance(quarter_id);
CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(attendance_date);
