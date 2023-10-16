<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\FtpBusinessLogic;


use Autoframe\Components\Exception\AfrException;
use Autoframe\Components\FileSystem\DirPath\AfrDirPathClass;
use Autoframe\Components\FileSystem\DirPath\AfrDirPathInterface;
use Autoframe\Components\FtpTransfer\AfrFtpBackupConfig;
use Autoframe\Components\FtpTransfer\Log\AfrFtpLogInline;
use Autoframe\Components\FtpTransfer\Log\AfrFtpLogInterface;
use Autoframe\Process\Control\Lock\AfrLockFileClass;
use Autoframe\Process\Control\Lock\AfrLockInterface;


class AfrFtpPutBigDataFacade implements AfrFtpBusinessLogicInterface
{
    protected AfrFtpLogInterface $oLog;
    protected ?AfrFtpBackupConfig $oFtpConfig = null;
    protected AfrDirPathInterface $oDirPath;
    protected AfrLockInterface $oLock;

    public function __construct(AfrFtpBackupConfig $oConfig)
    {
        $this->oFtpConfig = $oConfig;
    }

    /**
     * @throws AfrException
     */
    public function makeBackup(): void
    {
        /*
        foreach ($this->oFtpConfig->aFromToPaths as $sFromDir => $sToDir) {
            if (!$this->xetDirPath()->isDir($sFromDir)) {
                $this->logFatal('oConfig->aFromToPaths[' . $sFromDir . '] is not found!', false);
                unset($this->oFtpConfig->aFromToPaths[$sFromDir]);//remove broken from config
            }
        }
        if (empty($this->oFtpConfig->aFromToPaths)) {
            $this->logFatal('oConfig->aFromToPaths is empty!', false);
            return;
        }*/

        if (
            is_file($this->oFtpConfig->sResumeDump) &&
            strlen($sResume = substr(file_get_contents($this->oFtpConfig->sResumeDump), 8)) > 1
        ) {
            $this->log("RESUME: {$this->oFtpConfig->sResumeDump}", false);
            /** @var AfrFtpBusinessLogicInterface $oUploader */
            $oUploader = unserialize($sResume);
        }
        else{
            foreach ($this->oFtpConfig->aFromToPaths as $sFromDir => $sToDir) {
                if (!$this->xetDirPath()->isDir($sFromDir)) {
                    $this->logFatal('oConfig->aFromToPaths[' . $sFromDir . '] is not found!', false);
                    unset($this->oFtpConfig->aFromToPaths[$sFromDir]);//remove broken from config
                }
            }
            if (empty($this->oFtpConfig->aFromToPaths)) {
                $this->logFatal('oConfig->aFromToPaths is empty!', false);
                return;
            }
            /** @var AfrFtpPutBigData $oUploader */
            /** @var AfrFtpBusinessLogicInterface $oUploaderX */
            $oUploader = new ($this->oFtpConfig->getBusinessLogic())(
                $this->oFtpConfig,
                $this->xetAfrFtpLog(),
                $this->xetAfrLock()
            );
            $oUploader->makeBackup();
        }
        $this->log(__CLASS__ . ' Run: '.(!empty($oUploader)?'OK':'ERROR'), true);
        $this->oFtpConfig = null;
    }


    /**
     * @param AfrFtpLogInterface|null $oLog
     * @return AfrFtpLogInterface
     */
    public function xetAfrFtpLog(AfrFtpLogInterface $oLog = null): AfrFtpLogInterface
    {
        if ($oLog) {
            $this->oLog = $oLog;
        } elseif (empty($this->oLog)) {
            $this->oLog = new AfrFtpLogInline();
            $this->oLog->newLog();
        }
        return $this->oLog;
    }

    /**
     * @param AfrLockInterface|null $oLock
     * @return AfrLockInterface
     */
    public function xetAfrLock(AfrLockInterface $oLock = null): AfrLockInterface
    {
        if ($oLock) {
            $this->oLock = $oLock;
        } elseif (empty($this->oLock)) {
            $this->oLock = new AfrLockFileClass(
                'FTP',
                [$this->oFtpConfig->ConServer, $this->oFtpConfig->ConPort]
            );
        }
        return $this->oLock;
    }

    /**
     * @param AfrDirPathInterface|null $oDirPath
     * @return AfrDirPathInterface
     */
    public function xetDirPath(AfrDirPathInterface $oDirPath = null): AfrDirPathInterface
    {
        if ($oDirPath) {
            $this->oDirPath = $oDirPath;
        } elseif (empty($this->oDirPath)) {
            $this->oDirPath = AfrDirPathClass::getInstance();
        }
        return $this->oDirPath;
    }

    /**
     * @param string $sMessage
     * @param bool $bClose
     * @return void
     */
    protected function log(string $sMessage, bool $bClose = false): void
    {
        $this->xetAfrFtpLog()->logMessage(
            $sMessage,
            AfrFtpLogInterface::MESSAGE
        );
        if ($bClose) {
            $this->xetAfrFtpLog()->closeLog();
        }
    }

    /**
     * @param string $sMessage
     * @param bool $bClose
     * @return void
     */
    protected function logFatal(string $sMessage, bool $bClose): void
    {
        $this->xetAfrFtpLog()->logMessage(
            $sMessage,
            AfrFtpLogInterface::FATAL_ERR
        );
        if ($bClose) {
            $this->xetAfrFtpLog()->closeLog();
        }
    }


}