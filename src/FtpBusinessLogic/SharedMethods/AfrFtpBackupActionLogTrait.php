<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\FtpBusinessLogic\SharedMethods;

use Autoframe\Components\FtpTransfer\Log\AfrFtpLogInterface;

trait AfrFtpBackupActionLogTrait
{
    public array $aActionLog = [];
    protected AfrFtpLogInterface $oLog;

    /**
     * @param $sLogType
     * @param $sMsg
     * @param $s
     * @param $d
     * @return void
     */
    protected function ActionLog($sLogType, $sMsg, $s, $d): void
    {
        $sMsg = (string)$sMsg;
        $s = (string)$s;
        $d = (string)$d;

        $sLogType = strtoupper((string)$sLogType);
        $this->aActionLog[$sLogType][] = [
            'msg' => (string)$sMsg,
            's' => $s,
            'd' => $d
        ];

        $this->oLog->logMessage(
            $sLogType . ' ' . str_replace('$s', $s, str_replace('$d', $d, $sMsg)),
            $sLogType === 'ERR' ? $this->oLog::FATAL_ERR : $this->oLog::MESSAGE
        );

    }
}