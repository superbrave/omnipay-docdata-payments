<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Refund Request Response
 */
class RefundResponse extends AbstractResponse
{
    /**
     * Has the refund been created successfully?
     *
     * @return bool Successful refund creation
     */
    public function isSuccessful(): bool
    {
        return isset($this->data->refundSuccess)
            && $this->data->refundSuccess->success->code === 'SUCCESS';
    }
}
