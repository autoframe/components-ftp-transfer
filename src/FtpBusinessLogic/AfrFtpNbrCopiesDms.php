<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\FtpBusinessLogic;

use Autoframe\Components\FtpTransfer\AfrFtpBackupConfig;
use Autoframe\Components\FtpTransfer\Log\AfrFtpLogInterface;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\SharedMethods\AfrFtpBackupLocalFilesTrait;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\SharedMethods\AfrFtpBackupActionLogTrait;

class AfrFtpNbrCopiesDms implements AfrFtpBusinessLogicInterface
{
    use AfrFtpBackupLocalFilesTrait;
    use AfrFtpBackupActionLogTrait;

    protected int $iDirPermissions = 0775;

    /**
     * @param AfrFtpBackupConfig $oFtpConfig
     * @param AfrFtpLogInterface $oLog
     */
    public function __construct(
        AfrFtpBackupConfig $oFtpConfig,
        AfrFtpLogInterface $oLog
    )
    {
        $this->oLog = $oLog;
        $this->oFtpConfig = $oFtpConfig;
    }

    /**
     * @return void
     */
    public function makeBackup(): void
    {
        $this->iDirPermissions = $this->oFtpConfig->iDirPermissions;
        $this->ActionLog('info', "++++++++++ Local files copy START @ " . date('Y-m-d H:i:s') . " ++++++++++++++++", '', '');

        foreach ($this->oFtpConfig->aFromToPaths as $sFromDir => $sToDir) {
            if (!$this->isDir($sFromDir)) {
                $this->ActionLog(
                    'err',
                    'Source Folder not found. Please check your Input: ' . $this->sSourceFolder,
                    '',
                    '');
                continue;
            }
            $this->localDs = $this->detectPathSeparator($sFromDir);
            $this->sSourceFolder = $this->fixSlashStyle($sFromDir, $this->localDs);
            $this->sDestinationFolder = $this->fixSlashStyle($sToDir, $this->localDs);
            $sDestinationFolderWithFolderName = $this->getDestinationFolderWithFolderName();
            echo "\n++++ FileCopy ^ $sFromDir @ $sToDir " . date('Y-m-d H:i:s') . "\n\n";


            if (!$this->isDir($sDestinationFolderWithFolderName)) {
                if (!$this->mkdir($sDestinationFolderWithFolderName)) {
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
            if ($bSourceDestination) {
                $this->recursiveFoundInDestinationAndDeletedFromSource(
                    $this->sSourceFolder,
                    $sDestinationFolderWithFolderName
                );
            }
        }
        $this->ActionLog('end', 'Local files copy ended ' . date('Y-m-d H:i:s'), '', '');
    }


    /**
     * @param string $sCopyToPath
     * @return string
     */
    protected function getDestinationDateBackupFolderPathFromDestinationPath(string $sCopyToPath): string
    {
        $sCopyToDateFilePath = $this->getDestinationDayBackupFolderPath() . substr($sCopyToPath, strlen($this->getDestinationFolderWithFolderName()));
        $sBackupDateFolderPath = substr($sCopyToDateFilePath, 0, -strlen(basename($sCopyToPath)) - 1);
        $this->mkdir($sBackupDateFolderPath);
        return $sCopyToDateFilePath;
    }


    /**
     * @param string $sSourceDir
     * @param string $sDestinationDir
     * @return bool
     */
    protected function recursiveSourceDestinationCopy(string $sSourceDir, string $sDestinationDir): bool
    {
        //first check destination folder exist or not
        if (!$this->mkdir($sDestinationDir)) {
            return false;
        }
        foreach ($this->getDirFileList($sSourceDir) as $sListItemName) {
            $sCopyFromPath = $sSourceDir . $this->localDs . $sListItemName;
            $sCopyToPath = $sDestinationDir . $this->localDs . $sListItemName;
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
     * @return bool
     */
    protected function recursiveFoundInDestinationAndDeletedFromSource(
        string $sSourceDir,
        string $sDestinationDir
    ): bool
    {
        //first check destination folder exist or not
        if (!$this->mkdir($sDestinationDir)) {
            return false;
        }

        foreach ($this->getDirFileList($sDestinationDir) as $sDestinationListItemName) {
            $sSourcePath = $sSourceDir . $this->localDs . $sDestinationListItemName;
            $sDestinationPath = $sDestinationDir . $this->localDs . $sDestinationListItemName;
            $mType = $this->filetype($sDestinationPath);

            if ($mType === 'dir') {
                if (!$this->isDir($sSourcePath)) {
                    $this->recursiveFoundInDestinationAndDeletedFromSource($sSourcePath, $sDestinationPath);
                    $this->rmdir($sDestinationPath);
                }

            } elseif ($mType === 'file') {
                if (!$this->isFile($sSourcePath)) {
                    if(!empty($this->oFtpConfig) && !empty($this->oFtpConfig->sTodayFolderName)){
                        //move destination file to date folder and keep first version from today after 00:01 AM
                        $this->move(
                            $sDestinationPath,
                            $this->getDestinationDateBackupFolderPathFromDestinationPath($sDestinationPath),
                            true,
                            true
                        );
                    }
                    else{
                        $this->unlink($sDestinationPath);
                    }

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
        if ($this->isFile($sCopyToPath) && $bTodayFolderName) {
            if (
                (int)filesize($sCopyFromPath) != (int)filesize($sCopyToPath) ||
                filemtime($sCopyFromPath) > filemtime($sCopyToPath)
            ) {
                $sCopyToDateFilePath = $this->getDestinationDateBackupFolderPathFromDestinationPath($sCopyToPath);

                //move destination file to date folder and keep first version from today near 00:01 AM
                if (!$this->isFile($sCopyToDateFilePath)) {
                    $this->move($sCopyToPath, $sCopyToDateFilePath, true);
                }
                //once file moved to date folder
                // now copy original file to destination folder
                $this->copy($sCopyFromPath, $sCopyToPath);
            }
        } else {
            $this->copy($sCopyFromPath, $sCopyToPath);
        }
    }

}
