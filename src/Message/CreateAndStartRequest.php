<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Create Request + Start Request
 */
class CreateAndStartRequest extends CreateRequest
{
    /**
     * Payment method specific information to be used when executing a START request.
     * E.g. 'issuerId' for iDeal.
     *
     * @var array
     */
    protected $paymentInput = [];

    /**
     * Name of the payment method input. E.g. misterCashPaymentInput, elvPaymentInput, iDealPaymentInput
     * These are defined in the docdata wsdl
     *
     * @var string
     */
    protected $paymentInputType;

    /**
     * Get data that is required to fire a START request
     *
     * @param string $transactionReference
     *
     * @return array
     */
    public function getStartData(string $transactionReference)
    {
        $data = parent::getData();
        $data['paymentOrderKey'] = $transactionReference;
        $data['returnUrl'] = $this->getReturnUrl();
        $data['payment']['paymentMethod']= $this->getPaymentMethod();

        if (!empty($this->getPaymentInputType())) {
            $data['payment'][$this->getPaymentInputType()] = $this->getPaymentInput();
        }

        return $data;
    }

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
        /** @var \stdClass $createResponse */
        $createResponse = parent::runTransaction($soapClient, $data);

        if (isset($createResponse->createSuccess)) {
            // this will not return the start Response, as a createResponse is expected

            $startData = $this->getStartData($createResponse->createSuccess->key ?? '');
            $startResponse = $soapClient->__soapCall('start', [$startData]);

            if (isset($startResponse->startSuccess) && isset($startResponse->redirect)) {
                $createResponse->redirect = $startResponse->redirect;
            }
        }

        return $createResponse;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseName(): string
    {
        return CreateResponse::class;
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

    /**
     * @return string
     */
    public function getHouseNumberAddition(): string
    {
        return $this->houseNumberAddition;
    }

    /**
     * @param string|null $houseNumberAddition
     */
    public function setHouseNumberAddition($houseNumberAddition)
    {
        if ($houseNumberAddition === null) {
            $this->houseNumberAddition = '';
            return;
        }
        $this->houseNumberAddition = $houseNumberAddition;
    }
}
