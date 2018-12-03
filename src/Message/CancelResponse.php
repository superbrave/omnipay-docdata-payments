<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Cancel Request Response
 */
class CancelResponse extends AbstractResponse
{
    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        if (isset($this->data->cancelSuccess) && $this->data->cancelSuccess->result === 'SUCCESS') {
            return true;
        }
        return false;
    }
}
