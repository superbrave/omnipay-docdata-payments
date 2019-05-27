<?php

namespace Omnipay\DocdataPayments\Message;

class OnePageCompleteAuthorizeResponse extends StatusResponse
{
    const STATUSSUCCESS_CODE_SUCCESSFUL = 'SUCCESS';

    /**
     * {@inheritdoc}
     *
     * Has a different check for bank transfers after completeAuthorize call.
     */
    public function isSuccessful(): bool
    {
        if (!$this->hasSuccessfulStatusCode()) {
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

    /**
     * {@inheritdoc}
     */
    public function isCancelled(): bool
    {
        if (!$this->hasSuccessfulStatusCode()) {
            return false;
        }

        if ($this->isSuccessful()) {
            return false;
        }

        if (!isset($this->data->statusSuccess->report->approximateTotals)) {
            return false;
        }

        $approximateTotals = $this->data->statusSuccess->report->approximateTotals;

        if (
            $approximateTotals->totalRegistered > 0
            && $approximateTotals->totalShopperPending === 0
            && $approximateTotals->totalAcquirerPending === 0
            && $approximateTotals->totalAcquirerApproved === 0
            && $approximateTotals->totalCaptured === 0
        ) {
            return true;
        }
        return false;

    }

    /**
     * Check if the request has been successfully received by docdata.
     *
     * @return bool
     */
    private function hasSuccessfulStatusCode()
    {
        if (
            isset($this->data->statusSuccess)
            && $this->data->statusSuccess->success->code === self::STATUSSUCCESS_CODE_SUCCESSFUL
        ) {
            return true;
        }

        return false;
    }

}