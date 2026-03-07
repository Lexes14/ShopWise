<?php
declare(strict_types=1);

class AuditController extends ModuleController
{
    protected string $module = 'audit';
    protected string $title = 'Audit Logs';

    public function index(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT log_id, module, action, description, created_at
             FROM audit_logs
             ORDER BY created_at DESC
             LIMIT 200"
        );
        $this->moduleIndex($stmt->fetchAll());
    }

    public function detail(string $id): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT al.*, u.full_name
             FROM audit_logs al
             LEFT JOIN users u ON u.user_id = al.user_id
             WHERE al.log_id = ?
             LIMIT 1"
        );
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->error404('Audit log entry not found.');
        }

        $this->moduleSection('detail', ['extra' => ['id' => (int)$id, 'log' => $row]]);
    }

    public function exportCsv(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT log_id, module, action, description, ip_address, created_at
             FROM audit_logs
             ORDER BY created_at DESC
             LIMIT 5000"
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_export.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['log_id', 'module', 'action', 'description', 'ip_address', 'created_at']);
        foreach ($stmt->fetchAll() as $row) {
            fputcsv($out, [
                $row['log_id'],
                $row['module'],
                $row['action'],
                $row['description'],
                $row['ip_address'],
                $row['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }
}
