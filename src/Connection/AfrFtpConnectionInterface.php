<?php

namespace Autoframe\Components\FtpTransfer\Connection;

interface AfrFtpConnectionInterface
{
    /**
     * @return false|mixed|null
     */
    public function connect();

    /**
     * @return void
     */
    public function disconnect(): void;

    /**
     * @param int $iTimeoutMs
     * @return false|mixed|null
     */
    public function reconnect(int $iTimeoutMs = 10);

    /**
     * @return false|resource
     */
    public function getConnection();

    /**
     * @return bool
     */
    public function getLoginResult(): bool;

    /**
     * @return string
     */
    public function getError(): string;

    public function __destruct();

    public function getDirPerms(): int;
}