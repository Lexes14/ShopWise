<?php
declare(strict_types=1);

class BackupController extends ModuleController
{
    protected string $module = 'backup';
    protected string $title = 'Backups';

    public function index(): void
    {
        $this->requireAuth(['owner']);
        $files = [];
        if (is_dir(BACKUP_PATH)) {
            $entries = scandir(BACKUP_PATH) ?: [];
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $fullPath = BACKUP_PATH . $entry;
                if (is_file($fullPath)) {
                    $files[] = [
                        'name' => $entry,
                        'size' => filesize($fullPath),
                        'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                    ];
                }
            }
        }
        $this->moduleIndex($files);
    }

    public function create(): void
    {
        $this->requireAuth(['owner']);
        Auth::csrfVerify();

        $timestamp = date('Ymd_His');
        $fileName = DB_NAME . '_backup_' . $timestamp . '.sql';
        $target = BACKUP_PATH . $fileName;

        if (!is_dir(BACKUP_PATH)) {
            mkdir(BACKUP_PATH, 0755, true);
        }

        $cmd = '"' . MYSQLDUMP_PATH . '" --host=' . escapeshellarg(DB_HOST)
            . ' --user=' . escapeshellarg(DB_USER)
            . (DB_PASS !== '' ? ' --password=' . escapeshellarg(DB_PASS) : '')
            . ' --single-transaction --quick --routines --triggers '
            . escapeshellarg(DB_NAME)
            . ' > ' . escapeshellarg($target);

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($target)) {
            $this->done('Backup creation failed.', '/backup');
        }

        $logger = new Logger();
        $logger->log('backup', 'create', null, null, ['file' => $fileName], 'Database backup created.');

        $this->done('Backup created: ' . $fileName, '/backup');
    }

    public function download(string $filename): void
    {
        $this->requireAuth(['owner']);
        $clean = basename($filename);
        $file = BACKUP_PATH . $clean;
        if (!is_file($file)) {
            $this->error404('Backup file not found.');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $clean . '"');
        readfile($file);
        exit;
    }

    public function restore(): void
    {
        $this->requireAuth(['owner']);
        Auth::csrfVerify();

        $fileName = basename((string)$this->post('filename', ''));
        if ($fileName === '') {
            $this->done('Backup filename is required.', '/backup');
        }

        $source = BACKUP_PATH . $fileName;
        if (!is_file($source)) {
            $this->done('Backup file not found.', '/backup');
        }

        $cmd = '"' . MYSQL_PATH . '" --host=' . escapeshellarg(DB_HOST)
            . ' --user=' . escapeshellarg(DB_USER)
            . (DB_PASS !== '' ? ' --password=' . escapeshellarg(DB_PASS) : '')
            . ' ' . escapeshellarg(DB_NAME)
            . ' < ' . escapeshellarg($source);

        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            $this->done('Backup restore failed.', '/backup');
        }

        $logger = new Logger();
        $logger->log('backup', 'restore', null, null, ['file' => $fileName], 'Database restore completed.');

        $this->done('Backup restore completed: ' . $fileName, '/backup');
    }
}
