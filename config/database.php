<?php
/**
 * ShopWise AI — Database Configuration
 * 
 * Provides a PDO singleton with secure connection settings.
 * All errors are logged to file — never exposed to the browser.
 *
 * @package ShopWiseAI\Config
 */

declare(strict_types=1);

class Database
{
    /** @var PDO|null Singleton PDO instance */
    private static ?PDO $instance = null;

    // ── Connection Credentials ─────────────────────────────────────────────
    private static string $host     = 'localhost';
    private static string $dbName   = 'shopwise_db';
    private static string $username = 'root';
    private static string $password = '';
    private static string $charset  = 'utf8mb4';
    private static int    $port     = 3306;

    /**
     * Private constructor — prevents direct instantiation.
     */
    private function __construct() {}

    /**
     * Private clone — prevents cloning the singleton.
     */
    private function __clone() {}

    /**
     * Returns the singleton PDO instance.
     * Creates connection on first call; reuses on subsequent calls.
     *
     * @throws RuntimeException If connection fails (logged, not exposed)
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    /**
     * Creates a new PDO connection with production-safe settings.
     *
     * @return PDO
     */
    private static function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            self::$host,
            self::$port,
            self::$dbName,
            self::$charset
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ];

        try {
            return new PDO($dsn, self::$username, self::$password, $options);
        } catch (PDOException $e) {
            // Log error to file — NEVER expose credentials or connection details
            $logMessage = sprintf(
                "[%s] DB Connection Failed: %s\n",
                date('Y-m-d H:i:s'),
                $e->getMessage()
            );

            $logFile = defined('LOG_PATH') ? LOG_PATH . 'db_errors.log' : __DIR__ . '/../logs/db_errors.log';

            // Attempt to write log — suppress errors if logs dir doesn't exist
            @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

            // Throw a sanitized exception — no credentials in message
            throw new RuntimeException(
                'Database connection unavailable. Please contact your system administrator.',
                500
            );
        }
    }

    /**
     * Resets the singleton (useful for testing or reconnection after error).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Returns the current database name.
     *
     * @return string
     */
    public static function getDbName(): string
    {
        return self::$dbName;
    }
}