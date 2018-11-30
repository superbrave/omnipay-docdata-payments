<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Docdata Paymets SOAP gateway Abstract Request
 *
 * The merchant web service is accessible at the following URL:
 *
 * https://secure.docdatapayments.com/ps/services/paymentservice/1_3
 *
 * The WSDL description is accessible with the following URL:
 *
 * https://secure.docdatapayments.com/ps/services/paymentservice/1_3?wsdl
 *
 * SOAP requires a namespace for all operations. The namespace is as follows:
 *
 * http://www.docdatapayments.com/services/paymentservice/1_3/
 */
abstract class SoapAbstractRequest extends OmnipayAbstractRequest
{
    /** @var string Namespace for SOAP operations */
    protected $namespace = 'http://www.docdatapayments.com/services/paymentservice/1_3/';

    /**
     * Test Endpoint URL
     *
     * @var string URL
     */
    protected $testEndpoint = 'https://test.docdatapayments.com/ps/services/paymentservice/1_3?wsdl';

    /**
     * Live Endpoint URL
     *
     * @var string URL
     */
    protected $liveEndpoint = 'https://secure.docdatapayments.com/ps/services/paymentservice/1_3?wsdl';

    /**
     * @var \SoapClient
     */
    protected $soapClient;

    /**
     * @var string The name of the object that is expected in the SOAP response
     */
    public $responseName;

    /**
     * The generated SOAP request data, saved immediately before a transaction is run.
     *
     * @var array
     */
    protected $request;

    /**
     * The retrieved SOAP response, saved immediately after a transaction is run.
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * The amount of time in seconds to wait for both a connection and a response.
     *
     * Total potential wait time is this value times 2 (connection + response).
     *
     * @var float
     */
    public $timeout = 10;

    /**
     * Create a new Request
     *
     * @param ClientInterface $httpClient  A Guzzle client to make API calls with
     * @param HttpRequest     $httpRequest A Symfony HTTP request object
     * @param \SoapClient     $soapClient
     */
    public function __construct(
        ClientInterface $httpClient,
        HttpRequest $httpRequest,
        \SoapClient $soapClient = null
    ) {
        parent::__construct($httpClient, $httpRequest);
        $this->soapClient = $soapClient;
    }

    /**
     * Get merchant name
     *
     * Use the Merchant Name assigned by Docdata Payments
     *
     * @return string
     *
     * @throws InvalidRequestException
     */
    public function getMerchantName()
    {
        if (empty($this->getParameter('merchantName'))) {
            throw new InvalidRequestException('Merchant name must be set.');
        }
        return $this->getParameter('merchantName');
    }

    /**
     * Set merchant id
     *
     * Use the Merchant Name assigned by Docdata Payments
     *
     * @param string $value
     *
     * @return SoapAbstractRequest implements a fluent interface
     */
    public function setMerchantName($value)
    {
        return $this->setParameter('merchantName', $value);
    }

    /**
     * Get site id
     *
     * Use the Site ID assigned by Allied wallet.
     *
     * @return string
     *
     * @throws InvalidRequestException
     */
    public function getMerchantPassword()
    {
        if (empty($this->getParameter('merchantPassword'))) {
            throw new InvalidRequestException('Merchant password must be set.');
        }
        return $this->getParameter('merchantPassword');
    }

    /**
     * Set site id
     *
     * Use the Site ID assigned by Allied wallet.
     *
     * @param string $value
     *
     * @return SoapAbstractRequest implements a fluent interface
     */
    public function setMerchantPassword($value)
    {
        return $this->setParameter('merchantPassword', $value);
    }
    
    public function setPaymentProfile($value)
    {
        return $this->setParameter('paymentProfile', $value);
    }
    
    public function getPaymentProfile()
    {
        if (empty($this->getParameter('paymentProfile'))) {
            return "default";//standard
        }
        return $this->getParameter('paymentProfile');
    }
    
    public function setPaymentDays($value)
    {
        return $this->setParameter('paymentDays', $value);
    }
    
    public function getPaymentDays()
    {
        if (empty($this->getParameter('paymentDays'))) {
            return 7;
        }
        return $this->getParameter('paymentDays');
    }
    
    public function getLanguage()
    {
        if(empty($this->getParameter('language'))) return 'en';
        $language =  strtolower($this->getParameter('language'));
        if (!preg_match('/^[a-z]{2}$/', $language)) {
            throw new InvalidRequestException('Language must be an ISO 639-1:2002 Part 1: Alpha-2 Language Codes (lowercase) two-digit code.');
        }
        return $language;
    }
    
    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }
    
    public function getShopperId()
    {
        if (empty($this->getParameter('shopperId'))) {
            throw new InvalidRequestException('Shopper ID must be set.');
        }
        return $this->getParameter('shopperId');
    }
    
    public function setShopperId($value)
    {
        return $this->setParameter('shopperId', $value);
    }
    
    /**
     * Get the transaction ID.
     *
     * @return string
     */
    public function getTransactionId()
    {
        if (empty($this->getParameter('transactionId'))) {
            throw new InvalidRequestException('Transaction ID must be set.');
        }
        return $this->getParameter('transactionId');
    }
    
    /**
     * Get the request pending URL.
     *
     * @return string
     */
    public function getPendingUrl()
    {
        return $this->getParameter('pendingUrl');
    }
    
    /**
     * Sets the request return URL.
     *
     * @param string $value
     * @return AbstractRequest Provides a fluent interface
     */
    public function setPendingUrl($value)
    {
        return $this->setParameter('pendingUrl', $value);
    }

    /**
     * Build the request object
     *
     * @return array
     */
    public function getData()
    {
        $this->request = array();
        //this code is for version 1.3 so hard coded
        $this->request['version'] = "1.3";
        $this->request['merchant']['name'] = $this->getMerchantName();
        $this->request['merchant']['password'] = $this->getMerchantPassword();
        //integration info
        $this->request['integrationInfo']['webshopPlugin'] = 'omnipay-docdata-payments';
        $this->request['integrationInfo']['webshopPluginVersion'] = 'omnipay-docdata-payments';
        $this->request['integrationInfo']['programmingLanguage'] = 'php';
        $this->request['integrationInfo']['operatingSystem'] = PHP_OS;
        $this->request['integrationInfo']['operatingSystemVersion'] = mb_strimwidth(php_uname(),0,35);

        return $this->request;
    }

    /**
     * Build the SOAP Client and the internal request object
     *
     * @return \SoapClient
     *
     * @throws \Exception
     */
    public function buildSoapClient()
    {
        if ($this->soapClient !== null) {
            return $this->soapClient;
        }

        $context_options = array(
            'http' => array(
                'timeout' => $this->timeout,
            ),
        );

        $context = stream_context_create($context_options);

        // options we pass into the soap client
        // turn on HTTP compression
        // set the internal character encoding to avoid random conversions
        // throw SoapFault exceptions when there is an error
        $soap_options = array(
            'compression'           => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
            'encoding'              => 'utf-8',
            'exceptions'            => true,
            'connection_timeout'    => $this->timeout,
            'stream_context'        => $context,
        );

        // if we're in test mode, don't cache the wsdl
        if ($this->getTestMode()) {
            $soap_options['cache_wsdl'] = WSDL_CACHE_NONE;
        } else {
            $soap_options['cache_wsdl'] = WSDL_CACHE_BOTH;
        }

        $this->soapClient = new \SoapClient($this->getEndpoint(), $soap_options);

        return $this->soapClient;
    }

    /**
     * Run the SOAP transaction
     *
     * Over-ride this in sub classes.
     *
     * @param \SoapClient $soapClient
     * @param array $data
     *
     * @return array
     *
     * @throws \SoapFault
     */
    abstract protected function runTransaction(\SoapClient $soapClient, array $data);

    /**
     * Send Data to the Gateway
     *
     * @param array $data
     *
     * @return ResponseInterface
     *
     * @throws \Exception
     */
    public function sendData($data)
    {
        // Build the SOAP client
        $soapClient = $this->buildSoapClient();

        // Replace this line with the correct function.
        $response = $this->runTransaction($soapClient, $data);
        $class = $this->responseName;
        $this->response = new $class($this, $response);

        return $this->response;
    }

    /**
     * Get the SOAP endpoint
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }
}
