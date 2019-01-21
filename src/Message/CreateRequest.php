<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Create Request
 */
class CreateRequest extends SoapAbstractRequest
{
    /**
     * Most implementations might want to have the START event trigger immediately after the CREATE request.
     * This because you receive the issuer from the customer on the first page the user interacts with.
     *
     * @var bool
     */
    protected $fireStartRequestAfterCreate = true;

    /**
     * Payment method specific information to be used when executing a START request.
     * E.g. 'issuerId' for iDeal.
     *
     * @var array
     */
    protected $paymentInput = [];

    /**
     * Name of the payment method input. E.g. misterCashPaymentInput, elvPaymentInput, iDealPaymentInput
     * These are defined in the docdata wsdl
     *
     * @var string
     */
    protected $paymentInputType;

    /**
     * Optional addition to the housenumber. CreditCard object only supports 2 address lines.
     * Docdata has street (100), housenumber (35), housenumberAddition (35)
     *
     * @var string
     */
    protected $houseNumberAddition = '';

    /**
     * List of restrictions for data
     *
     * @var $cardRestrictions array
     */
    private $cardRestrictions = [
        'FirstName' => [
            'empty' => false,
            'min' => 1,
            'max' => 35
        ],
        'LastName' => [
            'empty' => false,
            'min' => 1,
            'max' => 35
        ],
        'BillingAddress1' => [
            'empty' => false,
            'min' => 1,
            'max' => 100
        ],
        'BillingAddress2' => [
            'empty' => true,
            'max' => 35
        ],
        'BillingCity' => [
            'empty' => false,
            'min' => 1,
            'max' => 35
        ],
        'BillingCountry' => [
            'empty' => false,
            'min' => 2,
            'max' => 2
        ],
        'BillingPostcode' => [
            'empty' => false,
            'min' => 1,
            'max' => 50
        ],
        
        'Title' => [
            'empty' => true,
            'min' => null,
            'max' => 50
        ],
        'Gender' => [
            'empty' => true,
            'min' => null,
            'max' => 1
        ],
    ];

    /**
     * Check fields on card for invalid data
     *
     * @param CreditCard $card Credit card to validate
     *
     * @TODO Validating user input with exceptions is not the best way to go
     *
     * @return void
     * @throws InvalidRequestException
     */
    public function validateCard($card)
    {
        foreach ($this->cardRestrictions as $name => $rule) {
            $value = $card->{"get{$name}"}();
            if (empty($value) && !$rule['empty']) {
                throw new InvalidRequestException("$name must be set.");
            }
            if (isset($rule['min']) && $rule['min']
                && strlen($value) < $rule['min']
            ) {
                throw new InvalidRequestException(
                    "$name must be at least {$rule['min']} characters long."
                );
            }
            
            if (isset($rule['max']) && $rule['max']
                && strlen($value) > $rule['max']
            ) {
                throw new InvalidRequestException(
                    "$name must be at most {$rule['max']} characters long."
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     *
     * @throws InvalidRequestException
     */
    public function getData()
    {
        $data = parent::getData();
        $card = $this->getCard();
        $this->validateCard($card);
        
        $data['description'] = $this->getDescription();
        $data['paymentPreferences']['profile'] = $this->getPaymentProfile();
        $data['paymentPreferences']['numberOfDaysToPay'] = $this->getPaymentDays();
        $data['merchantOrderReference'] = $this->getTransactionId();
        $data['totalGrossAmount'] = array(
            '_' => $this->getAmountInteger(),
            'currency' => $this->getCurrency()
        );

        $data['shopper']['id'] = $this->getShopperId();
        $data['shopper']['name']['title'] = $card->getTitle();
        $data['shopper']['name']['first'] = $card->getFirstName();
        $data['shopper']['name']['last'] = $card->getLastName();
        $data['shopper']['email'] = $card->getEmail();
        $data['shopper']['language']['code'] = $this->getLanguage();

        $gender = $card->getGender();
        if ($gender === 'M' || $gender === 'F') {
            $data['shopper']['gender'] = $gender;
        } else {
            $data['shopper']['gender'] = 'U';
        }
        $data['shopper']['dateOfBirth'] = $card->getBirthday();
        $data['shopper']['phoneNumber'] = $card->getPhone();
        $data['shopper']['mobilePhoneNumber'] = $card->getPhone();
        
        $data['billTo']['name']['first'] = $card->getBillingFirstName();
        $data['billTo']['name']['last'] = $card->getBillingLastName();
        
        $data['billTo']['address']['street'] = $card->getBillingAddress1();
        $data['billTo']['address']['houseNumber'] = $card->getBillingAddress2();
        $data['billTo']['address']['city'] = $card->getBillingCity();
        $data['billTo']['address']['postalCode'] = $card->getBillingPostcode();
        
        if (!preg_match('/^[A-Z]{2}$/', $card->getBillingCountry())) {
            throw new InvalidRequestException(
                'Billing country must be an ISO-3166 two-digit code.'
            );
        }

        $data['billTo']['address']['country']['code'] = $card->getBillingCountry();

        //non mandatory
        $data['billTo']['name']['prefix'] = $card->getBillingTitle();
        $data['billTo']['address']['company'] = $card->getBillingCountry();
        $data['billTo']['address']['state'] = $card->getBillingState();

        return $data;
    }

    /**
     * Get data that is required to fire a START request
     *
     * @param string $transactionReference
     */
    public function getStartData(string $transactionReference)
    {
        $data = parent::getData();
        $data['paymentOrderKey'] = $transactionReference;
        $data['returnUrl'] = $this->getReturnUrl();
        $data['payment']['paymentMethod']= $this->getPaymentMethod();

        if (!empty($this->getPaymentInputType())) {
            $data['payment'][$this->getPaymentInputType()] = $this->getPaymentInput();
        }

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
        /** @var \stdClass $createResponse */
        $createResponse = $soapClient->__soapCall('create', [$data]);

        if (isset($createResponse->createSuccess)) {
            // this will not return the start Response, as a createResponse is expected

            $startData = $this->getStartData($createResponse->createSuccess->key ?? '');
            $startResponse = $soapClient->__soapCall('start', [$startData]);

            if (isset($startResponse->startSuccess) && isset($startResponse->redirect)) {
                $createResponse->redirect = $startResponse->redirect;
            }
        }

        return $createResponse;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseName(): string
    {
        return CreateResponse::class;
    }

    /**
     * @return bool
     */
    public function getFireStartRequestAfterCreate(): bool
    {
        return $this->fireStartRequestAfterCreate;
    }

    /**
     * @param bool $fireStartRequestAfterCreate
     */
    public function setFireStartRequestAfterCreate(bool $fireStartRequestAfterCreate)
    {
        $this->fireStartRequestAfterCreate = $fireStartRequestAfterCreate;
    }

    /**
     * @return array
     */
    public function getPaymentInput(): array
    {
        return $this->paymentInput;
    }

    /**
     * @param array $paymentInput
     */
    public function setPaymentInput(array $paymentInput)
    {
        $this->paymentInput = $paymentInput;
    }

    /**
     * @return string
     */
    public function getPaymentInputType(): string
    {
        return $this->paymentInputType;
    }

    /**
     * @param string $paymentInputType
     */
    public function setPaymentInputType(string $paymentInputType)
    {
        $this->paymentInputType = $paymentInputType;
    }

    /**
     * @return string
     */
    public function getHouseNumberAddition(): string
    {
        return $this->houseNumberAddition;
    }

    /**
     * @param string|null $houseNumberAddition
     */
    public function setHouseNumberAddition($houseNumberAddition)
    {
        if ($houseNumberAddition === null) {
            $this->houseNumberAddition = '';
            return;
        }
        $this->houseNumberAddition = $houseNumberAddition;
    }
}
