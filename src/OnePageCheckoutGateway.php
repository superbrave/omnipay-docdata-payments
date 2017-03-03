<?php

namespace Omnipay\DocdataPayments;

use Guzzle\Http\ClientInterface;
use Omnipay\DocdataPayments\Message\SoapAbstractRequest;
use Omnipay\Common\AbstractGateway;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Allied Wallet SOAP gateway
 *
 * 381808 uses operations of a SOAP service over HTTP/HTTPS to integrate for
 * transactions (including settlement, void, refund, chargeback, etc. capabilities).
 *
 * Before you will be able to submit transactions to 381808, you will need an
 * 381808 merchant account for your website. Once you have a merchant account
 * established, 381808 will supply you with a MerchantID and a SiteID. These IDs
 * uniquely identify your websites, customers, and payments.
 *
 * ### Test Mode
 *
 * There is no test mode for this gateway.  Contact Allied Wallet to enable test mode.
 *
 * Test transactions can be made with these card data:
 *
 * * **Card Number** 4242424242424242
 * * **Expiry Date** Anything in the future
 * * **CVV** CVV 555 will result in a decline, 123 or almost any other will be successful
 *
 * ### Credentials
 *
 * The merchant is identified with a Site ID and a Merchant ID, both of which are 36 character
 * GUIDs in the following format:  xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
 *
 * There appear to be no other credentials such as usernames, passwords, OAuth Tokens, etc.
 *
 * ### Example
 *
 * #### Initialize Gateway
 *
 * <code>
 *   // Create a gateway for the Allied Wallet Soap Gateway
 *   // (routes to GatewayFactory::create)
 *   $gateway = Omnipay::create('DocdataPayments_Soap');
 *
 *   // Initialise the gateway
 *   $gateway->initialize(array(
 *       'merchantId'   => 'MyMerchantId',
 *       'siteId'       => 'MySiteId',
 *       'testMode' => true, // Or false when you are ready for live transactions
 *   ));
 * </code>
 *
 * #### Direct Credit Card Payment
 *
 * <code>
 *   // Create a credit card object
 *   $card = new CreditCard(array(
 *               'firstName' => 'Example',
 *               'lastName' => 'User',
 *               'number' => '4242424242424242',
 *               'expiryMonth'           => '01',
 *               'expiryYear'            => '2020',
 *               'cvv'                   => '123',
 *               'billingAddress1'       => '1 Scrubby Creek Road',
 *               'billingCountry'        => 'AU',
 *               'billingCity'           => 'Scrubby Creek',
 *               'billingPostcode'       => '4999',
 *               'billingState'          => 'QLD',
 *   ));
 *
 *   // Do a purchase transaction on the gateway
 *   try {
 *       $transaction = $gateway->purchase(array(
 *           'amount'        => '10.00',
 *           'currency'      => 'AUD',
 *           'description'   => 'This is a test purchase transaction.',
 *           'card'          => $card,
 *       ));
 *       $response = $transaction->send();
 *       $data = $response->getData();
 *       echo "Gateway purchase response data == " . print_r($data, true) . "\n";
 *
 *       if ($response->isSuccessful()) {
 *           echo "Purchase transaction was successful!\n";
 *       }
 *   } catch (\Exception $e) {
 *       echo "Exception caught while attempting authorize.\n";
 *       echo "Exception type == " . get_class($e) . "\n";
 *       echo "Message == " . $e->getMessage() . "\n";
 *   }
 * </code>
 *
 * ### Quirks
 *
 * * Card Tokens are not supported.
 * * Voids of captured transactions are not supported, only voiding authorize transactions is supported.
 */
class OnePageCheckoutGateway extends AbstractGateway
{
    /** @var \SoapClient */
    protected $soapClient;

    /**
     * Create a new gateway instance
     *
     * @param ClientInterface $httpClient  A Guzzle client to make API calls with
     * @param HttpRequest     $httpRequest A Symfony HTTP request object
     * @param \SoapClient     $soapClient
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
     * Example:
     *
     * <code>
     *   class MyRequest extends \Omnipay\Common\Message\AbstractRequest {};
     *
     *   class MyGateway extends \Omnipay\Common\AbstractGateway {
     *     function myRequest($parameters) {
     *       $this->createRequest('MyRequest', $parameters);
     *     }
     *   }
     *
     *   // Create the gateway object
     *   $gw = Omnipay::create('MyGateway');
     *
     *   // Create the request object
     *   $myRequest = $gw->myRequest($someParameters);
     * </code>
     *
     * @see \Omnipay\Common\Message\AbstractRequest
     * @param string $class The request class name
     * @param array $parameters
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    protected function createRequest($class, array $parameters)
    {
        /** @var SoapAbstractRequest $obj */
        $obj = new $class($this->httpClient, $this->httpRequest, $this->soapClient);

        return $obj->initialize(array_replace($this->getParameters(), $parameters));
    }

    public function getName()
    {
        return 'Docdata Payments One Page Checkout';
    }

    public function getDefaultParameters()
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
    public function getMerchantName()
    {
        return $this->getParameter('merchantName');
    }

    /**
     * Set merchant id
     *
     * Use the Merchant ID assigned by Allied wallet.
     *
     * @param string $value
     * @return SoapGateway implements a fluent interface
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
     */
    public function getMerchantPassword()
    {
        return $this->getParameter('merchantPassword');
    }

    /**
     * Set site id
     *
     * Use the Site ID assigned by Allied wallet.
     *
     * @param string $value
     * @return SoapGateway implements a fluent interface
     */
    public function setMerchantPassword($value)
    {
        return $this->setParameter('merchantPassword', $value);
    }

    /**
     * Create a purchase request
     *
     * @param array $parameters
     * @return \Omnipay\DocdataPayments\Message\SoapPurchaseRequest
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\DocdataPayments\Message\SoapPurchaseRequest', $parameters);
    }

    /**
     * Create an authorize request
     *
     * @param array $parameters
     * @return \Omnipay\DocdataPayments\Message\SoapAuthorizeRequest
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\DocdataPayments\Message\CreateRequest', $parameters);
    }
    
    /**
     * Handle notification callback.
     *
     * @param array $parameters
     * @return \Omnipay\DocdataPayments\Message\SoapAuthorizeRequest
     */
    public function acceptNotification(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\DocdataPayments\Message\StatusRequest', $parameters);
    }

    /**
     * Create a capture request
     *
     * @param array $parameters
     * @return \Omnipay\DocdataPayments\Message\SoapCaptureRequest
     */
    public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\DocdataPayments\Message\CaptureRequest', $parameters);
    }

    /**
     * Create a refund request
     *
     * @param array $parameters
     * @return \Omnipay\DocdataPayments\Message\SoapRefundRequest
     */
    public function refund(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\DocdataPayments\Message\SoapRefundRequest', $parameters);
    }

    /**
     * Create a void request
     *
     * @param array $parameters
     * @return \Omnipay\DocdataPayments\Message\SoapVoidRequest
     */
    public function void(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\DocdataPayments\Message\SoapVoidRequest', $parameters);
    }
}
