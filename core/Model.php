<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║                      SHOPWISE AI — BASE MODEL                       ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * All models extend this base class.
 * Provides common database operations using PDO.
 */

declare(strict_types=1);

class Model
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find record by ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Find all records
     */
    public function findAll(array $conditions = [], string $orderBy = '', int $limit = 0): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . $this->buildWhereClause($conditions, $params);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Find one record by conditions
     */
    public function findOne(array $conditions): ?array
    {
        $params = [];
        $sql = "SELECT * FROM {$this->table} WHERE " . $this->buildWhereClause($conditions, $params) . " LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Insert new record
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Update record
     */
    public function update(int $id, array $data): bool
    {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "$column = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . 
               " WHERE {$this->primaryKey} = ?";
        
        $params = array_values($data);
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete record (hard delete)
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Soft delete (set status to 'archived')
     */
    public function softDelete(int $id): bool
    {
        return $this->update($id, ['status' => 'archived']);
    }
    
    /**
     * Execute raw query
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Count records
     */
    public function count(array $conditions = []): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . $this->buildWhereClause($conditions, $params);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Check if record exists
     */
    public function exists(array $conditions): bool
    {
        return $this->count($conditions) > 0;
    }
    
    /**
     * Paginate results
     */
    public function paginate(int $page = 1, int $perPage = 25, array $conditions = [], string $orderBy = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . $this->buildWhereClause($conditions, $params);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        $sql .= " LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        $total = $this->count($conditions);
        
        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    /**
     * Build WHERE clause from conditions array
     */
    protected function buildWhereClause(array $conditions, array &$params): string
    {
        $clauses = [];
        
        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                // Handle IN clause
                $placeholders = array_fill(0, count($value), '?');
                $clauses[] = "$column IN (" . implode(', ', $placeholders) . ")";
                $params = array_merge($params, $value);
            } else {
                $clauses[] = "$column = ?";
                $params[] = $value;
            }
        }
        
        return implode(' AND ', $clauses);
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->db->rollBack();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}
