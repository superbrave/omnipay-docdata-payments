<?php

namespace Omnipay\DocdataPayments\Message;

/**
 * DocdataPayments Cancel Request
 */
class CancelRequest extends SoapAbstractRequest
{
    /**
     * Run the SOAP transaction
     *
     * @param \SoapClient $soapClient Configured SoapClient
     * @param array       $data       Formatted data to be sent to Docdata
     *
     * @return array
     *
     * @throws \SoapFault
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): array
    {
        $data['paymentOrderKey'] = $this->getTransactionReference();
        return $soapClient->__soapCall('cancel', [$data]);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseName(): string
    {
        return CancelResponse::class;
    }


}
