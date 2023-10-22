<?php
declare(strict_types=1);

namespace Unit;

use Autoframe\Components\FtpTransfer\AfrFtpBackupConfig;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\AfrFtpPutBigDataFacade;
use Autoframe\Components\FtpTransfer\Report\AfrFtpReportBpg;
use Autoframe\Components\FileSystem\Versioning\AfrDirMaxFileMtimeClass;
use Autoframe\Components\FtpTransfer\FtpBusinessLogic\AfrFtpNbrCopiesDms;
use Autoframe\Components\FtpTransfer\Log\AfrFtpLogInline;
use Autoframe\Process\Control\Lock\AfrLockFileClass;
use PHPUnit\Framework\TestCase;

class AfrFtpPutBigDataFacadeTest extends TestCase
{

	public static function insideProductionVendorDir(): bool
    {
        return strpos(__DIR__, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false;
    }

    protected function setUp(): void
    {
        $this->oFtpConfig = new AfrFtpBackupConfig();
    }
	
	protected function tearDown(): void
    {
        //cleanup between tests for static
    }

    /**
     * @test
     */
    public function AfrFtpPutBigDataFacadeTest(): void
    {
        $oAfrFtpPutBigDataFacade = new AfrFtpPutBigDataFacade($this->oFtpConfig);
        $this->assertSame($oAfrFtpPutBigDataFacade instanceof AfrFtpPutBigDataFacade, true);
    }


}