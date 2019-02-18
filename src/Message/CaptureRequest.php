<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;

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
     * @return \stdClass
     *
     * @throws \SoapFault
     * @throws InvalidRequestException
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): \stdClass
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

        $authorizedPayments = $this->getAllAuthorizedPayments($payments);
        if (empty($authorizedPayments)) {
            return $this->createFakeCaptureResponseForNoValidPayments();
        }

        $capturablePayment = $this->getFirstPaymentToCapture($authorizedPayments);
        if ($capturablePayment === null) {
            return $this->createSuccessfulCaptureResponseForAllCapturesAlreadyDone();
        }

        $data['paymentId'] = $capturablePayment->id;

        return $soapClient->__soapCall('capture', [$data]);
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
     * @param array $payments
     *
     * @return array
     */
    protected function getAllAuthorizedPayments(array $payments): array
    {
        $authorizedPayments = [];

        foreach ($payments as $payment) {
            if ($payment->authorization->status === 'AUTHORIZED') {
                $authorizedPayments[] = $payment;
            }
        }
        return $authorizedPayments;
    }

    /**
     * Check all payments to see if there is a capture that's open to capture
     *
     * @param array $payments
     *
     * @return array|null
     */
    protected function getFirstPaymentToCapture(array $payments)
    {
        // start at the back as it's very likely the successful one is the latest.
        $payments = array_reverse($payments);

        foreach ($payments as $payment) {
            foreach ($payment->authorization->capture as $capture) {
                if (is_string($capture) && !$this->isUncapturableState($capture)) {
                    return $payment;
                }
                if (is_array($capture)) {
                    foreach ($capture as $row) {
                        if (isset($row->status) && !$this->isUncapturableState($row->status)) {
                            return $payment;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * States for captures are not defined in the docdata documentation, although some are in the wsdl,
     * so make educated guesses from states that are documented and expand upon them.
     *
     * We need to know which payments to skip when trying to capture.
     *
     * @param string $state
     * @return bool
     */
    private function isUncapturableState(string $state): bool
    {
        if (in_array(strtoupper($state), [
            'PAID',
            'CAPTURED',
            'COMPLETE',
            'COMPLETED',
        ])) {
            return true;
        }
        return false;
    }

    /**
     * Create a stdClass that mimics a failed soap call to docdata, to be used instead of an exception
     *
     * @return \stdClass
     */
    private function createFakeCaptureResponseForNoValidPayments()
    {
        $response = new \stdClass();
        $response->captureErrors = new \stdClass();
        $response->captureErrors->error = new \stdClass();
        $response->captureErrors->error->_ = 'No capture executed because there were no valid payments';

        return $response;
    }

    /**
     * Create a stdClass that mimics a successful soap call to docdata.
     * This is used when there were no (authorized) payments to capture.
     * This occurs when a capture gets called on iDeal for instance.
     *
     * @return \stdClass
     */
    private function createSuccessfulCaptureResponseForAllCapturesAlreadyDone()
    {
        $response = new \stdClass();
        $response->captureSuccess = new \stdClass();
        $response->captureSuccess->success = new \stdClass();
        $response->captureSuccess->success->_ = 'All captures were already captured so no action was taken.';
        $response->captureSuccess->success->code = 'SUCCESS';

        return $response;
    }
}
