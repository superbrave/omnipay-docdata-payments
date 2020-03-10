<?php

namespace Omnipay\DocdataPayments\Message;

use SoapClient;
use stdClass;

/**
 * DocdataPayments Create Request
 */
class CaptureRequest extends SoapAbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $this->validate('transactionReference');

        $data = parent::getData();
        $data['paymentOrderKey'] = $this->getTransactionReference();

        return $data;
    }

    /**
     * Run the SOAP transaction
     *
     * @param SoapClient $soapClient Configured SoapClient
     * @param array      $data       Formatted Data to be sent to Docdata
     *
     * @return stdClass
     */
    protected function runTransaction(SoapClient $soapClient, array $data): stdClass
    {
        $statusResponse = $soapClient->__soapCall('status', [$data]);

        $payments = [];
        if (isset($statusResponse->statusSuccess->report->payment)) {
            $payments = $statusResponse->statusSuccess->report->payment;
        }

        if (is_array($payments) === false) {
            $payments = [$payments];
        }

        $capturePayment = $this->getPaymentAvailableForCapture($payments);

        $data['paymentId'] = 0;
        if ($capturePayment instanceof stdClass) {
            $data['paymentId'] = $capturePayment->id;
        }

        unset($data['paymentOrderKey']);

        $captureResponse = $soapClient->__soapCall('capture', [$data]);

        return $this->mergeResponses($statusResponse, $captureResponse);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseName(): string
    {
        return CaptureResponse::class;
    }

    /**
     * Check all payments to see if we have an authorized one
     *
     * @param stdClass[] $payments
     *
     * @return stdClass|null
     */
    private function getPaymentAvailableForCapture(array $payments): ?stdClass
    {
        $payment = null;

        foreach ($payments as $payment) {
            if ($this->isPaymentAuthorized($payment) && $this->isPaymentCaptured($payment) === false) {
                return $payment;
            }
        }

        return $payment;
    }

    /**
     * Returns true if the payment status is 'AUTHORIZED'.
     *
     * @param stdClass $payment
     *
     * @return bool
     */
    private function isPaymentAuthorized(stdClass $payment): bool
    {
        return $payment->authorization->status === 'AUTHORIZED';
    }

    /**
     * Returns true if the payment has one or more captures.
     *
     * @param stdClass $payment
     *
     * @return bool
     */
    private function isPaymentCaptured(stdClass $payment): bool
    {
        return isset($payment->authorization->capture);
    }

    /**
     * Returns an stdClass with the multiple responses.
     *
     * @param stdClass ...$responses
     *
     * @return stdClass
     */
    private function mergeResponses(stdClass ...$responses): stdClass
    {
        $mergedResponse = new stdClass();
        foreach ($responses as $response) {
            $properties = get_object_vars($response);
            foreach ($properties as $propertyKey => $propertyValue) {
                $mergedResponse->{$propertyKey} = $propertyValue;
            }
        }

        return $mergedResponse;
    }
}
