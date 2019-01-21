<?php

namespace Omnipay\DocdataPayments;

use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\DocdataPayments\Message\CancelRequest;
use Omnipay\DocdataPayments\Message\CaptureRequest;
use Omnipay\DocdataPayments\Message\CreateRequest;
use Omnipay\DocdataPayments\Message\RefundRequest;
use Omnipay\DocdataPayments\Message\SoapAbstractRequest;
use Omnipay\Common\AbstractGateway;
use Omnipay\DocdataPayments\Message\StartRequest;
use Omnipay\DocdataPayments\Message\StatusRequest;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Docdata gateway for Omnipay
 *
 * @package Omnipay\DocdataPayments
 *
 * @method RequestInterface completePurchase(array $options = array())
 * @method RequestInterface createCard(array $options = array())
 * @method RequestInterface updateCard(array $options = array())
 * @method RequestInterface deleteCard(array $options = array())
 */
class WebdirectGateway extends AbstractGateway
{
    /**
     * Configured client for communication with Docdata
     *
     * @var \SoapClient
     */
    protected $soapClient;

    /**
     * Create a new gateway instance
     *
     * @param ClientInterface $httpClient  A Guzzle client to make API calls with
     * @param HttpRequest     $httpRequest A Symfony HTTP request object
     * @param \SoapClient     $soapClient  Configured SoapClient
     */
    public function __construct(
        ClientInterface $httpClient = null,
        HttpRequest $httpRequest = null,
        \SoapClient $soapClient = null
    ) {
        parent::__construct($httpClient, $httpRequest);
        $this->soapClient = $soapClient;
    }

    /**
     * Create and initialize a request object
     *
     * This function is usually used to create objects of type
     * Omnipay\Common\Message\AbstractRequest (or a non-abstract subclass of it)
     * and initialise them with using existing parameters from this gateway.
     *
     * @param string $class      The request class name
     * @param array  $parameters Data to be sent to Docdata
     *
     * @see \Omnipay\Common\Message\AbstractRequest
     *
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    protected function createRequest($class, array $parameters)
    {
        /**
         * Recognise $obj as request
         *
         * @var SoapAbstractRequest $obj Request class
         */
        $obj = new $class($this->httpClient, $this->httpRequest, $this->soapClient);

        return $obj->initialize(array_replace($this->getParameters(), $parameters));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Docdata Payments Webdirect';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getDefaultParameters(): array
    {
        return array(
            'merchantName' => '',
            'merchantPassword' => '',
            'testMode' => false,
        );
    }

    /**
     * Get merchant id
     *
     * Use the Merchant ID assigned by Allied wallet.
     *
     * @return string
     */
    public function getMerchantName(): string
    {
        return $this->getParameter('merchantName');
    }

    /**
     * Set merchant id
     *
     * Use the Merchant ID as set up in backoffice.
     *
     * @param string $merchantName Name of merchant as set up in backoffice
     *
     * @return WebdirectGateway implements a fluent interface
     */
    public function setMerchantName(string $merchantName): WebdirectGateway
    {
        return $this->setParameter('merchantName', $merchantName);
    }

    /**
     * Get site id
     *
     * Use the Site ID assigned by Allied wallet.
     *
     * @return string
     */
    public function getMerchantPassword(): string
    {
        return $this->getParameter('merchantPassword');
    }

    /**
     * Set site id
     *
     * Use the Site ID assigned by Allied wallet.
     *
     * @param string $merchantPassword Merchant password as set up in backoffice
     *
     * @return WebdirectGateway implements a fluent interface
     */
    public function setMerchantPassword($merchantPassword): WebdirectGateway
    {
        return $this->setParameter('merchantPassword', $merchantPassword);
    }

    /**
     * Start a transaction
     *
     * @param array $parameters Data to be sent to Docdata
     *
     * @return RequestInterface
     */
    public function purchase(array $parameters = array()): RequestInterface
    {
        // TODO: Implement purchase() method.
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
     * Handle notification callback.
     *
     * @param array $parameters Data to be sent to Docdata
     *
     * @return RequestInterface
     */
    public function acceptNotification(array $parameters = array())
    {
        //TODO let's just use the parameters to check and figure out the status.
        return $this->createRequest(StatusRequest::class, $parameters);
    }

    /**
     * Create a capture request
     *
     * @param array $parameters Data to be sent to Docdata
     *
     * @return RequestInterface
     */
    public function capture(array $parameters = array())
    {
        return $this->createRequest(CaptureRequest::class, $parameters);
    }

    /**
     * Create a refund request
     *
     * @param array $parameters Data to be sent to Docdata
     *
     * @return RequestInterface
     */
    public function refund(array $parameters = array())
    {
        return $this->createRequest(RefundRequest::class, $parameters);
    }

    /**
     * Create a void request
     *
     * @param array $parameters Data to be sent to Docdata
     *
     * @return RequestInterface
     */
    public function void(array $parameters = array())
    {
        return $this->createRequest(CancelRequest::class, $parameters);
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
        return $this->createRequest(CaptureRequest::class, $parameters);
    }

    /**
     * Get the status of the transaction.
     *
     * @param array $options Data to be sent to Docdata
     *
     * @return RequestInterface
     */
    public function fetchTransaction(array $options = []): RequestInterface
    {
        return $this->createRequest(StatusRequest::class, $options);
    }
}
