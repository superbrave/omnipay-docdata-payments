<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Refund Request
 */
class RefundRequest extends SoapAbstractRequest
{
    
    public function getData()
    {
        $data = parent::getData();
        $data['amount'] = array('_' => $this->getAmountInteger(),'currency' => $this->getCurrency());
        return $data;
    }
    /**
     * Run the SOAP transaction
     *
     * @param SoapClient $soapClient
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function runTransaction($soapClient, $data)
    { 
        $statusData = $data;
        $statusData['paymentOrderKey'] = $this->getTransactionReference();
        $status = $soapClient->status($statusData);
        $data['paymentId'] = null;
        $captured = false;
        if(is_array($status->statusSuccess->report->payment)) {
            foreach($status->statusSuccess->report->payment as $payment){
                if($payment->authorization->status == 'AUTHORIZED') $data['paymentId'] = $payment->id;
            }
        }
        elseif($status->statusSuccess->report->payment->authorization->status == 'AUTHORIZED'){
            $data['paymentId'] = $status->statusSuccess->report->payment->id;
        }
        if(is_null($data['paymentId'])) {
            throw new InvalidRequestException("No payment to refund.");
        }
        
        $this->responseName = '\Omnipay\DocdataPayments\Message\RefundResponse';
        return $soapClient->refund($data);
    }
}
