<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\FtpBusinessLogic\SharedMethods;

trait AfrFtpBackupLocalFilesTrait
{
    use AfrFtpBackupSharedTrait;
    use AfrFtpBackupActionLogTrait;

    protected array $aFiletype = [];
    protected string $localDs = DIRECTORY_SEPARATOR;


    /**
     * @param string $sDirPath
     * @return array
     */
    protected function getDirFileList(string $sDirPath): array
    {
        $aContents = [];
        $handle = opendir($sDirPath);
        if ($handle) {
            $localDs = $this->detectPathSeparator($sDirPath);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && $this->fileExists($sDirPath . $localDs . $entry)) {
                    $aContents[] = $entry;
                }
            }
            closedir($handle);
        }
        return $aContents;
    }

    /**
     * If dir contains files or dirs or *, returns true
     * If dir id empty, returns false
     * @param string $dir
     * @return bool
     */
    protected function dirHasChildren(string $dir): bool
    {
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..') {
                closedir($handle);
                return true;
            }
        }
        closedir($handle);
        return false;
    }

    /**
     * @param string $sPath
     * @param bool $bForceCacheRefresh
     * @return false|mixed|string
     */
    protected function filetype(string $sPath, bool $bForceCacheRefresh = false)
    {
        //$sPath = $this->fixSlashStyle($sPath);
        $sPath = rtrim($sPath, '\/');
        if (!isset($this->aFiletype[$sPath]) || $bForceCacheRefresh) {
            //Possible values are fifo, char, dir, block, link, file, socket and unknown.
            $this->aFiletype[$sPath] = @filetype($sPath);
        }
        return $this->aFiletype[$sPath];
    }

    /**
     * File or Directory
     * @param string $sPath
     * @param bool $bForceCacheRefresh
     * @return bool
     */
    protected function fileExists(string $sPath, bool $bForceCacheRefresh = false): bool
    {
        return $this->isFile($sPath, $bForceCacheRefresh) || $this->isDir($sPath, false);
    }

    /**
     * @param string $sPath
     * @param bool $bForceCacheRefresh
     * @return bool
     */
    protected function isFile(string $sPath, bool $bForceCacheRefresh = false): bool
    {
        return $this->filetype($sPath, $bForceCacheRefresh) === 'file';
    }

    /**
     * @param string $sDirPath
     * @param bool $bForceCacheRefresh
     * @return bool
     */
    protected function isDir(string $sDirPath, bool $bForceCacheRefresh = false): bool
    {
        return $this->filetype($sDirPath, $bForceCacheRefresh) === 'dir';
    }

    /**
     * @param string $sFilePath
     * @param bool $bIgnoreCachedExistenceCheck
     * @return bool
     */
    protected function unlink(string $sFilePath, bool $bIgnoreCachedExistenceCheck = false): bool
    {
        if ($this->isFile($sFilePath, $bIgnoreCachedExistenceCheck)) {
            $bResponse = unlink($sFilePath);
            if ($bResponse) {
                $this->ActionLog('deleted', 'Removed File $s', $sFilePath, '');
                unset($this->aFiletype[$sFilePath]);
            } else {
                $this->filetype($sFilePath, true); //remake cache
                $this->ActionLog('err', 'ERROR File not removable $s', $sFilePath, '');
            }
            return $bResponse;
        }
        return false;
    }


    /**
     * @param string $sDirPath
     * @param bool $bRecursive
     * @return bool
     */
    protected function rmdir(string $sDirPath, bool $bRecursive = false): bool
    {
        $sDs = $this->detectPathSeparator($sDirPath);
        $sDirPath = $this->fixSlashStyle($sDirPath, $sDs);
        $bIsDir = $this->isDir($sDirPath);
        $bDirHasChildren = $bIsDir && $this->isDirEmpty($sDirPath);

        if ($bIsDir && $bRecursive) {
            $bDirHasChildren = false;
            foreach ($this->getDirFileList($sDirPath) as $sItem) {
                $sItemPath = $sDirPath . $this->ftpDs . $sItem;
                $sItemType = $this->filetype($sItemPath);
                if ($sItemType === 'dir' && !$this->rmdir($sItemPath, $bRecursive)) {
                    $bDirHasChildren = true;
                    break;
                } elseif ($sItemType === 'file' && !$this->unlink($sItemPath)) {
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
            $bResponse = rmdir($sDirPath);
            if ($bResponse) {
                $this->ActionLog('deleted', 'Removed Empty Folder $s', $sDirPath, '');
                unset($this->aFiletype[$sDirPath]);
            } else {
                $this->ActionLog('err', 'ERROR Folder not removable $s', $sDirPath, '');
            }
            return $bResponse;
        }
        return false;
    }


    /**
     * @param string $sDestinationDir
     * @param bool $bForceCacheRefresh
     * @return bool
     */
    protected function mkdir(string $sDestinationDir, bool $bForceCacheRefresh = false): bool
    {
        if ($this->isDir($sDestinationDir, $bForceCacheRefresh)) {
            return true;
        }
        $bStatus = mkdir($sDestinationDir, $this->iDirPermissions, true);
        if ($bStatus) {
            $this->aFiletype[$sDestinationDir] = 'dir';
            $this->ActionLog('add', 'Folder Created $s', $sDestinationDir, '');
        } else {
            $this->ActionLog('err', 'ERROR Folder not created $s', $sDestinationDir, '');
        }
        return $bStatus;
    }

    /**
     * If the dir does nor exist it returns false
     * If it exists and contains files, also returns false
     * If it exists and is empty, returns true
     * @param string $dir
     * @return bool
     */
    protected function isDirEmpty(string $dir): bool
    {
        if (!$this->isDir($dir)) {
            return false;
        }
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..') {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }

    /**
     * @param string $sFrom
     * @param string $sTo
     * @param bool $bOverwriteTarget
     * @return bool
     */
    protected function copy(string $sFrom, string $sTo, bool $bOverwriteTarget = true): bool
    {
        if (!$this->isFile($sFrom)) {
            $this->ActionLog('err', 'FILE TYPE ERROR `$d` $s', $sFrom, $this->aFiletype[$sFrom]);
            return false;
        }
        $bTargetExists = $this->isFile($sTo);
        if ($bTargetExists && !$bOverwriteTarget) {
            $this->ActionLog('overwrite', 'File flagged as NO OVERWRITE from $s to $d', $sFrom, $sTo);
            return false;
        }

        if (copy($sFrom, $sTo)) {
            if ($bTargetExists) {
                $this->ActionLog('overwrite', 'File overwrite from $s to $d', $sFrom, $sTo);
            } else {
                $this->ActionLog('add', 'File copied from $s to $d', $sFrom, $sTo);
            }
            $this->aFiletype[$sTo] = 'file';
            return true;
        }
        $this->ActionLog('err', 'ERROR when copy file from $s to $d', $sFrom, $sTo);
        $this->filetype($sTo, true);
        return false;
    }

    /**
     * @param string $sFrom
     * @param string $sTo
     * @param bool $bKeepAllCopies
     * @param bool $bRefreshCache
     * @return bool
     */
    protected function move(string $sFrom, string $sTo, bool $bKeepAllCopies = false, bool $bRefreshCache = false): bool
    {
        $sFrom = $this->fixSlashStyle($sFrom);
        $sTo = $this->fixSlashStyle($sTo);
        if (!$this->fileExists($sFrom, $bRefreshCache)) { //isFile
            $this->ActionLog('err', 'FILE TYPE ERROR `$d` from $s', $sFrom, $this->aFiletype[$sFrom]);
            return false;
        }
        list($sPathTo, $sNameTo) = $this->getFilenameParentDir($sTo, $this->localDs);
        if (!$this->mkdir($sPathTo, $bRefreshCache)) {
            $this->ActionLog('err', 'Target directory is missing when moving from $s to $d', $sFrom, $sTo);
            return false;
        }
        $bOverwriteDestinationFile = false;
        if ($this->isFile($sTo, $bRefreshCache)) {
            $bOverwriteDestinationFile = $this->fileExists($sTo, $bRefreshCache);
            if ($bOverwriteDestinationFile && $bKeepAllCopies) {
                $sToV = '.V' . filemtime($sTo);
                if (rename($sTo, $sTo . $sToV)) {
                    $this->aFiletype[$sTo . $sToV] = 'file';
                    unset($this->aFiletype[$sTo]);
                }
            }
        }

        if (rename($sFrom, $sTo)) {
            if ($bOverwriteDestinationFile) {
                if ($bKeepAllCopies) {
                    $this->ActionLog('add', 'Copy overwrite prevented $s to $d', $sFrom, $sTo);

                } else {
                    $this->ActionLog('overwrite', 'Move overwrite from $s to $d', $sFrom, $sTo);
                }
            } else {
                $this->ActionLog('add', ucwords($this->filetype($sFrom)) . ' moved from $s to $d', $sFrom, $sTo);
            }
            unset($this->aFiletype[$sFrom]);
            $this->aFiletype[$sTo] = 'file';
            return true;
        }
        $this->ActionLog('err', 'ERROR when moving file from $s to $d', $sFrom, $sTo);
        return false;
    }

}