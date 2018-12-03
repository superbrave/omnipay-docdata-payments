<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Create Request
 */
class CaptureRequest extends SoapAbstractRequest
{
    /**
     * Run the SOAP transaction
     *
     * @param \SoapClient $soapClient Configured SoapClient
     * @param array       $data       Formatted Data to be sent to Docdata
     *
     * @return array
     *
     * @throws \SoapFault
     * @throws InvalidRequestException
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): array
    {
        $statusData = $data;
        $statusData['paymentOrderKey'] = $this->getTransactionReference();
        $status = $soapClient->__soapCall('status', $statusData);
        $data['paymentId'] = null;

        $payment = $status->statusSuccess->report->payment;
        if (\is_array($payment)) {
            if (!isset($payment[0])) {
                throw new InvalidRequestException('No payment to capture.');
            }

            $payment = $payment[0];
        }

        $data['amount'] = [
            '_' => $payment->authorization->amount->_,
            'currency' => $payment->authorization->amount->currency
        ];

        $this->responseName = '\Omnipay\DocdataPayments\Message\CaptureResponse';
        return $soapClient->__soapCall('capture', $data);
    }
}
