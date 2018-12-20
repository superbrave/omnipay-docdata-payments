<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Refund Request
 */
class RefundRequest extends SoapAbstractRequest
{
    /**
     * Get the formatted data to be sent to Docdata
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();

        $data['amount'] = [
            '_' => $this->getAmountInteger(),
            'currency' => $this->getCurrency()
        ];

        return $data;
    }
    /**
     * Run the SOAP transaction
     *
     * @param \SoapClient $soapClient Configured SoapClient
     * @param array       $data       Data array in Docdata format
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): array
    {
        $statusData = $data;
        $statusData['paymentOrderKey'] = $this->getTransactionReference();
        $status = $soapClient->__soapCall('status', [$statusData]);

        $payments = $status->statusSuccess->report->payment;
        if (!is_array($payments)) {
            // Convert to an array
            $payments = [
                $payments
            ];
        }

        foreach ($payments as $payment) {
            if ($payment->authorization->status === 'AUTHORIZED') {
                $paymentIdToRefund = $payment->id;
            }
        }

        if ($paymentIdToRefund === null) {
            throw new InvalidRequestException(
                sprintf(
                    'No authorized payments found for transaction #%s.',
                    $this->getTransactionReference()
                )
            );
        }

        $data['paymentId'] = $paymentIdToRefund;

        $this->responseName = RefundResponse::class;
        return $soapClient->__soapCall('refund', [$data]);
    }
}
