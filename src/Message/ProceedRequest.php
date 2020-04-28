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
            if (isset($payment->authorization->capture)) {
                continue;
            }

            if (isset($payment->authorization->refund)) {
                continue;
            }

            if (isset($payment->authorization->chargeback)) {
                continue;
            }

            if (isset($payment->authorization->reversal)) {
                continue;
            }

            unset($data['paymentOrderKey']);

            $data['paymentId'] = $payment->id;
            $data[$this->getAuthorizationResultType()] = $this->getAuthorizationResult();

            $lastProceedResponse = $soapClient->__soapCall('proceed', [$data]);
        }

        $this->modifyProceedResponseToSuccessfulWhenPaymentAlreadyAcquirerApproved(
            $lastProceedResponse,
            $statusResponse
        );

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

    /**
     * Modifies the last proceed response to successful when the payments are already acquirer approved.
     *
     * @param stdClass $lastProceedResponse
     * @param stdClass $statusResponse
     */
    private function modifyProceedResponseToSuccessfulWhenPaymentAlreadyAcquirerApproved(
        stdClass $lastProceedResponse,
        stdClass $statusResponse
    ): void {
        if (isset($statusResponse->statusSuccess) === false) {
            return;
        }

        $statusResponseApproximateTotals = $statusResponse->statusSuccess->report->approximateTotals;
        if ($statusResponseApproximateTotals->totalRegistered !== $statusResponseApproximateTotals->totalAcquirerApproved) {
            return;
        }

        $lastProceedResponse->proceedSuccess = new stdClass();
        $lastProceedResponse->proceedSuccess->success = new stdClass();
        $lastProceedResponse->proceedSuccess->success->_ = 'All payments already acquirer approved.';
        $lastProceedResponse->proceedSuccess->success->code = 'SUCCESS';
        $lastProceedResponse->proceedSuccess->paymentResponse = new stdClass();
        $lastProceedResponse->proceedSuccess->paymentResponse->paymentSuccess = new stdClass();
        $lastProceedResponse->proceedSuccess->paymentResponse->paymentSuccess->status = 'AUTHORIZED';
    }
}
