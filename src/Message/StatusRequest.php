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
class StatusRequest extends SoapAbstractRequest implements NotificationInterface
{
    /**
     * Get the data that will be sent in the request
     *
     * @return array
     */
    public function getData(): array
    {
        $data = parent::getData();
        $data['paymentOrderKey'] = $this->getTransactionReference();
        return $data;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): array
    {
        $this->responseName = StatusResponse::class;
        return $soapClient->__soapCall('status', [$data]);
    }
    
    
    /**
     * Was the transaction successful?
     *
     * @return string Transaction status, one of STATUS_COMPLETED, STATUS_PENDING
     * or STATUS_FAILED.
     *
     * @TODO this is supposed to return a status, not a boolean..
     */
    public function getTransactionStatus()
    {
        if (isset($this->data->statusSuccess)) {
            return true;
        }
        return self::STATUS_FAILED;
    }
    
    /**
     * Response Message
     *
     * @return string A response message from the payment gateway
     */
    public function getMessage()
    {
        //TODO is this OK?
    }
}
