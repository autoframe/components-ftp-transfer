<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\FtpBusinessLogic;

interface AfrFtpBusinessLogicInterface
{
    /**
     * @return void
     */
    public function makeBackup(): void;

}