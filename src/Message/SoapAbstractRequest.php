<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;
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
     * @param ClientInterface  $httpClient  A Guzzle client to make API calls with
     * @param HttpRequest      $httpRequest A Symfony HTTP request object
     * @param \SoapClient|null $soapClient  Configured SoapClient; If null, a new
     *                                      one will be created with default values
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
     * @param string $merchantName Merchant name as configured in Docdata backoffice
     *
     * @return SoapAbstractRequest implements a fluent interface
     */
    public function setMerchantName($merchantName)
    {
        return $this->setParameter('merchantName', $merchantName);
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
     * Set merchant password as defined in Docdata backoffice
     *
     * @param string $merchantPassword Password as defined in Docdata backoffice
     *
     * @return SoapAbstractRequest implements a fluent interface
     */
    public function setMerchantPassword($merchantPassword)
    {
        return $this->setParameter('merchantPassword', $merchantPassword);
    }

    /**
     * Set the used payment profile as defined in Docdata backoffice
     *
     * @param string $paymentProfile Payment profile ID
     *
     * @return SoapAbstractRequest
     */
    public function setPaymentProfile(string $paymentProfile)
    {
        return $this->setParameter('paymentProfile', $paymentProfile);
    }

    /**
     * Get the used payment profile as defined in Docdata backoffice
     *
     * @return string
     */
    public function getPaymentProfile()
    {
        if (empty($this->getParameter('paymentProfile'))) {
            return 'default';
        }

        return $this->getParameter('paymentProfile');
    }

    /**
     * Set the amount of days before the payment will be closed if unfinished
     *
     * @param int $paymentDays Amount of days before payment will be closed
     *
     * @return SoapAbstractRequest
     */
    public function setPaymentDays($paymentDays)
    {
        return $this->setParameter('paymentDays', $paymentDays);
    }

    /**
     * Get the amount of days before the payment will be closed if unfinished
     *
     * @return int
     */
    public function getPaymentDays(): int
    {
        if (empty($this->getParameter('paymentDays'))) {
            return 7;
        }

        return $this->getParameter('paymentDays');
    }

    /**
     * Get the ISO 639-1:2002 language of all interfaces Docdata serves to customer
     *
     * @return string ISO 639-1:2002 language code
     *
     * @throws InvalidRequestException
     */
    public function getLanguage()
    {
        if (empty($this->getParameter('language'))) {
            return 'en';
        }

        $language =  strtolower($this->getParameter('language'));
        if (!preg_match('/^[a-z]{2}$/', $language)) {
            throw new InvalidRequestException(
                'Language must be in ISO 639-1:2002 format.'
            );
        }

        return $language;
    }

    /**
     * Set the language of all interfaces Docdata serves to customer (ISO 639-1:2002)
     *
     * @param string $languageCode ISO 639-1:2002 language code
     *
     * @return SoapAbstractRequest
     */
    public function setLanguage(string $languageCode)
    {
        return $this->setParameter('language', $languageCode);
    }

    /**
     * Get the shopper id
     *
     * @return string
     *
     * @throws InvalidRequestException
     */
    public function getShopperId(): string
    {
        if (empty($this->getParameter('shopperId'))) {
            throw new InvalidRequestException('Shopper ID must be set.');
        }
        return $this->getParameter('shopperId');
    }

    /**
     * Set the shopper ID
     *
     * @param string $shopperId Shopper ID
     *
     * @return SoapAbstractRequest
     */
    public function setShopperId(string $shopperId)
    {
        return $this->setParameter('shopperId', $shopperId);
    }

    /**
     * Get the transaction ID.
     *
     * @return string
     *
     * @throws InvalidRequestException
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
     * @param string $pendingUrl Pending request return URL
     *
     * @return SoapAbstractRequest Provides a fluent interface
     */
    public function setPendingUrl($pendingUrl): SoapAbstractRequest
    {
        return $this->setParameter('pendingUrl', $pendingUrl);
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
        $integrationInfo = [];

        $integrationInfo['webshopPlugin'] = 'omnipay-docdata-payments';
        $integrationInfo['webshopPluginVersion'] = 'omnipay-docdata-payments';
        $integrationInfo['programmingLanguage'] = 'php';
        $integrationInfo['operatingSystem'] = PHP_OS;
        $integrationInfo['operatingSystemVersion'] = mb_substr(php_uname(), 0, 35);

        $this->request['integrationInfo'] = $integrationInfo;


        return $this->request;
    }

    /**
     * Build the SOAP Client and the internal request object
     *
     * @return \SoapClient
     *
     * @throws \Exception
     */
    public function buildSoapClient(): \SoapClient
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
            'compression'           => SOAP_COMPRESSION_ACCEPT |
                SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
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
     * @param \SoapClient $soapClient Configured SoapClient
     * @param array       $data       All data to be sent in the transaction
     *
     * @return array
     *
     * @throws \SoapFault
     */
    abstract protected function runTransaction(
        \SoapClient $soapClient,
        array $data
    ): array;

    /**
     * Send Data to the Gateway
     *
     * @param array $data Formatted data to be sent to Docdata
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
        $class = $this->getResponseName();
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

    /**
     * Get the FQDN for the response to be created
     *
     * @return string
     */
    abstract protected function getResponseName(): string;
}
