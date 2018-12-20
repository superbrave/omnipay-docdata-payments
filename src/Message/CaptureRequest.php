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
        $status = $soapClient->__soapCall('status', [$statusData]);

        $payments = $status->statusSuccess->report->payment;

        if (\is_array($payments) === false) {
            $payments = [
                $payments
            ];
        }

        $authorizedPayment = null;
        foreach ($payments as $payment) {
            if ($payment->authorization->status === 'AUTHORIZED') {
                $authorizedPayment = $payment;
                break;
            }
        }

        if ($authorizedPayment === null) {
            throw new InvalidRequestException('No payment to capture.');
        }

        $data['paymentId'] = $authorizedPayment->id;

        $this->responseName = CaptureResponse::class;
        return $soapClient->__soapCall('capture', [$data]);
    }
}
