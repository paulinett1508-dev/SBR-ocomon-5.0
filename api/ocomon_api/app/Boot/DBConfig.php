<?php

require_once(__DIR__ . "/" . "../../../../includes/config.inc.php");

/**
 * Configuração de conexão do DataLayer para PostgreSQL (Supabase).
 * Migrado de MySQL para PostgreSQL — auditoria 2026-03-08.
 *
 * ATENÇÃO: CoffeeCode DataLayer é MySQL-específico e será substituído
 * por illuminate/database (Eloquent) na Fase 3 da migração.
 * Ver: changelog/AUDIT-2026-03-08.md — Seção 4.3
 */
define("DATA_LAYER_CONFIG", [
    "driver"   => "pgsql",
    "host"     => SQL_SERVER,
    "port"     => defined('SQL_PORT') ? SQL_PORT : "5432",
    "dbname"   => SQL_DB,
    "username" => SQL_USER,
    "passwd"   => SQL_PASSWD,
    "options"  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
]);
