<?php

namespace hw\ip\domophone\rubetek\Clients;

use RuntimeException;

final class WebSocketClient
{
    private const DEFAULT_TIMEOUT = 15;
    private const DEFAULT_MAX_MESSAGE_SIZE = 1048576;
    private const MAX_HTTP_HEADER_SIZE = 16384;

    /** @var resource|null */
    private $socket = null;

    private string $readBuffer = '';

    public function __construct(
        private readonly string $url,
        private readonly int    $timeout = self::DEFAULT_TIMEOUT,
        private readonly int    $maxMessageSize = self::DEFAULT_MAX_MESSAGE_SIZE,
    )
    {
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

        $this->disconnect();

        $url = parse_url($this->url);
        $host = $url['host'] ?? null;

        if ($host === null) {
            throw new RuntimeException("Invalid WebSocket URL: $this->url");
        }

        $scheme = strtolower($url['scheme'] ?? '');
        if (!in_array($scheme, ['ws', 'wss'], true)) {
            throw new RuntimeException("Unsupported WebSocket URL scheme: $scheme");
        }

        $secure = $scheme === 'wss';
        $port = $url['port'] ?? ($secure ? 443 : 80);
        $path = $url['path'] ?? '/';
        $path = $path === '' ? '/' : $path;

        if (isset($url['query'])) {
            $path .= '?' . $url['query'];
        }

        $socketHost = str_contains($host, ':') ? "[$host]" : $host;
        $transport = $secure ? 'tls' : 'tcp';
        $errno = 0;
        $error = '';
        $socket = @stream_socket_client(
            "$transport://$socketHost:$port",
            $errno,
            $error,
            $this->timeout,
        );

        if ($socket === false) {
            throw new RuntimeException("Failed to connect to WebSocket: $error ($errno)");
        }

        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;
        $this->readBuffer = '';

        try {
            $this->performHandshake($host, $port, $path, $secure);
        } catch (RuntimeException $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
        $this->readBuffer = '';
    }

    public function isConnected(): bool
    {
        return is_resource($this->socket) && !feof($this->socket);
    }

    public function receive(): string
    {
        return $this->readMessage();
    }

    public function send(string $payload): void
    {
        $this->writeFrame($payload);
    }

    private function performHandshake(string $host, int $port, string $path, bool $secure): void
    {
        $key = base64_encode(random_bytes(16));
        $defaultPort = $secure ? 443 : 80;
        $hostForHeader = str_contains($host, ':') ? "[$host]" : $host;
        $hostHeader = $port === $defaultPort ? $hostForHeader : "$hostForHeader:$port";
        $request = implode("\r\n", [
            "GET $path HTTP/1.1",
            "Host: $hostHeader",
            'Upgrade: websocket',
            'Connection: Upgrade',
            "Sec-WebSocket-Key: $key",
            'Sec-WebSocket-Version: 13',
            '',
            '',
        ]);

        $this->write($request);
        $response = $this->readHttpHeaders();
        $lines = preg_split('/\r\n/', $response);
        $statusLine = array_shift($lines);

        if (!is_string($statusLine) || !preg_match('/^HTTP\/1\.[01] 101\b/', $statusLine)) {
            throw new RuntimeException("WebSocket handshake was rejected: $statusLine");
        }

        $headers = [];
        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        $expectedAccept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        if (($headers['sec-websocket-accept'] ?? '') !== $expectedAccept) {
            throw new RuntimeException('Invalid Sec-WebSocket-Accept returned by server');
        }
    }

    private function readBytes(int $length): string
    {
        while (strlen($this->readBuffer) < $length) {
            $this->readBuffer .= $this->readFromSocket();
        }

        $result = substr($this->readBuffer, 0, $length);
        $this->readBuffer = substr($this->readBuffer, $length);

        return $result;
    }

    /**
     * @return array{bool, int, string}
     */
    private function readFrame(): array
    {
        $header = $this->readBytes(2);
        $first = ord($header[0]);
        $second = ord($header[1]);
        $fin = (bool)($first & 0x80);
        $opcode = $first & 0x0F;
        $masked = (bool)($second & 0x80);
        $length = $second & 0x7F;

        if ($length === 126) {
            $length = unpack('nlength', $this->readBytes(2))['length'];
        } elseif ($length === 127) {
            $parts = unpack('Nhigh/Nlow', $this->readBytes(8));
            if ($parts['high'] !== 0) {
                throw new RuntimeException('WebSocket frame is too large');
            }
            $length = $parts['low'];
        }

        if ($length > $this->maxMessageSize) {
            throw new RuntimeException('WebSocket frame is too large');
        }

        $mask = $masked ? $this->readBytes(4) : '';
        $payload = $this->readBytes($length);

        if ($masked) {
            $payload ^= substr(str_repeat($mask, (int)ceil($length / 4)), 0, $length);
        }

        return [$fin, $opcode, $payload];
    }

    private function readFromSocket(): string
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('WebSocket is not connected');
        }

        $data = fread($this->socket, 8192);
        if ($data !== false && $data !== '') {
            return $data;
        }

        $metadata = stream_get_meta_data($this->socket);
        $this->disconnect();

        if ($metadata['timed_out'] ?? false) {
            throw new RuntimeException('Timed out waiting for WebSocket response');
        }

        throw new RuntimeException('WebSocket connection was closed unexpectedly');
    }

    private function readHttpHeaders(): string
    {
        while (($position = strpos($this->readBuffer, "\r\n\r\n")) === false) {
            if (strlen($this->readBuffer) >= self::MAX_HTTP_HEADER_SIZE) {
                throw new RuntimeException('WebSocket handshake response is too large');
            }

            $this->readBuffer .= $this->readFromSocket();
        }

        $headers = substr($this->readBuffer, 0, $position);
        $this->readBuffer = substr($this->readBuffer, $position + 4);

        return $headers;
    }

    private function readMessage(): string
    {
        $message = '';
        $messageStarted = false;

        while (true) {
            [$fin, $opcode, $payload] = $this->readFrame();

            if ($opcode === 0x8) {
                $this->disconnect();
                throw new RuntimeException('WebSocket connection was closed by server');
            }

            if ($opcode === 0x9) {
                $this->writeFrame($payload, 0xA);
                continue;
            }

            if ($opcode === 0xA) {
                continue;
            }

            if ($opcode === 0x1) {
                $message = $payload;
                $messageStarted = true;
            } elseif ($opcode === 0x0 && $messageStarted) {
                $message .= $payload;
            } else {
                throw new RuntimeException("Unexpected WebSocket opcode: $opcode");
            }

            if (strlen($message) > $this->maxMessageSize) {
                throw new RuntimeException('WebSocket message is too large');
            }

            if ($fin) {
                return $message;
            }
        }
    }

    private function write(string $data): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('WebSocket is not connected');
        }

        $written = 0;
        $length = strlen($data);

        while ($written < $length) {
            $bytes = fwrite($this->socket, substr($data, $written));
            if ($bytes === false || $bytes === 0) {
                $this->disconnect();
                throw new RuntimeException('Failed to write to WebSocket');
            }

            $written += $bytes;
        }
    }

    private function writeFrame(string $payload, int $opcode = 0x1): void
    {
        $length = strlen($payload);

        if ($length > $this->maxMessageSize) {
            throw new RuntimeException('WebSocket request is too large');
        }

        $header = chr(0x80 | $opcode);
        if ($length <= 125) {
            $header .= chr(0x80 | $length);
        } elseif ($length <= 65535) {
            $header .= chr(0x80 | 126) . pack('n', $length);
        } else {
            $header .= chr(0x80 | 127) . pack('NN', 0, $length);
        }

        $mask = random_bytes(4);
        $maskedPayload = $payload ^ substr(str_repeat($mask, (int)ceil($length / 4)), 0, $length);
        $this->write($header . $mask . $maskedPayload);
    }
}
