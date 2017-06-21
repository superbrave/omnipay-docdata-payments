<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Refund Request Response
 */
class RefundResponse extends AbstractResponse
{
    
    public function isSuccessful()
    {
        if(isset($this->data->refundSuccess) && $this->data->refundSuccess->success->code === 'SUCCESS'){
            return true;
        }
        return false;
    }

}
