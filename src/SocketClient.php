<?php

namespace EnergyMeters;

use Exception;

class SocketClient
{
    private $socket;

    /**
     * @throws Exception
     */
    public function __construct($host, $port)
    {

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));
        if ($this->socket === false) {
            throw new Exception("Failed to create socket: " . socket_strerror(socket_last_error()));
        }

        $result = socket_connect($this->socket, $host, $port);
        if ($result === false) {
            throw new Exception("Failed to connect to $host:$port: " . socket_strerror(socket_last_error()));
        }
    }

    /**
     * @param $request
     * @return void
     */
    public function sendRequest($request): void
    {
        socket_write($this->socket, $request, strlen($request));
    }

    public function getResponse(): bool|string
    {
        return socket_read($this->socket, 4096);
    }

    public function close(): void
    {
        socket_close($this->socket);
    }
}
