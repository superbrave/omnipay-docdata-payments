<?php

namespace Omnipay\DocdataPayments\Message;

/**
 * DocdataPayments Proceed Request, fakes a successful response because we still want the ProceedResponse,
 * but not the ProceedRequest to be fired.
 */
class FakeSuccessfulProceedRequest extends ProceedRequest
{
    /**
     * {@inheritdoc}
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): \stdClass
    {
        return $this->createSuccessfulProceedResponseForValidPaymentsButNothingToDo();
    }
}
