<?php

namespace hw\ip\common\rubetek\Clients\JsonRpc;

use RuntimeException;

interface JsonRpcClientInterface
{
    /**
     * Executes a JSON-RPC request and returns its result.
     *
     * @throws RuntimeException If the transport, protocol, or RPC call fails.
     */
    public function call(string $method, array|object|null $params = null): mixed;
}
