<?php
declare(strict_types=1);

namespace Autoframe\Components\FtpTransfer\Connection;

class AfrFtpConnectionClass implements AfrFtpConnectionInterface
{
    protected string $sError;
    protected string $sServer;
    protected string $sUserName;
    protected string $sPass;
    protected bool $bPassive;
    protected int $iPort;
    protected int $iTimeout;
    protected int $iDirPermissions;
    protected $mConn = null;
    protected $mLogin = null;

    public function __construct(
        string $sServer,
        string $sUserName,
        string $sPass,
        bool   $bPassive = false,
        int    $iPort = 21,
        int    $iTimeout = 90,
        int    $iDirPermissions = 0775
    )
    {
        $this->sServer = $sServer;
        $this->sUserName = $sUserName;
        $this->sPass = $sPass;
        $this->bPassive = $bPassive;
        $this->iPort = $iPort;
        $this->iTimeout = $iTimeout;
        $this->iDirPermissions = $iDirPermissions;
        $this->sError = '';
    }

    /**
     * @return false|mixed|null
     */
    public function connect()
    {
    //    $this->disconnect();
        if($this->mConn){
            return $this->mConn;
        }
        if ($this->getLoginResult()) {
            if ($this->bPassive) {
                if (!ftp_pasv($this->mConn, true)) {
                    $this->disconnect();
                    $this->sError = 'Unable to set to passive ftp connection ' . $this->sServer . ':' . $this->iPort;
                    $this->mConn = false;
                }
            }
        }
        return $this->mConn;
    }

    /**
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->mConn) {
            ftp_close($this->mConn);
        }
        $this->mConn = $this->mLogin = null;
        $this->sError = 'Ftp disconnect triggered using method';
    }

    /**
     * @param int $iTimeoutMs
     * @return false|mixed|null
     */
    public function reconnect(int $iTimeoutMs = 10)
    {
        $this->disconnect();
        if($iTimeoutMs<1000){
            usleep(1000 * $iTimeoutMs); //wait xx ms
        }
        else{
            sleep((int)ceil($iTimeoutMs/1000));
        }
        return $this->connect();
    }

    /**
     * @return false|resource
     */
    public function getConnection()
    {
        if ($this->mConn) {
            return $this->mConn;
        }
        $r = $this->connect();
        return $r ?: false;
    }

    /**
     * @return false|resource
     */
    protected function setConnection()
    {
        if ($this->mConn === null) {
            $this->mConn = ftp_connect($this->sServer, $this->iPort, $this->iTimeout);
            if (!$this->mConn) {
                $this->sError = 'Unable to connect to ' . $this->sServer . ':' . $this->iPort;
            }
        }
        return $this->mConn;
    }


    /**
     * @return bool
     */
    public function getLoginResult(): bool
    {
        if ($this->mLogin === null) {
            $this->sError = '';
            $conn = $this->setConnection();
            $this->mLogin = $conn && ftp_login($conn, $this->sUserName, $this->sPass);
            if (!$this->mLogin) {
                $this->sError = 'Unable to login to ' . $this->sServer . ':' . $this->iPort;
            }
        }
        return $this->mLogin;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->sError;
    }


    public function __destruct()
    {
        $this->disconnect();
    }

    public function getDirPerms(): int
    {
        return $this->iDirPermissions;
    }
}