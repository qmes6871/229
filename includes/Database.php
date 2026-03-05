<?php
/**
 * 299본부 성과관리 CRM - Database 클래스
 */

class Database {
    private static ?PDO $instance = null;
    private PDO $pdo;

    public function __construct() {
        $this->pdo = self::getInstance();
    }

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    die("Database connection failed: " . $e->getMessage());
                }
                die("Database connection failed");
            }
        }
        return self::$instance;
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }

    // SELECT 쿼리 실행 - 여러 행
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // SELECT 쿼리 실행 - 단일 행
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // SELECT 쿼리 실행 - 단일 값
    public function fetchColumn(string $sql, array $params = []): mixed {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    // INSERT 쿼리 실행
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    // UPDATE 쿼리 실행
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...array_values($data), ...$whereParams]);

        return $stmt->rowCount();
    }

    // DELETE 쿼리 실행
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    // 일반 쿼리 실행
    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // 트랜잭션 시작
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    // 트랜잭션 커밋
    public function commit(): bool {
        return $this->pdo->commit();
    }

    // 트랜잭션 롤백
    public function rollback(): bool {
        return $this->pdo->rollBack();
    }

    // 설정값 가져오기
    public function getSetting(string $key): ?string {
        $result = $this->fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = ?",
            [$key]
        );
        return $result['setting_value'] ?? null;
    }

    // 설정값 저장
    public function setSetting(string $key, string $value): bool {
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = ?";
        return $this->execute($sql, [$key, $value, $value]);
    }

    // 현재 분기 가져오기
    public function getCurrentQuarter(): ?array {
        return $this->fetchOne("SELECT * FROM quarters WHERE is_current = 1 LIMIT 1");
    }
}
