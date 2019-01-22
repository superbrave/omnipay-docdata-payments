<?php

namespace Omnipay\DocdataPayments\Message;

/**
 * DocdataPayments Proceed Request, to 'pay' without interaction from the user
 */
class ProceedRequest extends SoapAbstractRequest
{
    /**
     * Name of the authorizationResult for the PROCEED request.
     * E.g. iDealAuthorizationResult , belfiusAuthorizationResult.
     *
     * @see https://test.docdatapayments.com/ps/orderapi-1_3.wsdl #part 5. Proceed
     * @var string
     */
    protected $authorizationResultType;

    /**
     * The actual values needed to be sent with every supported payment method
     *
     * @var array
     */
    protected $authorizationResult = [];

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
        // We only have the paymentOrderKey / Transaction Reference, and not the paymentId.
        // Use a STATUS request to get the reference, to be used in the proceed request.
        $statusData = $data;
        $statusData['paymentOrderKey'] = $this->getTransactionReference();
        $status = $soapClient->__soapCall('status', [$statusData]);

        $payments = $status->statusSuccess->report->payment;

        if (\is_array($payments) === false) {
            $payments = [
                $payments
            ];
        }

        $lastProceedResult = null;
        foreach($payments as $payment) {
            // try to 'proceed' every payment that has a valid state.
            // states are, however, badly documented.
            if (in_array($payment->authorization->status,[
                'REDIRECTED_FOR_AUTHENTICATION',
                'AUTHORIZATION_REQUESTED',
                'RISK_CHECK_OK'
            ])) {
                $data['paymentId'] = $payment->id;

                if (!empty($this->getAuthorizationResultType())) {
                    $data[$this->getAuthorizationResultType()] = $this->getAuthorizationResult();
                }

                $lastProceedResult = $soapClient->__soapCall('proceed', [$data]);
            }
        }
        // even if we were to have multiple payments, the last one should be the most relevant
        // and should have the result returned
        // @todo return 'fake' success response if no proceed is required, or failed state otherwise
        return $lastProceedResult;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseName(): string
    {
        return ProceedResponse::class;
    }

    /**
     * @return string
     */
    public function getAuthorizationResultType(): string
    {
        return $this->authorizationResultType;
    }

    /**
     * @param string $authorizationResultType
     */
    public function setAuthorizationResultType(string $authorizationResultType)
    {
        $this->authorizationResultType = $authorizationResultType;
    }

    /**
     * @return string
     */
    public function getAuthorizationResult(): array
    {
        return $this->authorizationResult;
    }

    /**
     * @param string $authorizationResult
     */
    public function setAuthorizationResult(array $authorizationResult)
    {
        $this->authorizationResult = $authorizationResult;
    }
}
