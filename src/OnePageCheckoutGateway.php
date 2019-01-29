<?php

namespace Omnipay\DocdataPayments;

use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\DocdataPayments\Message\CreateRequest;
use Omnipay\DocdataPayments\Message\FakeSuccessfulProceedRequest;
use Omnipay\Common\AbstractGateway;

/**
 * Docdata gateway for Omnipay - one page checkout, not webdirect.
 */
class OnePageCheckoutGateway extends WebdirectGateway
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Docdata Payments One Page Checkout';
    }

    /**
     * Create an authorize request
     *
     * @param array $parameters Data to be sent to Docdata
     *
     * @return RequestInterface
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest(CreateRequest::class, $parameters);
    }

    /**
     * Create completeAuthorize request
     *
     * @param array $parameters Data to be sent to Docdata
     *
     * @return RequestInterface
     */
    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest(FakeSuccessfulProceedRequest::class, $parameters);
    }
}
