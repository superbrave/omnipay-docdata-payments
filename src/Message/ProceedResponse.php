<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Cancel Request Response
 */
class ProceedResponse extends AbstractResponse
{
    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        if (
            isset($this->data->proceedSuccess)
            && $this->data->proceedSuccess->success->code === 'SUCCESS'
            && isset($this->data->proceedSuccess->paymentResponse->paymentSuccess)
            && $this->data->proceedSuccess->paymentResponse->paymentSuccess->status === 'AUTHORIZED'
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get a reference provided by the gateway to represent the payment.
     * This is not the same as the transactionReference from the createRequest.
     *
     * @return null|string
     */
    public function getTransactionReference()
    {
        return $this->data->proceedSuccess->paymentResponse->paymentSuccess->id ?? null;
    }
}
