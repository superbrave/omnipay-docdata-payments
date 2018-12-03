<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Status Request Response
 */
class ExtendedStatusResponse extends AbstractResponse
{
    
    //get the payment id(s) to be captured
    public function getTransactionReference()
    {
        return $this->data->statusSuccess->report->payment->id;
    }
    
    public function isSuccessful()
    {
        if (isset($this->data->statusSuccess) && $this->data->statusSuccess->success->code === 'SUCCESS') {
            if (!isset($this->data->statusSuccess->report->payment)) {
                return false;
            } elseif (is_array($this->data->statusSuccess->report->payment)) {
                foreach ($this->data->statusSuccess->report->payment as $payment) {
                    if ($payment->paymentMethod == 'BANK_TRANSFER' && $payment->authorization->status == 'AUTHORIZED') {
                        if ($this->data->statusSuccess->report->approximateTotals->totalRegistered == $this->data->statusSuccess->report->approximateTotals->totalCaptured) {
                            return true;
                        }
                        return false;
                    } elseif ($payment->paymentMethod != 'BANK_TRANSFER' && $payment->authorization->status == 'AUTHORIZED') {
                        return true;
                    }
                }
            } else {
                $payment = $this->data->statusSuccess->report->payment;
                if ($payment->paymentMethod == 'BANK_TRANSFER' && $payment->authorization->status == 'AUTHORIZED') {
                    if ($this->data->statusSuccess->report->approximateTotals->totalRegistered == $this->data->statusSuccess->report->approximateTotals->totalCaptured) {
                        return true;
                    }
                    return false;
                } elseif ($payment->paymentMethod != 'BANK_TRANSFER' && $payment->authorization->status == 'AUTHORIZED') {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Is the response successful?
     *
     * @return boolean
     */
    public function isPending()
    {
        if (!isset($this->data->statusSuccess->report->payment)) {
            return true;
        } elseif (is_array($this->data->statusSuccess->report->payment)) {
            foreach ($this->data->statusSuccess->report->payment as $payment) {
                if ($payment->authorization->status == 'CANCELED') {
                    continue;
                }
                if ($payment->paymentMethod == 'BANK_TRANSFER') {
                    if ($payment->authorization->status == 'AUTHORIZED') {
                        if ($this->data->statusSuccess->report->approximateTotals->totalRegistered == $this->data->statusSuccess->report->approximateTotals->totalCaptured) {
                            return false;
                        }
                        return true;
                    }
                }
            }
        } else {
            $payment = $this->data->statusSuccess->report->payment;
            if ($payment->authorization->status == 'CANCELED') {
                return false;
            }
            if ($payment->paymentMethod == 'BANK_TRANSFER') {
                if ($payment->authorization->status == 'AUTHORIZED') {
                    if ($this->data->statusSuccess->report->approximateTotals->totalRegistered == $this->data->statusSuccess->report->approximateTotals->totalCaptured) {
                        return false;
                    }
                    return true;
                }
            }
        }
    }
    
    /**
     * Is the transaction cancelled by the user?
     *
     * @return boolean
     */
    public function isCancelled()
    {
        $canceled = false;
        if (!isset($this->data->statusSuccess->report->payment)) {
            $canceled = false;
        } elseif (is_array($this->data->statusSuccess->report->payment)) {
            foreach ($this->data->statusSuccess->report->payment as $payment) {
                if ($payment->authorization->status == 'CANCELED') {
                    $canceled = true;
                } else {
                    $canceled = false;
                }
            }
        } else {
            $payment = $this->data->statusSuccess->report->payment;
            if ($payment->authorization->status == 'CANCELED') {
                $canceled = true;
            } else {
                $canceled = false;
            }
        }
        return $canceled;
    }
    
    public function isCaptured()
    {
        if ($this->data->statusSuccess->report->approximateTotals->totalRegistered == $this->data->statusSuccess->report->approximateTotals->totalCaptured) {
            return true;
        }
        return false;
    }
}
