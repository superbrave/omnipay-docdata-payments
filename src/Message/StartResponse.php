<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Cancel Request Response
 */
class StartResponse extends AbstractResponse
{
    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        if (isset($this->data->startSuccess) && $this->data->startSuccess->success->code === 'SUCCESS') {
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
        return $this->data->startSuccess->paymentResponse->paymentSuccess->id ?? null;
    }
}
