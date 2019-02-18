<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Status Request Response
 */
class StatusResponse extends AbstractResponse
{
    /**
     * Get the request reference instead of the payment id.
     *
     * @return string
     */
    public function getTransactionReference(): string
    {
        /** @var AbstractRequest $this->>request */
        return $this->request->getTransactionReference();
    }

    /**
     * Is payment authorized successfully?
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        if (!isset($this->data->statusSuccess) || $this->data->statusSuccess->success->code !== 'SUCCESS') {
            return false;
        }

        $statusSuccess = $this->data->statusSuccess;

        if (!isset($statusSuccess->report->payment)) {
            return false;
        }
        $payment = $this->getMostRecentPayment();

        if ($payment->authorization->status !== 'AUTHORIZED') {
            return false;
        }

        if ($payment->paymentMethod === 'BANK_TRANSFER') {
            $approximateTotals = $statusSuccess->report->approximateTotals;

            $totalRegistered = $approximateTotals->totalRegistered;
            $totalCaptured = $approximateTotals->totalCaptured;

            return $totalRegistered === $totalCaptured;
        }

        return true;
    }
    
    /**
     * Is the response successful?
     *
     * @return boolean
     */
    public function isPending(): bool
    {
        $statusSuccess = $this->data->statusSuccess;
        if (!isset($statusSuccess->report->payment)) {
            return true;
        }

        $payment = $this->getMostRecentPayment();
        if (is_array($this->data->statusSuccess->report->payment)) {
            $payment = $payment[0];
        }

        $authorizationStatus = $payment->authorization->status;
        if ($authorizationStatus === 'CANCELED') {
            return false;
        }

        //TODO This is probably right, but different from the original implementation
        if ($authorizationStatus === 'AUTHORIZED') {
            if ($payment->paymentMethod === 'BANK_TRANSFER') {
                if ($this->isCaptured() === false) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
    
    /**
     * Is the transaction cancelled by the user?
     *
     * @return boolean
     */
    public function isCancelled(): bool
    {
        if (!isset($this->data->statusSuccess->report->payment)) {
            return false;
        }

        $payment = $this->getMostRecentPayment();

        if (\is_array($payment)) {
            $payment = $payment[0];
        }


        return $payment->authorization->status === 'CANCELED';
    }

    /**
     * Has the full amount been paid and caputred?
     *
     * @return bool
     */
    public function isCaptured(): bool
    {
        $approximateTotals = $this->data->statusSuccess->report->approximateTotals;

        $totalRegistered = $approximateTotals->totalRegistered;
        $totalCaptured = $approximateTotals->totalCaptured;

        return $totalRegistered === $totalCaptured;
    }

    /**
     * Docdata returns an array of payments when you do several attempts. It returns 1 object if there was 1 attempt.
     * Get the most recent payment, as all previous ones should be unsuccessful.
     * When there is a successful attempt the user is returned to payment service.
     * The only issue could be bank transfers. No clue how that is handled.
     *
     * @return \stdClass payment information
     */
    protected function getMostRecentPayment()
    {
        $oneOrSeveralPayments = $this->data->statusSuccess->report->payment;

        if (is_array($oneOrSeveralPayments)) {
            return end($oneOrSeveralPayments);
        }

        return $oneOrSeveralPayments;
    }
}
