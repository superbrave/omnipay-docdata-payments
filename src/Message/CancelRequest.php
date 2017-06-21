<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Cancel Request
 */
class CancelRequest extends SoapAbstractRequest
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
        $data['paymentOrderKey'] = $this->getTransactionReference();
        $this->responseName = '\Omnipay\DocdataPayments\Message\CancelResponse';
        return $soapClient->cancel($data);
    }
}
