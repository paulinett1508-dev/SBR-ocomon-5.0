<?php

namespace includes\classes;

/**
 * Class ConnectPDO
 *
 * Singleton de conexão PDO para PostgreSQL via Supabase Session Pooler.
 * Migrado de MySQL para PostgreSQL — auditoria 2026-03-08.
 *
 * @package includes\classes
 */
class ConnectPDO
{
    /** @const array */
    private const OPTIONS = [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_CASE               => \PDO::CASE_NATURAL,
        \PDO::ATTR_EMULATE_PREPARES   => false, // PostgreSQL suporta prepared statements nativos
    ];

    /** @var \PDO */
    private static $instance;

    /**
     * @return null|\PDO
     */
    public static function getInstance(): ?\PDO
    {
        if (empty(self::$instance)) {
            try {
                $dsn = sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
                    SQL_SERVER,
                    defined('SQL_PORT') ? SQL_PORT : '5432',
                    SQL_DB
                );

                self::$instance = new \PDO(
                    $dsn,
                    SQL_USER,
                    SQL_PASSWD,
                    self::OPTIONS
                );
            } catch (\PDOException $exception) {
                error_log('ConnectPDO failed: ' . $exception->getMessage());
                echo 'Connection failed: ' . $exception->getMessage();
            }
        }

        return self::$instance;
    }

    /**
     * ConnectPDO constructor — privado (singleton).
     */
    final private function __construct()
    {
    }
}
