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
     * Get the payment id(s) to be captured
     *
     * @return string
     */
    public function getTransactionReference(): string
    {
        return $this->data->statusSuccess->report->payment->id;
    }

    /**
     * Is payment authorized successfully?
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        $statusSuccess = $this->data->statusSuccess;

        if (!isset($statusSuccess) || $statusSuccess !== 'SUCCESS') {
            return false;
        }

        $payment = $statusSuccess->report->payment;
        if (!isset($statusSuccess->report->payment)) {
            return false;
        }

        if (is_array($this->data->statusSuccess->report->payment)) {
            $payment = $payment[0];
        }

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

        $payment = $statusSuccess->report->payment;
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

        $payment = $this->data->statusSuccess->report->payment;

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
}
