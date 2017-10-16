<?php

namespace Omnipay\DocdataPayments\Message;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\DocdataPayments\Message\SoapAbstractRequest;
use Omnipay\Common\Message\AbstractRequest;
/**
 * The url of the notification must be set at the back office.
 * returns as a get request
 * /?orderId=
 */
class ExtendedStatusRequest extends SoapAbstractRequest implements NotificationInterface
{
    
    public function getData()
    {
        $data = parent::getData();
        $data['paymentOrderKey'] = $this->getTransactionReference();
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
        $this->responseName = '\Omnipay\DocdataPayments\Message\ExtendedStatusResponse';
        return $soapClient->statusExtended($data);
    }
    
    
    /**
     * Was the transaction successful?
     *
     * @return string Transaction status, one of {@see STATUS_COMPLETED}, {@see #STATUS_PENDING},
     * or {@see #STATUS_FAILED}.
     */
    public function getTransactionStatus(){
        if(isset($this->data->statusSuccess)) {
            return true;
        }
        return STATUS_FAILED;
    }
    
    /**
     * Response Message
     *
     * @return string A response message from the payment gateway
     */
    public function getMessage(){
        
    }
}