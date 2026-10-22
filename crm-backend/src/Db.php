<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Thin wrapper around one PDO connection. All queries use ? placeholders.
 */
final class Db
{
    private static ?PDO $pdo = null;
    private static int $transactionDepth = 0;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $config = Config::get('db');
            self::$pdo = new PDO($config['dsn'], $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '+00:00'");
        }

        return self::$pdo;
    }

    public static function all(string $sql, array $params = []): array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(array_values($params));

        return $statement->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(array_values($params));
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /** First column of the first row, or null when there is no row. */
    public static function value(string $sql, array $params = []): mixed
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(array_values($params));
        $value = $statement->fetchColumn();

        return $value === false ? null : $value;
    }

    /** Runs a statement and returns the number of affected rows. */
    public static function run(string $sql, array $params = []): int
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(array_values($params));

        return $statement->rowCount();
    }

    public static function insert(string $table, array $row): void
    {
        $columns = implode(', ', array_map(static fn (string $c): string => "`$c`", array_keys($row)));
        self::run("INSERT INTO `$table` ($columns) VALUES (" . self::marks($row) . ')', $row);
    }

    public static function update(string $table, array $row, string $where, array $whereParams): int
    {
        $set = implode(', ', array_map(static fn (string $c): string => "`$c` = ?", array_keys($row)));

        return self::run("UPDATE `$table` SET $set WHERE $where", [...array_values($row), ...$whereParams]);
    }

    /** "?, ?, ?" with one placeholder per item. */
    public static function marks(array $items): string
    {
        return implode(', ', array_fill(0, count($items), '?'));
    }

    /**
     * Runs $work in a transaction. Nested calls join the outer transaction,
     * so a bulk action that calls Records::update() many times commits or
     * rolls back as a whole.
     */
    public static function transaction(callable $work): mixed
    {
        $pdo = self::pdo();
        if (self::$transactionDepth === 0) {
            $pdo->beginTransaction();
        }
        self::$transactionDepth++;
        try {
            $result = $work();
            self::$transactionDepth--;
            if (self::$transactionDepth === 0) {
                $pdo->commit();
            }

            return $result;
        } catch (\Throwable $e) {
            self::$transactionDepth--;
            if (self::$transactionDepth === 0 && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
