<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use PDO;
use RuntimeException;

final class Database
{
    /** @return array<string,mixed> */
    public static function loadRBTConfig(string $path): array
    {
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($data) || !isset($data['db']) || !is_array($data['db'])) {
            throw new RuntimeException('RBT database configuration is missing');
        }
        return $data;
    }

    /** @param array<string,mixed> $config */
    public static function connect(array $config): PDO
    {
        $db = $config['db'] ?? null;
        if (!is_array($db) || !is_string($db['dsn'] ?? null)) {
            throw new RuntimeException('RBT database DSN is missing');
        }
        $options = is_array($db['options'] ?? null) ? $db['options'] : [];
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        $pdo = new PDO(
            $db['dsn'],
            is_string($db['username'] ?? null) ? $db['username'] : null,
            is_string($db['password'] ?? null) ? $db['password'] : null,
            $options,
        );
        $schema = $db['schema'] ?? null;
        if (is_string($schema) && $schema !== '') {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $schema)) {
                throw new RuntimeException('RBT database schema identifier is invalid');
            }
            $pdo->exec('SET search_path TO "' . $schema . '", public');
        }
        return $pdo;
    }
}
