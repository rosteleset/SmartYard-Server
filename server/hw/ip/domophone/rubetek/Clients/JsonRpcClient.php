<?php

namespace hw\ip\domophone\rubetek\Clients;

use JsonException;
use RuntimeException;

final class JsonRpcClient
{
    private int $requestId = 0;

    public function __construct(
        private readonly WebSocketClient $transport,
    )
    {
    }

    /**
     * Executes a JSON-RPC 2.0 request and returns its result.
     *
     * @throws RuntimeException If encoding, decoding, or the RPC call fails.
     */
    public function call(string $method, ?array $params = null): mixed
    {
        $id = ++$this->requestId;
        $request = $this->buildRequest($method, $params, $id);

        $this->transport->send($this->encode($request, $method));

        while (true) {
            $decoded = $this->decode($this->transport->receive(), $method);

            if (($decoded['id'] ?? null) !== $id) {
                continue;
            }

            if (isset($decoded['error'])) {
                $error = is_array($decoded['error']) ? $decoded['error'] : [];
                $code = $error['code'] ?? 'unknown';
                $message = $error['msg'] ?? $error['message'] ?? 'Unknown error';

                throw new RuntimeException("JSON-RPC method $method failed: $message ($code)");
            }

            if (!array_key_exists('result', $decoded)) {
                throw new RuntimeException("JSON-RPC method $method returned a response without result");
            }

            return $decoded['result'];
        }
    }

    /**
     * Sends a JSON-RPC 2.0 request without waiting for its response.
     */
    public function send(string $method, ?array $params = null): void
    {
        $request = $this->buildRequest($method, $params, ++$this->requestId);
        $this->transport->send($this->encode($request, $method));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequest(string $method, ?array $params, int $id): array
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => $method,
        ];

        $request['id'] = $id;

        if ($params !== null) {
            $request['params'] = $params;
        }

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $response, string $method): array
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
     * @param array<string, mixed> $request
     */
    private function encode(array $request, string $method): string
    {
        try {
            return json_encode($request, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new RuntimeException("Failed to encode JSON-RPC request $method", 0, $e);
        }
    }
}
