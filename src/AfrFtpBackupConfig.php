<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer;

use Autoframe\Components\FtpTransfer\FtpBusinessLogic\AfrFtpPutBigData;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\AfrFtpBusinessLogicInterface;
use Autoframe\Components\Exception\AfrException;
use Autoframe\Components\FtpTransfer\Report\AfrFtpReportInterface;

class AfrFtpBackupConfig
{

    protected string $sBusinessLogicClass = AfrFtpPutBigData::class;

    /**
     * @param string $BusinessLogicClass
     * @return void
     * @throws AfrException
     */
    public function setBusinessLogic(string $BusinessLogicClass): void
    {
        if (!in_array(AfrFtpBusinessLogicInterface::class, class_implements($BusinessLogicClass))) {
            throw new AfrException('Class must implement ' . AfrFtpBusinessLogicInterface::class);
        }
        $this->sBusinessLogicClass = $BusinessLogicClass;
    }

    /**
     * AfrFtpBusinessLogicInterface
     * @return string
     */
    public function getBusinessLogic(): string
    {
        return $this->sBusinessLogicClass;
    }

    /**
     * From local dir path is in key,
     * Ftp destination dir path is into value
     * = [ 'C:\xampp\htdocs\afr\src\FtpBackup' => '/bpg-backup/MG1/test2/resume']
     * @var array
     */
    public array $aFromToPaths;

    /**
     * Server ip or hostname
     * @var string
     */
    public string $ConServer;
    public string $ConUsername;
    public string $ConPassword;
    public int $ConPort = 21;
    public int $ConTimeout = 90;
    public bool $ConPassive = true;
    public int $iDirPermissions = 0775;

    public string $sTodayFolderName = 'today';
    public string $sLatestFolderName = '!latest';
    public string $sResumeDump = __DIR__ . DIRECTORY_SEPARATOR . 'self.resume.php';

    public string $sReportTarget = '';
    public string $sReportTo = '';
    public string $sReportToSecond = '';
    public string $sReportSubject = 'Ftp upload report';
    public string $sReportBody ;
    public $mReportMixedA = null;
    public $mReportMixedS = null;
    public $mReportMixedI = null;
    public int $iLogUploadProgressEveryXSeconds = 30;

    /**
     * @param string|null $sTodayFolderName
     */
    public function __construct(string $sTodayFolderName = null)
    {
        if ($sTodayFolderName === null) {
            $sTodayFolderName = date('Ymd');
        }
        $this->sTodayFolderName = $sTodayFolderName;
    }

    /**
     * @var string|null
     */
    protected ?string $sReportClass = null;

    /**
     * @param string $sReportClass
     * @return void
     * @throws AfrException
     */
    public function setReportClass(string $sReportClass): void
    {
        if (!in_array(AfrFtpReportInterface::class, class_implements($sReportClass))) {
            throw new AfrException('Class must implement ' . AfrFtpReportInterface::class);
        }
        $this->sReportClass = $sReportClass;
    }

    /**
     * AfrFtpReportInterface
     * @return string
     */
    public function getReportClass(): string
    {
        return (string)$this->sReportClass;
    }

}