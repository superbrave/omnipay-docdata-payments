<?php

namespace Omnipay\DocdataPayments\Message;

use SoapClient;
use stdClass;

/**
 * DocdataPayments Proceed Request, to 'pay' without interaction from the user
 */
class ProceedRequest extends SoapAbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $this->validate('transactionReference', 'authorizationResultType');

        $data = parent::getData();
        $data['paymentOrderKey'] = $this->getTransactionReference();

        return $data;
    }

    /**
     * Run the SOAP transaction
     *
     * @param SoapClient $soapClient Configured SoapClient
     * @param array      $data       Formatted data to be sent to Docdata
     *
     * @return stdClass
     */
    protected function runTransaction(SoapClient $soapClient, array $data): stdClass
    {
        $statusResponse = $soapClient->__soapCall('status', [$data]);

        $payments = [];
        if (isset($statusResponse->statusSuccess->report->payment)) {
            $payments = $statusResponse->statusSuccess->report->payment;
        }

        if (is_array($payments) === false) {
            $payments = [$payments];
        }

        $lastProceedResponse = new stdClass();
        foreach ($payments as $payment) {
            // try to 'proceed' every payment that has a valid state.
            // states are, however, badly documented.
            switch($payment->authorization->status) {
                case 'REDIRECTED_FOR_AUTHORIZATION':
                case 'AUTHORIZATION_REQUESTED':
                case 'RISK_CHECK_OK':
                    unset($data['paymentOrderKey']);

                    $data['paymentId'] = $payment->id;
                    $data[$this->getAuthorizationResultType()] = $this->getAuthorizationResult();

                    // we can't return here because there might be multiple payments that need to proceed
                    $lastProceedResponse = $soapClient->__soapCall('proceed', [$data]);
                    break;
            }
        }

        // Even if we were to have multiple payments, the last one should be the most relevant
        // and should have the result returned.
        return $this->mergeResponses($statusResponse, $lastProceedResponse);
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
     * Returns the name of the authorizationResult for the proceed request.
     *
     * @return string
     */
    public function getAuthorizationResultType()
    {
        return $this->getParameter('authorizationResultType');
    }

    /**
     * Sets the name of the authorization result for the proceed request.
     * (eg. iDealAuthorizationResult, belfiusAuthorizationResult)
     *
     * @see https://test.docdatapayments.com/ps/orderapi-1_3.wsdl #part 5. Proceed
     *
     * @param string $authorizationResultType
     *
     * @return $this
     */
    public function setAuthorizationResultType(string $authorizationResultType)
    {
        return $this->setParameter('authorizationResultType', $authorizationResultType);
    }

    /**
     * Returns the authorization result for the proceed request.
     *
     * @return array
     */
    public function getAuthorizationResult(): array
    {
        $authorizationResult = $this->getParameter('authorizationResult');
        if ($authorizationResult === null) {
            $authorizationResult = [];
        }

        return $authorizationResult;
    }

    /**
     * Sets the authorization result for the proceed request.
     *
     * @see https://test.docdatapayments.com/ps/orderapi-1_3.wsdl #part 5. Proceed
     *
     * @param array $authorizationResult
     *
     * @return $this
     */
    public function setAuthorizationResult(array $authorizationResult)
    {
        return $this->setParameter('authorizationResult', $authorizationResult);
    }
}
