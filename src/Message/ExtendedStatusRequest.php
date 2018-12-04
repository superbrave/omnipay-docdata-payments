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
    /**
     * Get the data for the request
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        $data['paymentOrderKey'] = $this->getTransactionReference();
        return $data;
    }
    
    /**
     * Run the SOAP transaction
     *
     * @param \SoapClient $soapClient Configured SoapClient
     * @param array       $data       Formatted data to be sent to Docdata
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): array
    {
        $this->responseName = ExtendedStatusResponse::class;
        return $soapClient->__soapCall('statusExtended', $data);
    }
    
    
    /**
     * Was the transaction successful?
     *
     * @return string Transaction status, one of {STATUS_COMPLETED},
     * {STATUS_PENDING} or {STATUS_FAILED}.
     *
     * @TODO status should be one of STATUS_* constants
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
    }
}
