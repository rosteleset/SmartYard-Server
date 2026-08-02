<?php

declare(strict_types=1);

namespace api\edgeAgents;

use api\api;
use InvalidArgumentException;
use Throwable;

require_once dirname(__DIR__, 2) . '/edge-agent-controller/bootstrap.php';

final class controller extends api
{
    public static function GET($params)
    {
        try {
            $resource = is_string($params['_id'] ?? null) ? trim($params['_id']) : '';
            $view = is_string($params['view'] ?? null)
                ? $params['view']
                : ($resource === '' ? 'summary' : ($resource === 'logs' ? 'logs' : 'agent'));
            $result = match ($view) {
                'summary' => rbtAgentDesiredStateService()->summary(),
                'agent' => rbtAgentDesiredStateService()->agent($resource !== '' ? $resource : self::required($params, 'agentId')),
                'logs' => ['events' => rbtAgentLog()->tail((int) ($params['limit'] ?? 200))],
                default => throw new InvalidArgumentException('edge_agent_view_invalid'),
            };
            return api::ANSWER($result, '__asis__', 0);
        } catch (Throwable $error) {
            return self::failure($error);
        }
    }

    public static function POST($params)
    {
        try {
            $operation = self::required($params, 'operation');
            $service = rbtAgentDesiredStateService();
            $result = match ($operation) {
                'createInvitation' => $service->createInvitation(
                    self::serverURL(),
                    (int) ($params['ttl'] ?? 600),
                    is_string($params['displayName'] ?? null) ? $params['displayName'] : 'RBT',
                    isset($params['_realUid']) ? (int) $params['_realUid'] : null,
                ),
                'authorizeManaged' => $service->authorizeManaged(
                    self::required($params, 'agentId'),
                    self::required($params, 'secret'),
                    (int) ($params['ttl'] ?? 600),
                ),
                'savePool' => $service->savePool(self::object($params, 'pool')),
                'assignPool' => $service->assignPool(
                    self::required($params, 'agentId'),
                    self::required($params, 'poolId'),
                    self::boolean($params['enabled'] ?? true),
                ),
                'saveMapping' => $service->saveMapping(
                    self::required($params, 'agentId'),
                    self::object($params, 'mapping'),
                ),
                'queueAction' => $service->queueAction(
                    self::required($params, 'agentId'),
                    self::required($params, 'actionType'),
                    isset($params['payload']) ? self::object($params, 'payload') : [],
                    (int) ($params['ttl'] ?? 600),
                ),
                'gatewayPublicKey' => [
                    'interface' => self::required($params, 'interface'),
                    'publicKey' => rbtAgentGatewayKeyStore()->publicKey(self::required($params, 'interface')),
                ],
                default => throw new InvalidArgumentException('edge_agent_operation_invalid'),
            };
            return api::ANSWER($result, '__asis__', 0);
        } catch (Throwable $error) {
            return self::failure($error);
        }
    }

    public static function DELETE($params)
    {
        try {
            $service = rbtAgentDesiredStateService();
            $result = match (self::required($params, 'operation')) {
                'deleteMapping' => $service->deleteMapping(
                    self::required($params, 'agentId'),
                    self::required($params, 'mappingId'),
                ),
                'releasePool' => $service->releasePool(self::required($params, 'agentId')),
                'revokeAgent' => $service->revokeAgent(self::required($params, 'agentId')),
                default => throw new InvalidArgumentException('edge_agent_operation_invalid'),
            };
            return api::ANSWER($result, '__asis__', 0);
        } catch (Throwable $error) {
            return self::failure($error);
        }
    }

    public static function index()
    {
        return [
            'GET',
            'POST',
            'DELETE',
        ];
    }

    private static function required(array $params, string $key): string
    {
        $value = $params[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException($key . '_required');
        }
        return trim($value);
    }

    /** @return array<string,mixed> */
    private static function object(array $params, string $key): array
    {
        $value = $params[$key] ?? null;
        if (!is_array($value) || array_is_list($value)) {
            throw new InvalidArgumentException($key . '_object_required');
        }
        return $value;
    }

    private static function boolean(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }

    private static function serverURL(): string
    {
        $configured = rbtAgentConfig()['edgeAgentController']['publicUrl'] ?? null;
        if (!is_string($configured) || trim($configured) === '') {
            throw new InvalidArgumentException('edge_agent_controller_public_url_required');
        }
        return rtrim(trim($configured), '/');
    }

    private static function failure(Throwable $error): array
    {
        $public = $error instanceof InvalidArgumentException
            ? $error->getMessage()
            : 'edge_agent_operation_failed';
        rbtAgentLog()->write('admin_operation_failed', [
            'reason' => $error->getMessage(),
            'publicReason' => $public,
        ]);
        return api::ERROR($public);
    }
}
