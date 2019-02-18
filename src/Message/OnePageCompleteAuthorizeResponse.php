<?php

namespace Omnipay\DocdataPayments\Message;

class OnePageCompleteAuthorizeResponse extends StatusResponse
{
    /**
     * {@inheritdoc}
     *
     * Has a different check for bank transfers after completeAuthorize call.
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
            $totalPending = $approximateTotals->totalShopperPending;

            return ($totalRegistered > 0 && $totalPending > 0) || ($totalRegistered == $totalCaptured);
        }

        return true;
    }
}