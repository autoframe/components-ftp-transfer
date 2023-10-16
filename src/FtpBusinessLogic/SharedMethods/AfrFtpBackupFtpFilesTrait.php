<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\FtpBusinessLogic\SharedMethods;

use Autoframe\Components\FtpTransfer\AfrFtpBackupConfig;
use Autoframe\Components\FtpTransfer\Connection\AfrFtpConnectionClass;
use Autoframe\Components\FtpTransfer\Connection\AfrFtpConnectionInterface;

trait AfrFtpBackupFtpFilesTrait
{
    use AfrFtpBackupSharedTrait;
    use AfrFtpBackupActionLogTrait;

    protected array $aFileListFtp = [];
    protected string $ftpDs = '/';
    public string $ftpTimezone = 'UTC';
    protected AfrFtpConnectionInterface $oFtpConnection;

    /**
     * @param AfrFtpConnectionInterface|null $oFtpConnection
     * @return AfrFtpConnectionInterface
     */
    protected function xetAfrFtpConnection(AfrFtpConnectionInterface $oFtpConnection = null): AfrFtpConnectionInterface
    {
        if ($oFtpConnection) {
            $this->oFtpConnection = $oFtpConnection;
        } elseif (empty($this->oFtpConnection)) {
            $this->oFtpConnection = new AfrFtpConnectionClass(
                $this->oFtpConfig->ConServer,
                $this->oFtpConfig->ConUsername,
                $this->oFtpConfig->ConPassword,
                $this->oFtpConfig->ConPassive,
                $this->oFtpConfig->ConPort,
                $this->oFtpConfig->ConTimeout,
                $this->oFtpConfig->iDirPermissions,
            );
        }
        return $this->oFtpConnection;
    }

    /**
     * Cleanup
     */
    public function __destruct()
    {
        $this->xetAfrFtpConnection()->__destruct();
    }

    /**
     * @param string $sDirPath
     * @return array|false
     */
    protected function ftp_mlsd(string $sDirPath)
    {
        if (!$this->oFtpConnection->getConnection()) {
            return false;
        }
        $sDirPath = $this->fixSlashStyle($sDirPath, $this->ftpDs);
        $mList = ftp_mlsd($this->oFtpConnection->getConnection(), $sDirPath);
        if (!empty($mList) && is_array($mList)) {
            $aList = [];
            foreach ($mList as $aEntry) {
                if ($aEntry['name'] === '.' || $aEntry['name'] === '..') {
                    continue;
                }
                //[modify] => 20230709051829   2023-07-09T13:10:38 UTC
                $date =
                    substr($aEntry['modify'], 0, 4) . '-' .
                    substr($aEntry['modify'], 4, 2) . '-' .
                    substr($aEntry['modify'], 6, 2) . 'T' .
                    substr($aEntry['modify'], 8, 2) . ':' .
                    substr($aEntry['modify'], 10, 2) . ':' .
                    substr($aEntry['modify'], 12, 2) . ' ' . $this->ftpTimezone;
                $aEntry['date'] = $date;
                $aEntry['ts'] = strtotime($date);
                //unset($aEntry['UNIX.mode']);
                //unset($aEntry['UNIX.uid']);
                //unset($aEntry['UNIX.gid']);
                //unset($aEntry['unique']);
                //unset($aEntry['modify']);
                $aList[$aEntry['name']] = $aEntry;
            }

            $this->aFileListFtp[$sDirPath] = $aList;
        } else {
            $this->aFileListFtp[$sDirPath] = $mList;
        }
        return $mList;
    }


    /**
     * @param string $sDirPath
     * @param bool $bRefreshCache
     * @param string[] $aTypes
     * @return array
     */
    protected function getDirFileListFtp(
        string $sDirPath,
        bool   $bRefreshCache = false,
        array  $aTypes = ['dir', 'file']
    ): array
    {
        $aContents = [];
        if (!$this->oFtpConnection->getConnection()) {
            return [];
        }
        $sDirPath = $this->fixSlashStyle($sDirPath, $this->ftpDs);
        if ($bRefreshCache || !isset($this->aFileListFtp[$sDirPath])) {
            $this->ftp_mlsd($sDirPath);
        }

        if (!empty($this->aFileListFtp[$sDirPath]) && is_array($this->aFileListFtp[$sDirPath])) {
            foreach ($this->aFileListFtp[$sDirPath] as $aEntry) {
                if ($aEntry['name'] === '.' || $aEntry['name'] === '..') {
                    continue;
                }
                if (!empty($aTypes) && in_array($aEntry['type'], $aTypes) || empty($aTypes)) {
                    $aContents[] = $aEntry['name'];
                }
            }
        }
        return $aContents;
    }

    /**
     * If dir contains files or dirs or *, returns true
     * If dir id empty, returns false
     * @param string $dir
     * @param bool $bRefreshCache
     * @return bool
     */
    protected function ftpPathContainsFilesOrDirs(string $dir, bool $bRefreshCache = false): bool
    {
        return count($this->getDirFileListFtp($dir, $bRefreshCache)) > 0;
    }

    /**
     * If the dir does nor exist it returns false
     * If it exists and contains files, also returns false
     * If it exists and is empty, returns true
     * @param string $sDirPath
     * @param bool $bRefreshCache
     * @return bool
     */
    protected function isDirEmptyFtp(string $sDirPath, bool $bRefreshCache = false): bool
    {
        return count($this->getDirFileListFtp($sDirPath, $bRefreshCache, [])) > 0;
    }


    /**
     * @param string $sFullPath
     * @param bool $bForceCacheRefresh
     * @return false|mixed|string
     */
    protected function filetypeFtp(string $sFullPath, bool $bForceCacheRefresh = false)
    {
        if (!$this->oFtpConnection->getConnection()) {
            return false;
        }
        $sFullPath = $this->fixSlashStyle($sFullPath, $this->ftpDs);
        if (in_array($sFullPath, [$this->ftpDs, '.', '..'])) {
            return 'dir';
        } else {
            list($sPath, $sName) = $this->getFilenameParentDir($sFullPath, $this->ftpDs);
            if (
                $bForceCacheRefresh ||
                !isset($this->aFileListFtp[$sPath])
                //    ||  !isset($this->aFileListFtp[$sPath][$sName]) //SPEED OPT
            ) {
                $this->ftp_mlsd($sPath);
            }
            if (isset($this->aFileListFtp[$sPath][$sName]['type'])) {
                return $this->aFileListFtp[$sPath][$sName]['type'];
            }
            return false;
        }
    }

    /**
     * @param string $sFullPath
     * @param bool $bForceCacheRefresh
     * @return false|int
     */
    protected function filemtimeFtp(string $sFullPath, bool $bForceCacheRefresh = false)
    {
        if (!$this->oFtpConnection->getConnection()) {
            return false;
        }
        $mType = $this->filetypeFtp($sFullPath, $bForceCacheRefresh);
        if ($mType === 'dir' || $mType === 'file') {
            list($sPath, $sName) = $this->getFilenameParentDir($sFullPath, $this->ftpDs);
            return (int)$this->aFileListFtp[$sPath][$sName]['ts'];
        }
        return false;
    }


    /**
     * File or Directory
     * @param string $sPath
     * @param bool $bForceCacheRefresh
     * @return bool
     */
    protected function fileExistsFtp(string $sPath, bool $bForceCacheRefresh = false): bool
    {
        return $this->isFileFtp($sPath, $bForceCacheRefresh) || $this->isDirFtp($sPath, false);
    }

    /**
     * @param string $sPath
     * @param bool $bForceCacheRefresh
     * @return bool
     */
    protected function isFileFtp(string $sPath, bool $bForceCacheRefresh = false): bool
    {
        return $this->filetypeFtp($sPath, $bForceCacheRefresh) === 'file';
    }

    /**
     * @param string $sDirPath
     * @param bool $bForceCacheRefresh
     * @return bool
     */
    protected function isDirFtp(string $sDirPath, bool $bForceCacheRefresh = false): bool
    {
        return $this->filetypeFtp($sDirPath, $bForceCacheRefresh) === 'dir';
    }

    /**
     * @param string $sFilePath
     * @param bool $bForceCacheRefresh
     * @return bool
     */
    protected function unlinkFtp(string $sFilePath, bool $bForceCacheRefresh = false): bool
    {

        $sFilePath = $this->fixSlashStyle($sFilePath, $this->ftpDs);
        if ($this->isFileFtp($sFilePath, $bForceCacheRefresh)) {
            $bResponse = ftp_delete($this->oFtpConnection->getConnection(), $sFilePath);
            if ($bResponse) {
                $this->ActionLog('deleted', 'Removed File $s', $sFilePath, '');
                list($sPath, $sName) = $this->getFilenameParentDir($sFilePath, $this->ftpDs);
                unset($this->aFileListFtp[$sPath][$sName]);
            } else {
                $this->filetype($sFilePath, true); //remake cache
                $this->ActionLog('err', 'ERROR File not removable $s', $sFilePath, '');
            }
            return $bResponse;
        }
        return false;
    }

    /**
     * @return void
     */
    protected function listFtpCache(): void
    {
        $aFileListFtpSubDir = $this->aFileListFtp;
        ksort($aFileListFtpSubDir);
        foreach ($aFileListFtpSubDir as $sPath => $aInfo) {
            echo str_repeat(' ', 2) . '↠' . $sPath . "\n";
            foreach ($aInfo as $aEntry) {
                if ($aEntry['name'] === '.' || $aEntry['name'] === '..') {
                    continue;
                }
                echo str_repeat(' ', 2) . '┗' . $aEntry['name'] . "\n";
            }
            echo "\n";
        }
        echo "\n\n---\n";
    }

    /**
     * @param string $sDirPath
     * @param bool $bForceCacheRefresh
     * @param bool $bRecursive
     * @return bool
     */
    protected function rmdirFtp(
        string $sDirPath,
        bool   $bForceCacheRefresh = false,
        bool   $bRecursive = false
    ): bool
    {
        if (!$this->oFtpConnection->getConnection()) {
            return false;
        }
        $sDirPath = $this->fixSlashStyle($sDirPath, $this->ftpDs);
        $bIsDir = $this->isDirFtp($sDirPath, $bForceCacheRefresh);
        $bDirHasChildren = $bIsDir && $this->isDirEmptyFtp($sDirPath);
        if ($bIsDir && $bRecursive) {
            $bDirHasChildren = false;
            $aContents = $this->getDirFileListFtp($sDirPath);
            foreach ($aContents as $sItem) {
                $sItemPath = $sDirPath . $this->ftpDs . $sItem;
                $sItemType = $this->filetypeFtp($sItemPath);
                if ($sItemType === 'dir' && !$this->rmdirFtp($sItemPath, $bForceCacheRefresh, $bRecursive)) {
                    $bDirHasChildren = true;
                    break;
                } elseif ($sItemType === 'file' && !$this->unlinkFtp($sItemPath)) {
                    $bDirHasChildren = true;
                    break;
                }
            }
        }


        if (!$bIsDir) {
            $this->ActionLog('info', 'Folder not removable because it was not found $s', $sDirPath, '');
        } elseif ($bDirHasChildren) {
            $this->ActionLog('err', 'Folder not removable because it contains files $s', $sDirPath, '');
        } else {
            $bResponse = ftp_rmdir($this->oFtpConnection->getConnection(), $sDirPath);
            if ($bResponse) {
                $this->ActionLog('deleted', 'Removed Empty Folder $s', $sDirPath, '');
                list($sParentPath, $sRemovedName) = $this->getFilenameParentDir($sDirPath, $this->ftpDs);
                unset($this->aFileListFtp[$sDirPath]);
                unset($this->aFileListFtp[$sParentPath][$sRemovedName]);
            } else {
                $this->ActionLog('err', 'ERROR Folder not removable because is not empty $s', $sDirPath, '');
            }

            return $bResponse;
        }
        return false;
    }

    /**
     * @param string $sNewDir
     * @param bool $bForceCacheRefresh
     * @return bool
     */
    protected function mkdirFtp(string $sNewDir, bool $bForceCacheRefresh = false): bool
    {
        if (!$this->oFtpConnection->getConnection()) {
            return false;
        }
        $sNewDir = $this->fixSlashStyle($sNewDir, $this->ftpDs);
        if ($this->isDirFtp($sNewDir, $bForceCacheRefresh)) {
            return true;
        }
        if (isset($this->aFileListFtp[$sNewDir])) {
            unset($this->aFileListFtp[$sNewDir]);//clear file list cache if any
        }
        list($sParentPath, $sNewDirName) = $this->getFilenameParentDir($sNewDir, $this->ftpDs);
        if (
            strlen($sParentPath) &&
            $sParentPath !== $this->ftpDs &&
            !$this->isDirFtp($sParentPath, $bForceCacheRefresh)
        ) {
            //create parent dir if missing
            if (!$this->mkdirFtp($sParentPath, $bForceCacheRefresh)) {
                $this->ActionLog('err', 'ERROR Folder not created recursively $s', $sNewDir, '');
                return false;
            }
            if (isset($this->aFileListFtp[$sParentPath])) {
                unset($this->aFileListFtp[$sParentPath]);//clear parent cache if any
            }
        }

        $bStatus = ftp_mkdir($this->oFtpConnection->getConnection(), $sNewDir);
        if ($bStatus) {
            @ftp_chmod($this->oFtpConnection->getConnection(), $this->oFtpConnection->getDirPerms(), $sNewDir);
            //$this->ftp_mlsd($sNewDir);
            $this->ftp_mlsd($sParentPath);
            $this->ActionLog('add', 'Folder Created $s', $sNewDir, '');
        } else {
            $this->ActionLog('err', 'ERROR Folder not created $s', $sNewDir, '');
        }
        return (bool)$bStatus;
    }


    /**
     * @param string $sFrom
     * @param string $sTo
     * @param bool $bRefreshCache
     * @return bool
     */
    protected function copyToFtp(string $sFrom, string $sTo, bool $bRefreshCache = false): bool
    {
        if (!$this->oFtpConnection->getConnection()) {
            return false;
        }
        $sTo = $this->fixSlashStyle($sTo, $this->ftpDs);
        if (!$this->isFile($sFrom, $bRefreshCache)) {
            $this->ActionLog('err', 'FILE is missing `$d` $s', $sFrom, '');
            return false;
        }
        list($sPathTo, $sNameTo) = $this->getFilenameParentDir($sTo, $this->ftpDs);
        if (!$this->mkdirFtp($sPathTo, $bRefreshCache)) {
            $this->ActionLog('err', 'Invalid destination when copy file from $s to $d', $sFrom, $sTo);
            return false;
        }

        $bOverwrite = $this->isFileFtp($sTo, $bRefreshCache);
        $iFileSizeGb = ceil(filesize($sFrom) / 1024 / 1024 / 1024);
        $bR = false;

        $maxRetry = $iFileSizeGb + 2;
        if ($maxRetry > 100) {
            $maxRetry = 20;
        } elseif ($maxRetry > 10) {
            $maxRetry = 8;
        }
        for ($iCurrentRetry = 1; $iCurrentRetry <= $maxRetry; $iCurrentRetry++) {
            $this->ActionLog('info', 'Retry #' . $iCurrentRetry . ' Upload started at ' . date('H:i:s') . ' $s to $d ', $sFrom, $sTo);
            if ($iCurrentRetry > 1) {
                $this->oFtpConnection->reconnect(50 * $iCurrentRetry);
            }
            $bR = $this->uploadFtpProgress($sFrom, $sTo, $iCurrentRetry);
            if ($bR) {
                $this->ActionLog('info', 'Upload completed at ' . date('H:i:s') . ' $s to $d ', $sFrom, $sTo);
                break; //uploaded successfully
            }
        }

        if ($bR) {
            if ($bOverwrite) {
                $this->ActionLog('overwrite', 'File was overwritten successfully from $s to $d', $sFrom, $sTo);
            } else {
                $this->ActionLog('add', 'File uploaded successfully from $s to $d', $sFrom, $sTo);
            }
            $this->aFileListFtp[$sPathTo][$sNameTo] = [
                'name' => $sNameTo,
                'type' => 'file',
                'size' => filesize($sFrom),
                'modify' => gmdate('YmdHis'),
                'date' => gmdate('Y-m-d\TH:i:s') . ' UTC',
                'ts' => time()
            ];
        } else {
            $this->ActionLog('err', 'ERROR when uploading file from $s to $d', $sFrom, $sTo);
            $this->filetype($sTo, true);
        }
        return $bR;
    }

    /**
     * @param string $sFrom
     * @param string $sTo
     * @param bool $bKeepAllCopies
     * @param bool $bRefreshCache
     * @return bool
     */
    protected function moveFtp(string $sFrom, string $sTo, bool $bKeepAllCopies = false, bool $bRefreshCache = false): bool
    {
        if (!$this->oFtpConnection->getConnection()) {
            return false;
        }
        $sFrom = $this->fixSlashStyle($sFrom, $this->ftpDs);
        $sTo = $this->fixSlashStyle($sTo, $this->ftpDs);
        if (!$this->fileExistsFtp($sFrom, $bRefreshCache)) { //isFileFtp
            $this->ActionLog('err', 'FILE TYPE ERROR `$d` from $s', $sFrom, $sFrom);
            return false;
        }
        list($sPathTo, $sNameTo) = $this->getFilenameParentDir($sTo, $this->ftpDs);
        if (!$this->mkdirFtp($sPathTo, $bRefreshCache)) {
            $this->ActionLog('err', 'Target directory is missing when moving from $s to $d', $sFrom, $sTo);
            return false;
        }
        $bOverwriteDestinationFile = false;
        if ($this->isFileFtp($sFrom)) {
            $bOverwriteDestinationFile = $this->isFileFtp($sTo);
            if ($bOverwriteDestinationFile && $bKeepAllCopies) {
                $sToV = '.V' . $this->filemtimeFtp($sTo);
                if (ftp_rename($this->oFtpConnection->getConnection(), $sTo, $sTo . $sToV)) {
                    $this->aFileListFtp[$sPathTo][$sNameTo . $sToV] = $this->aFileListFtp[$sPathTo][$sNameTo];
                    $this->aFileListFtp[$sPathTo][$sNameTo . $sToV]['name'] = $sNameTo . $sToV;
                    unset($this->aFileListFtp[$sPathTo][$sNameTo]);
                }
            }
        }

        if (ftp_rename($this->oFtpConnection->getConnection(), $sFrom, $sTo)) {
            if ($bOverwriteDestinationFile) {
                if ($bKeepAllCopies) {
                    $this->ActionLog('add', 'Copy overwrite prevented $s to $d', $sFrom, $sTo);

                } else {
                    $this->ActionLog('overwrite', 'Move overwrite from $s to $d', $sFrom, $sTo);
                }
            } else {
                $this->ActionLog('add', ucwords($this->filetypeFtp($sFrom)) . ' moved from $s to $d', $sFrom, $sTo);
            }
            list($sPathFrom, $sNameFrom) = $this->getFilenameParentDir($sFrom, $this->ftpDs);
            $this->aFileListFtp[$sPathTo][$sNameTo] = [
                'name' => $sNameTo,
                'type' => 'file',
                'size' => ftp_size($this->oFtpConnection->getConnection(), $sTo),
                'modify' => gmdate('YmdHis'),
                'date' => gmdate('Y-m-d\TH:i:s') . ' UTC',
                'ts' => time()
            ];
            unset($this->aFileListFtp[$sPathFrom][$sNameFrom]);
            return true;
        }

        $this->ActionLog('err', 'ERROR when moving file from $s to $d', $sFrom, $sTo);
        return false;
    }

    /**
     * @param string $sFrom
     * @param string $sTo
     * @param int $iCurrentRetry
     * @param int $iMaxTimeout
     * @return bool
     */
    protected function uploadFtpProgress(string $sFrom, string $sTo, int $iCurrentRetry = 1, int $iMaxTimeout = 90): bool
    {
        if (!$this->oFtpConnection->getConnection()) {
            return false;
        }
        $tStart = time();
        $iFileSize = (int)filesize($sFrom);
        $fp = fopen($sFrom, 'r');

        //ftp_set_option($this->oFtpConnection->getConnection(), FTP_TIMEOUT_SEC, $iMaxTimeout);
        $ret = ftp_nb_fput($this->oFtpConnection->getConnection(), $sTo, $fp, FTP_BINARY);

        $iLastPercent = -1;
        $dProgress = 0;
        if ($ret != FTP_FINISHED) {
            while ($ret == FTP_MOREDATA) {
                ob_start();
                $ret = ftp_nb_continue($this->oFtpConnection->getConnection()); //TODO
                $sWarnings = ob_get_contents();
                ob_end_clean();
                if (strlen($sWarnings) && $dProgress <= 99.95) {
                    echo $sWarnings;
                    fclose($fp);
                    $mConn = $this->oFtpConnection->reconnect(1000 * 20);
                    $this->ActionLog(
                        'info',
                        'Reconnecting to FTP: ' .
                        (empty($mConn) ? 'FAILED' : 'SUCCESS') .
                        '! Preventing possible data loss $s to $d ' . date('H:i:s'),
                        $sFrom,
                        $sTo
                    );
                    return false;
                }
                // Calculate upload progress
                $dProgress = $this->calculateUploadProgress(
                    (int)ftell($fp),
                    $iFileSize,
                    $iLastPercent,
                    $iMaxTimeout,
                    $tStart,
                    $iCurrentRetry,
                    $sFrom,
                    $sTo
                );
            }
        }


        //final status
        $iLastPercent = 99.9999;
        $this->calculateUploadProgress(
            (int)ftell($fp),
            $iFileSize,
            $iLastPercent,
            $iMaxTimeout,
            $tStart,
            $iCurrentRetry,
            $sFrom,
            $sTo
        );
        fclose($fp);
        if ($ret !== FTP_FINISHED) {
            $mConn = $this->oFtpConnection->reconnect(1000 * 20);
            if (ftp_size($this->oFtpConnection->getConnection(), $sTo) === $iFileSize) {
                return true; //FTP STUPID ERROR! File is ok!
            }
            $this->ActionLog('err', 'FAILED(' . $ret . ') when uploading progress file from $s to $d ' . date('H:i:s'), $sFrom, $sTo);
            $this->ActionLog(
                empty($mConn) ? 'err' : 'info',
                'Reconnecting to FTP: ' . (empty($mConn) ? 'FAILED' : 'SUCCESS') . ' ' . date('H:i:s'),
                $sFrom,
                $sTo
            );
            return false;
        }

        return true;
    }

    /**
     * @param int $iUploadedBytes
     * @param int $iFileSize
     * @param $iLastPercent
     * @param int $iMaxTimeout
     * @param int $tStart
     * @param int $iCurrentRetry
     * @param string $sFrom
     * @param string $sTo
     * @return float
     */
    protected function calculateUploadProgress(
        int    $iUploadedBytes,
        int    $iFileSize,
               &$iLastPercent,
        int    $iMaxTimeout,
        int    $tStart,
        int    $iCurrentRetry,
        string $sFrom,
        string $sTo
    ): float
    {
        if ($iUploadedBytes > 0) {
            $dPercent = round(($iUploadedBytes / $iFileSize) * 100, 4);
        } else {
            $dPercent = 0.0;
        }
        $iPercent = floor($dPercent);
        if ($iPercent == 100 && $iUploadedBytes != $iFileSize) {
            $iPercent = 99.9999;
        }
        if ($iLastPercent != $iPercent) {
            $iDecimals = $iLastPercent === 99.9999 || $dPercent >= 99.9999 ? 6 : 2;
            $iLastPercent = $iPercent;

           // $sInfo = ' [Max timeout ' . $iMaxTimeout . ' seconds]';
            $sInfo = '';
            if ($dPercent) {
                $iSeconds = max(time() - $tStart, 0.1);
                $sSeconds = $iSeconds . ' sec';
                if ($iSeconds > 3600) {
                    $sSeconds = number_format($iSeconds / 3600, 2) . ' hours';
                } elseif ($iSeconds > 60) {
                    $sSeconds = number_format($iSeconds / 60, 1) . ' min';
                }
                $dSpeed = $iPercent ? number_format($iUploadedBytes / 1024 / 1024 / $iSeconds, 2) : 0;
                $dSpeed = max($dSpeed, 0.004);
                $iRemaining = $dSpeed ? ceil(($iFileSize - $iUploadedBytes) / 1024 / 1024 / $dSpeed) : '...';
                if (!$dSpeed) {
                    $sRemaining = $iRemaining;
                } elseif ($iRemaining < 60) {
                    $sRemaining = $iRemaining . ' sec';
                } elseif ($iRemaining < 60 * 60) {
                    $sRemaining = ceil($iRemaining / 60) . ' min';
                } else {
                    $sRemaining = ceil($iRemaining / 60 / 60) . ' hours';
                }

                $sInfo =
                    ' (' .
                    number_format($iUploadedBytes / 1024 / 1024, $iDecimals) . ' / ' .
                    number_format($iFileSize / 1024 / 1024, $iDecimals) . 'MB)' .
                    '[' . $sSeconds . ' @' . $dSpeed . ' MB/s]' .
                    '[left ~' . $sRemaining . ']';
            }
            $this->ActionLog(
                'info',
                'Upload progress ' . $iLastPercent . '%' . $sInfo . ' (retry #' . $iCurrentRetry . ') $s to $d ' . date('H:i:s'),
                $sFrom,
                $sTo
            );
        }
        return $dPercent;
    }

    /**
     * @param string $sPath
     * @param bool $bRefreshFtpData
     * @return int
     */
    protected function filesizeFtp(string $sPath, bool $bRefreshFtpData = false): int
    {
        if ($this->isFileFtp($sPath, $bRefreshFtpData)) {
            $aDirPath = explode($this->ftpDs, $sPath);
            $sFileName = $aDirPath[count($aDirPath) - 1];
            unset($aDirPath[count($aDirPath) - 1]);
            $sDirPath = implode($this->ftpDs, $aDirPath);
            if (!empty($this->aFileListFtp[$sDirPath]) && is_array($this->aFileListFtp[$sDirPath])) {
                foreach ($this->aFileListFtp[$sDirPath] as $aData) {
                    if (isset($aData['name']) && $aData['name'] === $sFileName) {
                        if (isset($aData['sizd'])) {
                            return (int)$aData['sizd'];
                        } elseif (isset($aData['size'])) {
                            return (int)$aData['size'];
                        } else return ftp_size($this->oFtpConnection->getConnection(), $sPath);
                    }
                }
            }
        }
        return 0;
    }
}