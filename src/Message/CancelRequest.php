<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Cancel Request
 */
class CancelRequest extends SoapAbstractRequest
{
    /**
     * Run the SOAP transaction
     *
     * @param \SoapClient $soapClient Configured SoapClient
     * @param array       $data       Formatted data to be sent to Docdata
     *
     * @return array
     *
     * @throws \SoapFault
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): array
    {
        $data['paymentOrderKey'] = $this->getTransactionReference();
        $this->responseName = '\Omnipay\DocdataPayments\Message\CancelResponse';
        return $soapClient->__soapCall('cancel', $data);
    }
}
