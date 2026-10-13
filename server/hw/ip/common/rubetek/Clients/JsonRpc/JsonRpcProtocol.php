<?php

namespace hw\ip\common\rubetek\Clients\JsonRpc;

use JsonException;
use RuntimeException;

/**
 * Encodes and decodes JSON-RPC messages independently of their transport.
 */
final class JsonRpcProtocol
{
    public static function encodeRequest(string $method, array|object|null $params, int $id): string
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'id' => $id,
        ];

        if ($params !== null) {
            $request['params'] = $params;
        }

        try {
            return json_encode($request, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new RuntimeException("Failed to encode JSON-RPC request $method", 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeResponse(string $response, string $method): array
    {
        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Invalid JSON response for JSON-RPC method $method", 0, $e);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException("Invalid JSON-RPC response for method $method");
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $response
     */
    public static function getResult(array $response, string $method): mixed
    {
        if (isset($response['error'])) {
            $error = is_array($response['error']) ? $response['error'] : [];
            $code = $error['code'] ?? 'unknown';
            $message = $error['msg'] ?? $error['message'] ?? 'Unknown error';

            throw new RuntimeException("JSON-RPC method $method failed: $message ($code)");
        }

        if (!array_key_exists('result', $response)) {
            throw new RuntimeException("JSON-RPC method $method returned a response without result");
        }

        return $response['result'];
    }
}
