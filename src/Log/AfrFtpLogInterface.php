<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\Log;

interface AfrFtpLogInterface
{
    public const FATAL_ERR = 1;
    public const MESSAGE = 2;

    /**
     * @return $this
     */
    public function newLog(): self;

    /**
     * @param string $sMessage
     * @param int $iType
     * @return $this
     */
    public function logMessage(string $sMessage, int $iType): self;

    /**
     * @return $this
     */
    public function closeLog(): self;
}