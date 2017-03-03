<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Status Request Response
 */
class StatusResponse extends AbstractResponse implements RedirectResponseInterface
{
    protected $livePaymentMenu = 'https://secure.docdatapayments.com/ps/menu';
    protected $testPaymentMenu = 'https://test.docdatapayments.com/ps/menu';

    public function isSuccessful()
    {
        if(isset($this->data->createSuccess) && $this->data->createSuccess->success->code === 'SUCCESS') return true;
        return false;
    }
    
    public function getMessage()
    {
        if($this->isSuccessful()) return $this->data->createSuccess->success->_;
        else return $this->data->createErrors->error->_;
    }

    public function isRedirect()
    {
        return $this->isSuccessful();
    }

    public function getRedirectUrl()
    {
        return $this->getPaymentMenu().'?'.http_build_query($this->getRedirectQueryParameters(), '', '&');
    }

    public function getTransactionReference()
    {
        return isset($this->data->createSuccess->key) ? $this->data->createSuccess->key : null;
    }
    
    public function getTransactionId()
    {
        return !empty($this->getRequest()->getTransactionId()) ? $this->getRequest()->getTransactionId() : null;
    }

    public function getRedirectMethod()
    {
        return 'GET';
    }

    public function getRedirectData()
    {
        return null;
    }

    /**
     * 1 payment_cluster_key yes This is the value that is returned by the create paymentOrder call as described in Prerequisites.
     * 2 merchant_name Yes This is your merchant name as used in all Docdata Payments communication.
     * 3 return_url_success No The URL that your client will be redirected to after a successful payment.
     * 4 return_url_canceled No The URL that your client will be redirected to after a payment has been canceled.
     * 5 return_url_pending No The URL that your client will be redirected to after the payment menu was accessed for a pending payment.
     * 6 return_url_error No The URL that your client will be redirected to after an error has occurred.
     * 7 client_language No The 2-letter ISO language code, will diplay the menu in the language you have specified.
     * 
     * @author Burak USGURLU <burak@uskur.com.tr>
     * @return string[]|NULL[]
     */
    protected function getRedirectQueryParameters()
    {
        return array(
            'payment_cluster_key' => $this->getTransactionReference(),
            'merchant_name' => $this->getRequest()->getMerchantName(),
            'return_url_success' =>$this->getRequest()->getReturnUrl(),
            'return_url_canceled'=>$this->getRequest()->getCancelUrl(),
            'return_url_pending'=>$this->getRequest()->getPendingUrl(),
            'return_url_error'=>$this->getRequest()->getCancelUrl(),
            'client_language'=>$this->getRequest()->getLanguage(),
        );
    }

    protected function getPaymentMenu()
    {
        return $this->getRequest()->getTestMode() ? $this->testPaymentMenu : $this->livePaymentMenu;
    }
}
