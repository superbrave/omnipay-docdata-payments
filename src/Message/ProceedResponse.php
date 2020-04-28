<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Proceed Request Response
 */
class ProceedResponse extends AbstractResponse
{
    /**
     * @var string When the proceed request was successfully received by Docdata
     */
    const PROCEEDSUCCESS_CODE_SUCCESSFUL = 'SUCCESS';

    /**
     * @var string When the proceed request was authorized for payment
     */
    const PAYMENT_SUCCESS_STATUS_AUTHORIZED = 'AUTHORIZED';

    /**
     * @var string When the proceed request was cancelled
     */
    const PAYMENT_SUCCESS_STATUS_CANCELLED = 'CANCELED';

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        if (
            isset($this->data->proceedSuccess->success->code)
            && $this->data->proceedSuccess->success->code === self::PROCEEDSUCCESS_CODE_SUCCESSFUL
            && isset($this->data->proceedSuccess->paymentResponse->paymentSuccess->status)
            && $this->data->proceedSuccess->paymentResponse->paymentSuccess->status === self::PAYMENT_SUCCESS_STATUS_AUTHORIZED
        ) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isCancelled()
    {
        if (
            isset($this->data->proceedSuccess->success->code)
            && $this->data->proceedSuccess->success->code === self::PROCEEDSUCCESS_CODE_SUCCESSFUL
            && isset($this->data->proceedSuccess->paymentResponse->paymentSuccess->status)
            && $this->data->proceedSuccess->paymentResponse->paymentSuccess->status === self::PAYMENT_SUCCESS_STATUS_CANCELLED
        ) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionReference()
    {
        return $this->request->getTransactionReference();
    }
}
