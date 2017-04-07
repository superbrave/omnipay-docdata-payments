<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Create Request
 */
class CaptureRequest extends SoapAbstractRequest
{

   
    public function getData()
    {
        $data = parent::getData();
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
        if(is_array($status->statusSuccess->report->payment)) {
            foreach($status->statusSuccess->report->payment as $payment){
                
            }
        }
        $data['paymentId'] = $status->statusSuccess->report->payment->id;
        $data['amount'] = array('_' => (string)$status->statusSuccess->report->payment->authorization->amount->_,'currency' => (string)$status->statusSuccess->report->payment->authorization->amount->currency);
        $this->responseName = '\Omnipay\DocdataPayments\Message\CaptureResponse';
        return $soapClient->capture($data);
    }
}
