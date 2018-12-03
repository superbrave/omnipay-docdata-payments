<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Capture Request Response
 */
class CaptureResponse extends AbstractResponse
{
    /**
     * Is capture successful?
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return isset($this->data->captureSuccess)
            && $this->data->captureSuccess->success->code === 'SUCCESS';
    }

    /**
     * Get the success or error message
     *
     * @return string
     */
    public function getMessage()
    {
        if ($this->isSuccessful()) {
            return $this->data->captureSuccess->success->_;
        }

        return $this->data->captureErrors->error->_;
    }

    /**
     * Get the transaction reference
     *
     * @return null
     */
    public function getTransactionReference()
    {
        return null;
    }

    /**
     * Get the transaction ID
     *
     * @return string|null
     *
     * @TODO I think this doesn't work
     */
    public function getTransactionId()
    {
        if (empty($this->getRequest()->getTransactionId())) {
            return null;
        }

        return $this->getRequest()->getTransactionId();
    }
}
