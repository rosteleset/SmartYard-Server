<?php

namespace hw\ip\common\rubetek\Clients\JsonRpc;

use hw\ip\common\rubetek\Clients\Transport\WebSocket;
use RuntimeException;

final class WebSocketJsonRpcClient implements JsonRpcClientInterface
{
    private int $requestId = 0;

    public function __construct(
        private readonly WebSocket $transport,
    )
    {
    }

    /**
     * Executes a JSON-RPC 2.0 request and returns its result.
     *
     * @throws RuntimeException If encoding, decoding, or the RPC call fails.
     */
    public function call(string $method, array|object|null $params = null): mixed
    {
        $id = ++$this->requestId;
        $this->transport->send(JsonRpcProtocol::encodeRequest($method, $params, $id));

        while (true) {
            $decoded = JsonRpcProtocol::decodeResponse($this->transport->receive(), $method);

            if (($decoded['id'] ?? null) !== $id) {
                continue;
            }

            return JsonRpcProtocol::getResult($decoded, $method);
        }
    }

    /**
     * Sends a JSON-RPC 2.0 request without waiting for its response.
     */
    public function send(string $method, array|object|null $params = null): void
    {
        $this->transport->send(JsonRpcProtocol::encodeRequest($method, $params, ++$this->requestId));
    }
}
