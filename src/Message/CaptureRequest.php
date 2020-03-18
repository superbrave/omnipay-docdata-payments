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
        $this->modifyCaptureResponseToSuccessfulWhenAlreadyCaptured($captureResponse, $statusResponse);

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
     * Returns the first payment available for capturing. When all payments are captured, the last payment is returned.
     * This will result in an "already captured" error from the payment service provider.
     *
     * When no payments are available, null will be returned. This will result in an "invalid payment id" error from
     * the payment service provider.
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
     * Modifies a erroneous capture response to successful when the payments are already captured.
     *
     * This is required to facilitate the same workflow for both directly captured payment methods (like iDeal)
     * and delayed captured payment methods (like ELV).
     *
     * @param stdClass $captureResponse
     * @param stdClass $statusResponse
     */
    private function modifyCaptureResponseToSuccessfulWhenAlreadyCaptured(
        stdClass $captureResponse,
        stdClass $statusResponse
    ): void {
        if (isset($captureResponse->captureSuccess)) {
            return;
        }

        if (isset($statusResponse->statusSuccess) === false) {
            return;
        }

        $statusResponseApproximateTotals = $statusResponse->statusSuccess->report->approximateTotals;
        if ($statusResponseApproximateTotals->totalRegistered !== $statusResponseApproximateTotals->totalCaptured) {
            return;
        }

        $captureResponse->captureSuccess = new stdClass();
        $captureResponse->captureSuccess->success = new stdClass();
        $captureResponse->captureSuccess->success->code = 'SUCCESS';
        $captureResponse->captureSuccess->success->_ = $captureResponse->captureErrors->error->_;
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
