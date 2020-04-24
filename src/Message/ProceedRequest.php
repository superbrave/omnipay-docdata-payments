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
     * {@inheritdoc}
     */
    public function getData()
    {
        $this->validate('transactionReference');

        $data = parent::getData();
        $data['paymentOrderKey'] = $this->getTransactionReference();

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
        $statusResponse = $soapClient->__soapCall('status', [$data]);

        $payments = [];
        if (isset($statusResponse->statusSuccess->report->payment)) {
            $payments = $statusResponse->statusSuccess->report->payment;
        }

        if (is_array($payments) === false) {
            $payments = [$payments];
        }

        $lastProceedResult = null;
        $authorizedPayments = [];

        foreach ($payments as $payment) {
            if (isset($payment->authorization->reversal)) {
                continue;
            }

            // try to 'proceed' every payment that has a valid state.
            // states are, however, badly documented.
            switch($payment->authorization->status) {
                case 'REDIRECTED_FOR_AUTHORIZATION':
                case 'AUTHORIZATION_REQUESTED':
                case 'RISK_CHECK_OK':
                    unset($data['paymentOrderKey']);
                    $data['paymentId'] = $payment->id;

                    if (!empty($this->getAuthorizationResultType())) {
                        $data[$this->getAuthorizationResultType()] = $this->getAuthorizationResult();
                    }

                    // we can't return here because there might be multiple payments that need to proceed
                    $lastProceedResult = $soapClient->__soapCall('proceed', [$data]);
                    break;
                case 'AUTHORIZED':
                    if (
                        isset($payment->authorization->capture->status)
                        && $payment->authorization->capture->status === 'STARTED'
                    ) {
                        $authorizedPayments[] = $payment;
                    }
                    break;
            }
        }

        if ($lastProceedResult === null) {
            if (!empty($this->getAuthorizationResultType())) {
                // we should have proceeded but we can't
                return $this->createFakeProceedErrorResponseForNoValidPayments();
            }

            if (!empty($authorizedPayments)) {
                // bank transfer. Promise to pay, but no money yet.
                return $this->createSuccessfulProceedResponseForValidPaymentsButNothingToDo();
            }
        }
        // Even if we were to have multiple payments, the last one should be the most relevant
        // and should have the result returned.
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
     * @return string|null
     */
    public function getAuthorizationResultType()
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

    /**
     * @return bool
     */
    public function getSkipProceedRequest(): bool
    {
        return $this->skipProceedRequest;
    }

    /**
     * @param bool $skipProceedRequest
     */
    public function setSkipProceedRequest(bool $skipProceedRequest)
    {
        $this->skipProceedRequest = $skipProceedRequest;
    }

    /**
     * Create a stdClass that mimics a successful soap call to docdata.
     * This is used when there were no (authorized) payments to proceed.
     * This occurs when a user chooses bank transfer.
     *
     * @return \stdClass
     */
    protected function createSuccessfulProceedResponseForValidPaymentsButNothingToDo()
    {
        $response = new \stdClass();
        $response->proceedSuccess = new \stdClass();
        $response->proceedSuccess->success = new \stdClass();
        $response->proceedSuccess->success->_ = 'All payments were already processed so no action was taken.';
        $response->proceedSuccess->success->code = 'SUCCESS';
        $response->proceedSuccess->paymentResponse = new \stdClass();
        $response->proceedSuccess->paymentResponse->paymentSuccess = new \stdClass();
        $response->proceedSuccess->paymentResponse->paymentSuccess->status = 'AUTHORIZED';

        return $response;
    }

    /**
     * Create a stdClass that mimics a failed soap call to docdata, to be used instead of an exception
     *
     * @return \stdClass
     */
    private function createFakeProceedErrorResponseForNoValidPayments()
    {
        $response = new \stdClass();
        $response->proceedErrors = new \stdClass();
        $response->proceedErrors->error = new \stdClass();
        $response->proceedErrors->error->_ = 'No Proceed executed because there were no valid payments';

        return $response;
    }
}
