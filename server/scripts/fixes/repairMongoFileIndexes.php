<?php

// Repair indexes created with a duplicated metadata prefix; file documents are not modified
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

try {
    require_once __DIR__ . '/../../vendor/autoload.php';

    $raw = file_get_contents(__DIR__ . '/../../config/config.json');
    if ($raw === false) {
        throw new RuntimeException('Cannot read the server configuration');
    }
    $config = function_exists('json5_decode')
        ? json5_decode($raw, true)
        : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

    if (($config['backends']['files']['backend'] ?? null) !== 'mongo') {
        throw new RuntimeException('This fix requires the files/mongo backend');
    }

    $database = ($config['backends']['files']['db'] ?? null) ?: 'rbt';
    $uri = ($config['backends']['files']['uri'] ?? null) ?: ($config['mongo']['uri'] ?? null);
    $client = $uri ? new MongoDB\Client($uri) : new MongoDB\Client();
    $files = $client->selectDatabase($database)->selectCollection('fs.files');

    $hasReplacement = static function (array $indexes, string $field): bool {
        foreach ($indexes as $index) {
            if ((array)$index->getKey() === [$field => 1]
                && !($index['partialFilterExpression'] ?? false)
                && !($index['sparse'] ?? false)
                && !($index['hidden'] ?? false)
                && !isset($index['expireAfterSeconds'])
                && (!isset($index['collation']) || ($index['collation']['locale'] ?? null) === 'simple')) {
                return true;
            }
        }
        return false;
    };

    $indexes = iterator_to_array($files->listIndexes());
    $incorrect = [];
    foreach ($indexes as $index) {
        $keys = (array)$index->getKey();
        $field = array_key_first($keys);
        if (count($keys) === 1 && str_starts_with($field, 'metadata.metadata.')
            && $keys[$field] === 1 && $index->getName() === 'index_' . $field) {
            $incorrect[$index->getName()] = substr($field, strlen('metadata.'));
        }
    }

    echo "Database: $database; incorrect file indexes: " . count($incorrect) . "\n";

    // Finish creating and verifying every replacement before removing any old index
    foreach ($incorrect as $field) {
        if (!$hasReplacement($indexes, $field)) {
            echo "Creating index on $field\n";
            $files->createIndex([$field => 1], ['name' => 'index_' . $field]);
        }
    }

    if ($incorrect) {
        $indexes = iterator_to_array($files->listIndexes());
        foreach ($incorrect as $field) {
            if (!$hasReplacement($indexes, $field)) {
                throw new RuntimeException("Missing full, visible replacement index on $field; old indexes retained");
            }
        }

        foreach ($incorrect as $name => $field) {
            echo "Dropping $name\n";
            $files->dropIndex($name);
        }
    }

    echo "Done\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'File index repair failed: ' . $e->getMessage() . "\n");
    exit(1);
}
