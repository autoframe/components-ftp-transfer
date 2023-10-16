<?php

namespace Autoframe\Components\FtpTransfer\Report;

use Autoframe\Components\Exception\AfrException;
use Autoframe\Components\FtpTransfer\AfrFtpBackupConfig;

interface AfrFtpReportInterface
{
    /**
     * @param AfrFtpBackupConfig $oFtpConfig
     * @return array
     * @throws AfrException
     */
    public function ftpReport(AfrFtpBackupConfig $oFtpConfig): array;
}