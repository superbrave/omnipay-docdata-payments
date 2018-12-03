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
        if (!isset($this->data->statusSuccess)) {
            return false;
        }

        $statusSuccess = $this->data->statusSuccess;
        if ($statusSuccess->success->code !== 'SUCCESS') {
            return false;
        }

        if (!isset($statusSuccess->report->payment)) {
            return false;
        }

        $payment = $statusSuccess->report->payment;

        if (\is_array($this->data->statusSuccess->report->payment)) {
            $payment = $payment[0];
        }

        if ($payment->authorization->status !== 'AUTHORIZED') {
            return false;
        }

        if ($payment->paymentMethod === 'BANK_TRANSFER') {
            $totalRegistered = $statusSuccess->report->approximateTotals
                ->totalRegistered;
            $totalCaptured = $statusSuccess->report->approximateTotals
                ->totalCaptured;

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
        if (!isset($this->data->statusSuccess->report->payment)) {
            return true;
        }

        $payment = $this->data->statusSuccess->report->payment;
        if (is_array($payment)) {
            $payment = $payment[0];
        }

        if ($payment->authorization->status === 'CANCELED') {
            return false;
        }

        if ($payment->paymentMethod === 'BANK_TRANSFER') {
            if ($payment->authorization->status !== 'AUTHORIZED') {
                return true;
            }

            $totalRegistered = $this->data->statusSuccess->report->approximateTotals
                ->totalRegistered;
            $totalCaptured = $this->data->statusSuccess->report->approximateTotals
                ->totalCaptured;
            return $totalRegistered !== $totalCaptured;
        }

        return false;
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
        $totalRegistered = $this->data->statusSuccess->report->approximateTotals
            ->totalRegistered;
        $totalCaptured = $this->data->statusSuccess->report->approximateTotals
            ->totalCaptured;

        return $totalRegistered === $totalCaptured;
    }
}
