<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use Autoframe\Components\FtpTransfer\AfrFtpBackupConfig;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\AfrFtpPutBigDataFacade;
use Autoframe\Components\FtpTransfer\Report\AfrFtpReportBpg;
use Autoframe\Components\FileSystem\Versioning\AfrDirMaxFileMtimeClass;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\AfrFtpNbrCopiesDms;
use Autoframe\Components\FtpTransfer\Log\AfrFtpLogInline;
use Autoframe\Process\Control\Lock\AfrLockFileClass;

class AfrFtpNbrCopiesDmsX extends AfrFtpNbrCopiesDms
{
    protected function recursiveFoundInDestinationAndDeletedFromSource(
        string $sSourceDir,
        string $sDestinationDir
    ): bool
    {
        return true;
    }
}


//////// LOCK
$GLOBALS['oLockSingle'] = new AfrLockFileClass('MG1_BerMetal');
register_shutdown_function(function () {
    $GLOBALS['oLockSingle']->releaseLock();
});
if (!$GLOBALS['oLockSingle']->obtainLock()) {
    die('Only a single instance of this script may run at any time. Current PID is:' . $GLOBALS['oLockSingle']->getLockPid());
}


////////////////  CONFIG
date_default_timezone_set('Europe/Bucharest');
$iStartHour = 8;
$iStartHour = 1;
$iStopHour = 19;

$aPathsPairs = [
    [
        'H:\WindowsImageBackup_TO_NAS',
        //'H:\WindowsImageBackup_TO_NAS',
        'H:\WindowsImageBackup_TO_NAS',
        '/bpg-backup/MG1/BPG-Offsite-' . date('m-d') . '-FULL!'
    ]
];

$oLocalConfig = new AfrFtpBackupConfig('');
$oFtpConfig = new AfrFtpBackupConfig();
$oFtpConfig->ConServer = '82.77.000.000';
$oFtpConfig->ConUsername = 'bpg-backup';
$oFtpConfig->ConPassword = '---------------';
$oFtpConfig->ConPort = 21;
$oFtpConfig->ConPassive = true;
$oFtpConfig->sReportToSecond = 'it@domain.org';
$oFtpConfig->sReportTo = 'dj@gmail.com';
$oFtpConfig->sReportSubject = 'Ftp upload report';
$oFtpConfig->sReportTarget = 'https://ddd.cc/rest/emailler/';
$oFtpConfig->setReportClass(AfrFtpReportBpg::class);


//CHECK FOR RESUME FTP OR RUN
if (is_file($oFtpConfig->sResumeDump) && filemtime($oFtpConfig->sResumeDump) < time() - 3600 * 24 * 7) {
    unlink($oFtpConfig->sResumeDump);
}
if (is_file($oFtpConfig->sResumeDump)) {
    echo 'RESUME PROCESS ' . date('H:i:s') . "\n\n\n\n\n";
    $AfrFtpBackupFacadeClass = new AfrFtpPutBigDataFacade($oFtpConfig);
    $AfrFtpBackupFacadeClass->makeBackup();
} else {
    //////// GOLDEN HOURS
    while (true) {
        $iHour = (int)date('H');
        if ($iStartHour <= $iHour && $iHour <= $iStopHour) {
            break;
        } else {
            echo '@' . date('Y-m-d H:i:s') . "  Waiting for the current hour to be inside the range: [$iStartHour:00 / $iStopHour:59]\n";
            sleep(10 * 60);
        }
    }

    //TODO CLEANUP on local
    //TODO cleanup ftp

    $oVersioning = AfrDirMaxFileMtimeClass::getInstance();
    foreach ($aPathsPairs as $aPairI) {
        if (
            !is_dir($aPairI[1]) ||
            $oVersioning->getDirMaxFileMtime($aPairI[0], 6) >
            $oVersioning->getDirMaxFileMtime($aPairI[1], 6)
        ) {
            $oLocalConfig->aFromToPaths = [$aPairI[0] => $aPairI[1]];
            $oFtpConfig->aFromToPaths = [$aPathsPairs[0][1] . '\!latest' => $aPathsPairs[0][2]];
        }
    }

    ///////////////// Effective copy process
    $oLogInline = new AfrFtpLogInline();
    $oDmsCopy = new AfrFtpNbrCopiesDmsX($oLocalConfig, $oLogInline);
    //$oDmsCopy->makeBackup();

    $oFtpConfig->aFromToPaths = ['H:\WindowsImageBackup_TO_NAS2' => '/bpg-backup/MG1/BPG-Offsite-Octomber21Test'];

    //TODO cleanup ftp
    ///////////////// Effective upload process
    $AfrFtpBackupFacadeClass = new AfrFtpPutBigDataFacade($oFtpConfig);
    $AfrFtpBackupFacadeClass->xetAfrFtpLog($oLogInline);
    $AfrFtpBackupFacadeClass->makeBackup();
}


echo 'END @ ' . date('H:i:s') . "\n\n\n\n\n";
