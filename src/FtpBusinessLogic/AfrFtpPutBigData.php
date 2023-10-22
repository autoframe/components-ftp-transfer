<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\FtpBusinessLogic;

use Autoframe\Components\Exception\AfrException;
use Autoframe\Components\FtpTransfer\AfrFtpBackupConfig;
use Autoframe\Components\FtpTransfer\Log\AfrFtpLogInterface;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\SharedMethods\AfrFtpBackupLocalFilesTrait;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\SharedMethods\AfrFtpBackupFtpFilesTrait;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\SharedMethods\AfrFtpBackupActionLogTrait;
use Autoframe\Components\FtpTransfer\Report\AfrFtpReportInterface;
use Autoframe\Process\Control\Lock\AfrLockInterface;

class AfrFtpPutBigData implements AfrFtpBusinessLogicInterface
{
    use AfrFtpBackupLocalFilesTrait;
    use AfrFtpBackupFtpFilesTrait;
    use AfrFtpBackupActionLogTrait;


    protected array $aQueue = [];

    protected int $iDirPermissions = 0775;
    protected int $iLeftReconnections = 60;
    protected ?AfrLockInterface $oLock = null;

    /**
     * @param AfrFtpBackupConfig $oFtpConfig
     * @param AfrFtpLogInterface $oLog
     * @param AfrLockInterface $oLock
     */
    public function __construct(
        AfrFtpBackupConfig $oFtpConfig,
        AfrFtpLogInterface $oLog,
        AfrLockInterface $oLock
    )
    {

        $this->oLog = $oLog;
        $this->oFtpConfig = $oFtpConfig;
        $this->oLock = $oLock;

        $bSingleLock = $this->oLock->obtainLock();
        //$oLock->isLocked();
        $this->ActionLog(
            ($bSingleLock ? 'info' : 'err'),
            ($bSingleLock ? 'Single instance lock obtained for pid: ' . $this->oLock->getLockPid() : 'Error obtaining single instance lock'),
            '',
            ''
        );
//        $this->makeBackup();
//        $this->mkdirFtp('/bpg-backup/MG1/test1/test2/test3/test4/test7');
//        $this->copyToFtp(__FILE__, '/bpg-backup/MG1/test1/test2/test3/aa.php');
        //$this->mkdirFtp('/bpg-backup/MG1/test4/test4/test5/test6/test7');
        //$this->mkdirFtp('/bpg-backup/MG1/test3/test4/test5/test6/test7');
        //$this->mkdirFtp('/bpg-backup/MG1/test2/test4/test5/test6/test7');
//        $this->rmdirFtp('/bpg-backup/MG1/test',false,true);
//        $this->moveFtp('/bpg-backup/MG1/test1/test2/test3', '/bpg-backup/MG1/test2_move', true);

        /* $this->rmdirFtp('/bpg-backup/MG1/test5/test4/test5/test6/test7');
         $this->rmdirFtp('/bpg-backup/MG1/test5/test4/test5/test6');
         $this->rmdirFtp('/bpg-backup/MG1/test5/test4/test5');
         $this->rmdirFtp('/bpg-backup/MG1/test5/test4');
         $this->rmdirFtp('/bpg-backup/MG1/test5');*/


        //$this->rmdirFtp('/bpg-backup/MG1/test2_move',false,true); die;
        /*        $this->rmdirFtp('/bpg-backup/MG1/test4/test4/test5/test6/test7');
                $this->rmdirFtp('/bpg-backup/MG1/test4/test4/test5/test6');
                $this->rmdirFtp('/bpg-backup/MG1/test4/test4/test5');
                $this->rmdirFtp('/bpg-backup/MG1/test4/test4');
                $this->rmdirFtp('/bpg-backup/MG1/test4');
                $this->rmdirFtp('/bpg-backup/MG1/test3/test4/test5/test6/test7');
                $this->rmdirFtp('/bpg-backup/MG1/test3/test4/test5/test6');
                $this->rmdirFtp('/bpg-backup/MG1/test3/test4/test5');
                $this->rmdirFtp('/bpg-backup/MG1/test3/test4');
                $this->rmdirFtp('/bpg-backup/MG1/test3');
                $this->rmdirFtp('/bpg-backup/MG1/test2/test4/test5/test6/test7');
                $this->rmdirFtp('/bpg-backup/MG1/test2/test4/test5/test6');
                $this->rmdirFtp('/bpg-backup/MG1/test2/test4/test5');
                $this->rmdirFtp('/bpg-backup/MG1/test2/test4');
                $this->rmdirFtp('/bpg-backup/MG1/test2');*/
        //    die;


        //$this->copyToFtp('C:\\sources\\HP_ProBook_450_G8_Notebook_PC_CR_2009.wim', '/bpg-backup/MG1/test/!latest/bigUploadTest.txt');
        //$this->copyToFtp('C:\\google-api-php-client--PHP7.4\\AppData.7z', '/bpg-backup/MG1/test/!latest/bigUploadTest.txt');
        //$this->copyToFtp('C:\\RMN_kit\\Office 2010 - No Key Needed x86.zip', '/bpg-backup/MG1/test/!latest/bigUploadTest.txt');

    }

    /**
     * @return void
     * @throws AfrException
     */
    public function __wakeup()
    {
        $this->oFtpConnection = $this->xetAfrFtpConnection();
        for ($i = 1; $i <= 10; $i++) {
            $rConn = $this->oFtpConnection->reconnect();
            $this->ActionLog(
                ($rConn ? 'info' : 'err'),
                ($rConn ? 'Reconnected to FTP' : 'Failed to reconnect to FTP') . ' $s $d ',
                $this->oFtpConfig->ConServer,
                $this->oFtpConnection->getError()
            );
            if ($rConn) {
                break;
            } else {
                echo 'Sleep seconds ' . ($i * 60) . PHP_EOL;
                sleep($i * 60);
            }
        }
        if ($this->oLock->obtainLock()) {
            $this->ActionLog('info', 'Single instance lock obtained for pid: ' . $this->oLock->getLockPid(), '', '' );
        }
        $this->makeBackup();
    }

    /**
     * @return void
     */
    protected function xSleep()
    {
        if ($this->oFtpConfig->sResumeDump) {
            $oFtpConnection = $this->oFtpConnection;
            //$oLock = $this->oLock;
            unset($this->oFtpConnection);
            //unset($this->oLock);
            file_put_contents($this->oFtpConfig->sResumeDump, '<?php //' . serialize($this));
            $this->oFtpConnection = $oFtpConnection;
            //$this->oLock = $oLock;
        }
    }

    /**
     * @return void
     */
    protected function xRemoveSleep()
    {
        if ($this->oFtpConfig->sResumeDump && is_file($this->oFtpConfig->sResumeDump)) {
            @unlink($this->oFtpConfig->sResumeDump);
        }
    }

    /**
     * @return void
     * @throws AfrException
     */
    public function makeBackup(): void
    {
        if (!$this->oLock->obtainLock()) {
            $this->ActionLog('err', 'Error obtaining single instance lock', '', '' );
            $this->write_log();
            return;
        }

        if (empty($this->oFtpConnection)) {
            $this->oFtpConnection = $this->xetAfrFtpConnection();
        }


        $iFailed = 0;
        if (empty($this->aQueue)) {

            $this->iDirPermissions = $this->oFtpConfig->iDirPermissions;

            if (!$this->oFtpConnection->getConnection()) {
                $this->ActionLog(
                    'err',
                    'Unable to create process queue because of FTP connection issues! ' .
                    '$s; retriesLeft($d)[' . date('Y-m-d H:i:s') . ']',
                    $this->oFtpConnection->getError(),
                    $this->iLeftReconnections
                );
                $this->iLeftReconnections--;
                if ($this->iLeftReconnections > 0) {
                    $iFailed = -1;
                }

                echo 'Sleep 10 minutes because FTP CONNECTION ISSUES! ' . $this->oFtpConnection->getError() . "\n";
                sleep(60 * 10);
            } else {
                $this->ActionLog(
                    'info',
                    'Connected to FTP $s:$d ',
                    $this->oFtpConfig->ConServer,
                    $this->oFtpConfig->ConPort
                );
                $this->populateToQueue();
                $this->xSleep();
            }
        }
        //print_r($this->aQueue); die;
        if (empty($this->aQueue) && $iFailed === 0) {
            $this->ActionLog('log', 'Process queue is empty...', '', '');
        }

        foreach ($this->aQueue as $sDestinationFullPath => $aProcess) {

            if (
                $this->aQueue[$sDestinationFullPath]['bSuccess'] ||
                $this->aQueue[$sDestinationFullPath]['iRetry'] > 5
            ) {
                continue;
            }
            $try = ++$this->aQueue[$sDestinationFullPath]['iRetry'];
            $sMethod = $aProcess['sMethod'];
            $aArgs = $aProcess['aArgs'];
            $this->ActionLog('log', "Try#$try $sMethod $sDestinationFullPath ", '', '',);

            $bQueueResult = $this->$sMethod(...$aArgs);
            $this->aQueue[$sDestinationFullPath]['bSuccess'] = $bQueueResult;
            if (!$bQueueResult) {
                $iFailed++;
            }
            $this->xSleep();
        }
        if ($iFailed) {
            if ($iFailed > 0) {
                echo "Sleep 60 seconds because #failed:$iFailed\n";
                sleep(60);
            }
            $this->makeBackup(); //retry
        } else {
            $this->xRemoveSleep();
            $this->ActionLog('log', 'Total queue processed: ' . count($this->aQueue), '', '');
            $this->ActionLog('end', 'Queue ended ' . date('Y-m-d H:i:s'), '', '');

            //$this->ActionLog('confirm', print_r($this->aQueue, true), '', '');
            $this->write_log();
        }


    }

    /**
     * @param string $sDestinationFullPath
     * @param string $sMethod
     * @param array $aArgs
     * @return void
     */
    public function addToQueue(
        string $sDestinationFullPath,
        string $sMethod,
        array  $aArgs
    ): void
    {
        $this->aQueue[$sDestinationFullPath] = [
            'sMethod' => $sMethod,
            'aArgs' => $aArgs,
            'iRetry' => 0,
            'bSuccess' => false,
        ];
    }

    /**
     * @return void
     */
    public function populateToQueue(): void
    {
        foreach ($this->oFtpConfig->aFromToPaths as $sFromDir => $sToDir) {
            $this->localDs = $this->detectPathSeparator($sFromDir);
            $this->sSourceFolder = $this->fixSlashStyle($sFromDir, $this->localDs);
            $this->sDestinationFolder = $this->fixSlashStyle($sToDir, $this->ftpDs);
            $sDestinationFolderWithFolderName = $this->getDestinationFolderWithFolderName();
            echo "\n++++ populateToQueue ^ $sFromDir @ $sToDir " . date('Y-m-d H:i:s') . "\n\n";

            if (!$this->isDir($this->sSourceFolder)) {
                $this->ActionLog(
                    'err',
                    'Source Folder not found. Please check your Input: ' . $this->sSourceFolder,
                    '',
                    '');
                continue;
            }
            if (!$this->isDirFtp($sDestinationFolderWithFolderName)) {
                if (!$this->mkdirFtp($sDestinationFolderWithFolderName)) {
                    $this->ActionLog(
                        'err',
                        'Destination Folder writable Please check ' . $sDestinationFolderWithFolderName,
                        '',
                        '');
                    continue;
                }
            }
            $bSourceDestination = $this->recursiveSourceDestinationCopy(
                $this->sSourceFolder,
                $sDestinationFolderWithFolderName
            );
            //TODO recheck!
            if ($bSourceDestination) {
                $bDeletedSave = $this->recursiveFoundInDestinationAndDeletedFromSource(
                    $this->sSourceFolder,
                    $sDestinationFolderWithFolderName,
                    true
                );
            }
        }
    }

    /**
     * @throws AfrException
     */
    protected function write_log(array $aLogs = [])
    {
        echo 'Preparing the logs...' . PHP_EOL;
        $aLogs = !empty($aLogs) ? $aLogs : $this->getBackupLogs();
        if (!empty($aLogs)) {

            foreach ($aLogs as $sSubject => $sBody) {

                if ($this->oFtpConfig->getReportClass()) {
                    $this->oFtpConfig->sReportSubject = $sSubject;
                    $this->oFtpConfig->sReportBody = $sBody;

                    /** @var AfrFtpReportInterface $oReport */
                    $oReport = new ($this->oFtpConfig->getReportClass())();
                    try {
                        $oReport->ftpReport($this->oFtpConfig);
                    } catch (AfrException $e) {
                        error_log($e->getMessage());
                        echo "\nERROR!!!: ".$e->getMessage().PHP_EOL;
                    }
                }

                if (!empty($this->oFtpConnection) && $this->oFtpConnection->getConnection()) {
                    $sLogDir = $this->fixSlashStyle($this->sDestinationFolder, $this->ftpDs) . $this->ftpDs . 'logs' . $this->ftpDs;
                    $this->mkdirFtp($sLogDir);
                    $filenameFtp = $sLogDir . $sSubject . '.txt';
                    $filenameTmp = __DIR__ . DIRECTORY_SEPARATOR . 'log';
                    file_put_contents($filenameTmp, $sBody);
                    ftp_put($this->oFtpConnection->getConnection(), $filenameFtp, $filenameTmp);
                    @unlink($filenameTmp);
                } else {
                    echo "\n\n$sSubject\n\n$sBody\n\n";
                }

            }
        }
        echo 'Total generated logs are ' . count($aLogs) . PHP_EOL;

    }

    protected function getBackupLogs(): array
    {
        $sLogTypeHeader = '';
        if (empty($this->sSourceFolder)) {
            foreach ($this->oFtpConfig->aFromToPaths as $sFromDir => $sToDir) {
                $this->localDs = $this->detectPathSeparator($sFromDir);
                $this->sSourceFolder = $this->fixSlashStyle($sFromDir, $this->localDs);
                $this->sDestinationFolder = $this->fixSlashStyle($sToDir, $this->ftpDs);
            }
        }
        $source = $this->sSourceFolder . $this->localDs;
        $dest = $this->sDestinationFolder . $this->ftpDs;

        $sOut = '$s=' . $source . PHP_EOL . '$d=' . $dest . PHP_EOL;
        if (count($this->oFtpConfig->aFromToPaths) > 1) {
            $sOut .= 'oFtpConfig->aFromToPaths: ' . print_r($this->oFtpConfig->aFromToPaths, true) . PHP_EOL;
        }
        foreach ($this->aActionLog as $sLogType => $aRows) {
            foreach ($aRows as $aData) {
                if ($sLogTypeHeader != $sLogType) {
                    $sOut .= '~~~~~~~~~~~~~~~~~~' . PHP_EOL;
                    $sOut .= $sLogType . PHP_EOL . PHP_EOL;
                    $sLogTypeHeader = $sLogType;
                }
                if ($aData['s']) {
                    $aData['s'] = str_replace($source, '', $aData['s']);
                    $aData['s'] = str_replace($dest, '', $aData['s']);
                }
                if ($aData['d']) {
                    $aData['d'] = str_replace($source, '', $aData['d']);
                    $aData['d'] = str_replace($dest, '', $aData['d']);
                }
                $sOut .= $aData['msg'] . PHP_EOL;
                $sOut .= ($aData['s'] ? '$s: ' . $aData['s'] : '') . PHP_EOL;
                $sOut .= ($aData['d'] ? '$d: ' . $aData['d'] : '') . PHP_EOL . PHP_EOL;
            }

        }
        $sOut .= 'DONE! ------------------------------------------' . PHP_EOL;

        return [
            'Ftp Backup logs ' . date('Y-m-d H-i') => $sOut,
            'Ftp Backup FULL logs ' . date('Y-m-d H-i') => (string)$this->oLog
        ];

    }


    /**
     * @param string $sCopyToPath
     * @return string
     */
    protected function getDestinationDateBackupFolderPathFromDestinationPath(string $sCopyToPath): string
    {
        $sCopyToDateFilePath = $this->getDestinationDayBackupFolderPath() . substr($sCopyToPath, strlen($this->getDestinationFolderWithFolderName()));
        $sBackupDateFolderPath = substr($sCopyToDateFilePath, 0, -strlen(basename($sCopyToPath)) - 1);
        $this->mkdirFtp($sBackupDateFolderPath);
        return $sCopyToDateFilePath;
    }


    /**
     * @param string $sSourceDir
     * @param string $sDestinationDir
     * @return bool
     */
    protected function recursiveSourceDestinationCopy(string $sSourceDir, string $sDestinationDir): bool
    {
        //$sDestinationDir = $this->fixSlashStyle($sDestinationDir, $this->ftpDs);
        //first check destination folder exist or not
        //if (!$this->mkdirFtp($sDestinationDir)) { return false; }
        if (!$this->isDirFtp($sDestinationDir)) {
            $this->addToQueue($sDestinationDir, 'mkdirFtp', [$sDestinationDir]);
        }


        //$sSourceDir = $this->fixSlashStyle($sSourceDir, $this->localDs);
        foreach ($this->getDirFileList($sSourceDir) as $sListItemName) {
            $sCopyFromPath = $sSourceDir . $this->localDs . $sListItemName;
            $sCopyToPath = $sDestinationDir . $this->ftpDs . $sListItemName;
            $mType = $this->filetype($sCopyFromPath);

            if ($mType === 'dir') {
                $this->recursiveSourceDestinationCopy($sCopyFromPath, $sCopyToPath);
            } elseif ($mType === 'file') {
                $this->safeCopyFilesFromSourceToDestination($sCopyFromPath, $sCopyToPath);
            } else {
                $this->ActionLog('skip', 'Skipped $s because type `$d`', $sCopyFromPath, $mType);
            }
        }

        return true;
    }

    /**
     * @param string $sSourceDir
     * @param string $sDestinationDir
     * @param bool $bRefreshCache
     * @return bool
     */
    protected function recursiveFoundInDestinationAndDeletedFromSource(
        string $sSourceDir,
        string $sDestinationDir,
        bool   $bRefreshCache
    ): bool
    {
        //first check destination folder exist or not
        //if (!$this->mkdirFtp($sDestinationDir)) { return false; }
        if (!$this->isDirFtp($sDestinationDir)) {
            $this->addToQueue('~m' . $sDestinationDir, 'mkdirFtp', [$sDestinationDir]);
        }


        foreach ($this->getDirFileListFtp($sDestinationDir, $bRefreshCache) as $sDestinationListItemName) {
            $sSourcePath = $sSourceDir . $this->localDs . $sDestinationListItemName;
            $sDestinationPath = $sDestinationDir . $this->ftpDs . $sDestinationListItemName;
            $mType = $this->filetypeFtp($sDestinationPath);

            if ($mType === 'dir') {
                if (!$this->isDir($sSourcePath)) {
                    $this->recursiveFoundInDestinationAndDeletedFromSource($sSourcePath, $sDestinationPath, $bRefreshCache);
                    $this->addToQueue('~' . $sDestinationPath, 'rmdirFtp', [$sDestinationPath]);
                    //$this->rmdirFtp($sDestinationPath);
                }

            } elseif ($mType === 'file') {
                if (!$this->isFile($sSourcePath)) {
                    //move destination file to date folder and keep first version from today after 00:01 AM
                    $this->addToQueue(
                        '~' . $sDestinationPath,
                        'moveFtp',
                        [
                            $sDestinationPath,
                            $this->getDestinationDateBackupFolderPathFromDestinationPath($sDestinationPath),
                            true,
                            true
                        ]
                    );

                    /*$this->moveFtp(
                        $sDestinationPath,
                        $this->getDestinationDateBackupFolderPathFromDestinationPath($sDestinationPath),
                        true,
                        true
                    );*/
                }
            } else {
                $this->ActionLog('skip', 'Skipped $s because type `$d`', $sSourcePath, $mType);
            }
        }
        return true;
    }


    /**
     * @param string $sCopyFromPath
     * @param string $sCopyToPath
     * @return void
     */
    protected function safeCopyFilesFromSourceToDestination(string $sCopyFromPath, string $sCopyToPath): void
    {
        $bTodayFolderName = !empty($this->oFtpConfig) && !empty($this->oFtpConfig->sTodayFolderName);
        if ($this->isFileFtp($sCopyToPath) && $bTodayFolderName) {
            if (
                (int)filesize($sCopyFromPath) != $this->filesizeFtp($sCopyToPath) ||
                filemtime($sCopyFromPath) > $this->filemtimeFtp($sCopyToPath)
            ) {
                $sCopyToDateFilePath = $this->getDestinationDateBackupFolderPathFromDestinationPath($sCopyToPath);

                //move destination file to date folder and keep first version from today near 00:01 AM
                if (!$this->isFileFtp($sCopyToDateFilePath)) {
                    $this->addToQueue($sCopyToDateFilePath, 'moveFtp', [$sCopyToPath, $sCopyToDateFilePath, true]);
                    //$this->moveFtp($sCopyToPath, $sCopyToDateFilePath, true);
                }
                //once file moved to date folder
                // now copy original file to destination folder
                $this->addToQueue($sCopyToPath, 'copyToFtp', [$sCopyFromPath, $sCopyToPath]);
                //$this->copyToFtp($sCopyFromPath, $sCopyToPath);
            }
        } else {
            $this->addToQueue($sCopyToPath, 'copyToFtp', [$sCopyFromPath, $sCopyToPath]);
            //$this->copyToFtp($sCopyFromPath, $sCopyToPath);
        }
    }

}
