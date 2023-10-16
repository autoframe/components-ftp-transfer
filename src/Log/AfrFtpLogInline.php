<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\Log;

class AfrFtpLogInline implements AfrFtpLogInterface
{
    public array $aMessages = [];

    /**
     * @inheritDoc
     */
    public function newLog(): AfrFtpLogInterface
    {
        if (!empty($this->aMessages)) {
            $this->closeLog();
        }
        $this->aMessages = [];
        $this->logMessage('', self::MESSAGE);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function logMessage(string $sMessage, int $iType): AfrFtpLogInterface
    {
        if (empty($this->aMessages)) {
            $this->printInline($this->aMessages[] = [$iType, 'Logging Started: ' . date('Y-m-d H:i:s')]);
        }
        if (strlen($sMessage) || $iType === self::FATAL_ERR) {
            $this->printInline($this->aMessages[] = [$iType, $sMessage]);
        }
        return $this;
    }

    /**
     * @param array $aMsg
     * @return void
     */
    protected function printInline(array $aMsg): void
    {
        $sType = $this->getType($aMsg[0]);
        $bIsCli = http_response_code() === false;
        echo $sType . $aMsg[1] . ($bIsCli ? '' : '<br />') . PHP_EOL;
        if (!$bIsCli && ob_get_length()) {
            ob_end_flush();
        }
    }

    /**
     * @inheritDoc
     */
    public function closeLog(): AfrFtpLogInterface
    {
        if (!empty($this->aMessages)) {
            $this->logMessage('Logging Ended ' . date('Y-m-d H:i:s'), self::MESSAGE);
            //trigger_error('Unable to write log!');
            //todo write to file, email, whatever
        }
        $this->aMessages = [];
        return $this;
    }

    public function __destruct()
    {
        $this->closeLog();
    }

    /**
     * @param int $iType
     * @return string
     */
    protected function getType(int $iType): string
    {
        $aTypeMap = [
            self::FATAL_ERR => 'FATAL ERROR: ',
            self::MESSAGE => '',
        ];
        return $aTypeMap[$iType] ?? 'UNKNOWN: ';
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $sOut = '';
        foreach ($this->aMessages as $aMsg) {
            $sOut .= $this->getType($aMsg[0]) . $aMsg[1] . "\n";
        }
        return $sOut;
    }
}