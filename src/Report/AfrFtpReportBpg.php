<?php

namespace Autoframe\Components\FtpTransfer\Report;

use Autoframe\Components\Exception\AfrException;
use Autoframe\Components\FtpTransfer\AfrFtpBackupConfig;

class AfrFtpReportBpg implements AfrFtpReportInterface
{
    /**
     * @param AfrFtpBackupConfig $oFtpConfig
     * @return array
     * @throws AfrException
     */

    public function ftpReport(AfrFtpBackupConfig $oFtpConfig): array
    {
        $aPostData = [
            'to' => $oFtpConfig->sReportTo,
            'cc' => $oFtpConfig->sReportToSecond,
            'subject' => $oFtpConfig->sReportSubject,
            'msg' => $oFtpConfig->sReportBody,
        ];
        if (is_array($oFtpConfig->mReportMixedA)) {
            $aPostData = array_merge($aPostData, $oFtpConfig->mReportMixedA);
        }
        $aOptions = [
            'http' => [
                'method' => 'POST',
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
                'timeout' => 20,
            ]];


        $aOptions['http']['content'] = count($aPostData)?http_build_query($aPostData):'';


        if ($oFtpConfig->mReportMixedS) {
            $aOptions['http']['header'] = $oFtpConfig->mReportMixedS;
        }
        else{
            $aOptions['http']['header'] =
                'Content-Type: application/x-www-form-urlencoded' . "\r\n" .
                'Content-Length: ' . strlen($aOptions['http']['content']) . "\r\n" .
                'User-Agent: ' . PHP_VERSION;
        }


        $sUrl = $oFtpConfig->sReportTarget;
        $ctx = stream_context_create($aOptions);
        $fp = fopen($sUrl, 'rb', false, $ctx);
        //print_r($aOptions);   print_r($ctx);  print_r($fp);

        if (!$fp) {
            throw new AfrException('Unable to open stream to: ' . $sUrl);
        }
        $response = stream_get_contents($fp);
        if ($response === false) {
            throw new AfrException('Unable to get response stream from: ' . $sUrl);
        }
        //print_r($http_response_header);  print_r($response);

        return ['header' => $http_response_header, 'content' => $response];
    }

}