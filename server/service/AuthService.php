<?php

namespace Selpol\Service;

use Exception;
use Selpol\Http\HttpException;
use Selpol\Http\ServerRequest;

class AuthService
{
    private ?array $jwt = null;
    private ?array $subscriber = null;

    public function getJwt(): ?array
    {
        return $this->jwt;
    }

    public function getJwrOrThrow(): array
    {
        if ($this->jwt === null)
            throw new HttpException(message: 'Запрос не авторизирован', code: 401);

        return $this->jwt;
    }

    public function setJwt(?array $jwt): void
    {
        $this->jwt = $jwt;
    }

    public function getSubscriber(): ?array
    {
        return $this->subscriber;
    }

    public function getSubscriberOrThrow(): array
    {
        if ($this->subscriber === null)
            throw new HttpException(message: 'Запрос не авторизирован', code: 401);

        return $this->subscriber;
    }

    public function setSubscriber(?array $subscriber): void
    {
        $this->subscriber = $subscriber;
    }

    public function setJwtFromRequest(ServerRequest $request): ?string
    {
        $token = $request->getHeader('Authorization');

        if (count($token) === 0 || !str_starts_with($token[0], 'Bearer '))
            return 'Запрос не авторизирован';

        $bearer = substr($token[0], 7);

        if (substr_count($bearer, '.') !== 2)
            return 'Не верный формат токена';

        list($header, $payload, $signature) = explode('.', $bearer);
        $decoded_signature = base64_decode(str_replace(array('-', '_'), array('+', '/'), $signature));

        try {
            $oauth = config('backends.oauth');
        } catch (Exception) {
            return 'Не удалось проверить токен';
        }

        $publicKey = file_get_contents($oauth['public_key']);

        if (openssl_verify(utf8_decode($header . '.' . $payload), $decoded_signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1)
            return 'Не валидный токен';

        $jwt = json_decode(base64_decode($payload), true);

        if (time() <= $jwt['nbf'] || time() >= $jwt['exp'])
            return 'Время действия токена истекло';

        $audience = explode(',', $oauth['audience']);

        if (!in_array($jwt['aud'], $audience) || !array_key_exists('scopes', $jwt) || !array_key_exists(1, $jwt['scopes']))
            return 'Не верный тип токена';

        $this->setJwt($jwt);

        return null;
    }
}