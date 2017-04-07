<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Capture Request Response
 */
class CaptureResponse extends AbstractResponse
{

    public function isSuccessful()
    {
        if(isset($this->data->captureSuccess) && $this->data->captureSuccess->success->code === 'SUCCESS') return true;
        return false;
    }
    
    public function getMessage()
    {
        if($this->isSuccessful()) return $this->data->captureSuccess->success->_;
        else return $this->data->captureErrors->error->_;
    }

    public function getTransactionReference()
    {
        return null;
    }
    
    public function getTransactionId()
    {
        return !empty($this->getRequest()->getTransactionId()) ? $this->getRequest()->getTransactionId() : null;
    }

}
