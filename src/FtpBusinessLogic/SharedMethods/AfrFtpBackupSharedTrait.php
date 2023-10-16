<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\FtpBusinessLogic\SharedMethods;

use Autoframe\Components\FtpTransfer\AfrFtpBackupConfig;

trait AfrFtpBackupSharedTrait
{
    protected string $sSourceFolder;
    protected string $sDestinationFolder;
    protected AfrFtpBackupConfig $oFtpConfig;

    /**
     * @param string $sDirPath
     * @return string
     */
    protected function detectPathSeparator(string $sDirPath): string
    {
        if (
            substr($sDirPath, 0, 2) === '\\\\' || # Detect Windows network path \\192.168.0.1\share
            substr($sDirPath, 1, 2) == ':\\'  #Detect Windows drive path C:\Dir
        ) {
            $iWinDs = 1;
            $iUnixDs = 0;
        } else {
            $iWinDs = substr_count($sDirPath, '\\');
            $iUnixDs = substr_count($sDirPath, '/');
        }

        if ($iWinDs < 1 && $iUnixDs < 1) {
            return DIRECTORY_SEPARATOR;
        }
        return $iWinDs > $iUnixDs ? '\\' : '/';
    }

    /**
     * @param string $sPath
     * @param string $sToDs
     * @return string
     */
    protected function fixSlashStyle(string $sPath, string $sToDs = ''): string
    {
        if (!$sToDs) {
            $sToDs = $this->detectPathSeparator($sPath);
        }
        $sFromDs = $sToDs === '/' ? '\\' : '/';
        $sPath = rtrim(strtr($sPath, $sFromDs, $sToDs), '\/');
        if (strlen($sPath) < 1) {
            $sPath = $sToDs;
        }
        return $sPath;
    }

    /**
     * @param string $sFullPath
     * @param string $sDs
     * @return array
     */
    protected function getFilenameParentDir(string $sFullPath, string $sDs = ''): array
    {
        if (!$sDs) {
            $sDs = $this->detectPathSeparator($sFullPath);
        }
        $sFullPath = $this->fixSlashStyle($sFullPath, $sDs);
        if ($sDs == '\\' && substr($sFullPath, 1, 1) == ':' && strlen($sFullPath) <= 3) {
            $sPath = substr($sFullPath, 0, 2); //root C:
            $sName = '';
        } elseif ($sFullPath == $sDs) {
            $sPath = $sDs; //root path / or \
            $sName = '';
        } else {
            $sName = substr($sFullPath, -strpos(strrev($sFullPath), $sDs));
            $sPath = $this->fixSlashStyle(substr($sFullPath, 0, -strlen($sName)), $sDs);
        }
        // echo "[$sFullPath] ($sPath$sDs$sName) path:'$sPath' name:'$sName' \n";
        return [$sPath, $sName];
    }

    /**
     * @return string
     */
    protected function getDestinationFolderWithFolderName(): string
    {

        $sLatestFolderName = '';
        if (!empty($this->oFtpConfig) && strlen($this->oFtpConfig->sLatestFolderName)) {
            $sLatestFolderName = $this->oFtpConfig->sLatestFolderName;
        } elseif (!empty($this->sLatestFolderName)) {
            $sLatestFolderName = $this->sLatestFolderName;
        }
        if ($sLatestFolderName) {
            return $this->sDestinationFolder .
                $this->detectPathSeparator($this->sDestinationFolder) .
                $this->oFtpConfig->sLatestFolderName;
        }
        return $this->sDestinationFolder;
    }

    /**
     * @return string
     */
    protected function getDestinationDayBackupFolderPath(): string
    {
        if (!empty($this->oFtpConfig) && !empty($this->oFtpConfig->sTodayFolderName)) {
            $sTodayFolderName = $this->oFtpConfig->sTodayFolderName;
        } else {
            $sTodayFolderName = date('Ymd');
        }
        return $this->sDestinationFolder .
            $this->detectPathSeparator($this->sDestinationFolder) .
            $sTodayFolderName;
    }

}