<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Create Request
 */
class CaptureRequest extends SoapAbstractRequest
{

   
    public function getData()
    {
        
    }
    /**
     * Run the SOAP transaction
     *
     * @param SoapClient $soapClient
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function runTransaction($soapClient, $data)
    {
        $this->responseName = '\Omnipay\DocdataPayments\Message\CaptureResponse';
        return $soapClient->capture($data);
    }
}
