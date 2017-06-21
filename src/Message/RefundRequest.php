<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Create Request
 */
class RefundRequest extends SoapAbstractRequest
{
    
    public function getData()
    {
        $data = parent::getData();
        
        return $data;
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
        $this->responseName = '\Omnipay\DocdataPayments\Message\RefundResponse';
        return $soapClient->refund($data);
    }
}
