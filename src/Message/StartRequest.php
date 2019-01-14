<?php

namespace Omnipay\DocdataPayments\Message;

/**
 * DocdataPayments Start Request, to 'pay' without interaction from the user
 */
class StartRequest extends SoapAbstractRequest
{
    /**
     * Run the SOAP transaction
     *
     * @param \SoapClient $soapClient Configured SoapClient
     * @param array       $data       Formatted data to be sent to Docdata
     *
     * @return \stdClass
     *
     * @throws \SoapFault
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): \stdClass
    {
        $data['paymentOrderKey'] = $this->getTransactionReference();
        return $soapClient->__soapCall('start', [$data]);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseName(): string
    {
        return StartResponse::class;
    }


}
