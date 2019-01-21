<?php

namespace Omnipay\DocdataPayments\Message;

/**
 * DocdataPayments Start Request, to 'pay' without interaction from the user
 */
class StartRequest extends SoapAbstractRequest
{
    /**
     * Payment method specific information to be used when executing a START request.
     * E.g. 'issuerId' for iDeal.
     *
     * @var array
     */
    protected $paymentInput;

    /**
     * Name of the payment method input. E.g. misterCashPaymentInput, elvPaymentInput, iDealPaymentInput
     * These are defined in the docdata wsdl
     *
     * @var string
     */
    protected $paymentInputType;

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
        $data['payment'] = [];
        $data['payment']['paymentMethod']= $this->getPaymentMethod();

        if (!empty($this->getPaymentInputType())) {
            $data['payment'][$this->getPaymentInputType()] = $this->getPaymentInput();
        }

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

    /**
     * @return array
     */
    public function getPaymentInput(): array
    {
        return $this->paymentInput;
    }

    /**
     * @param array $paymentInput
     */
    public function setPaymentInput(array $paymentInput)
    {
        $this->paymentInput = $paymentInput;
    }

    /**
     * @return string
     */
    public function getPaymentInputType(): string
    {
        return $this->paymentInputType;
    }

    /**
     * @param string $paymentInputType
     */
    public function setPaymentInputType(string $paymentInputType)
    {
        $this->paymentInputType = $paymentInputType;
    }
}
