<?php

namespace Omnipay\DocdataPayments\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\CreditCard;

/**
 * DocdataPayments Create Request
 */
class CreateRequest extends SoapAbstractRequest
{

    private $validateCard = [
        'FirstName' => ['empty'=>false,'min'=>1,'max'=>35],
        'LastName' => ['empty'=>false,'min'=>1,'max'=>35],
        'BillingAddress1' => ['empty'=>false,'min'=>1,'max'=>100],
        'BillingAddress2' => ['empty'=>false,'min'=>1,'max'=>35],
        'BillingCity' => ['empty'=>false,'min'=>1,'max'=>35],
        'BillingCountry' => ['empty'=>false,'min'=>2,'max'=>2],
        'BillingPostcode' => ['empty'=>false,'min'=>1,'max'=>50],
        
        'Title' => ['empty'=>true,'min'=>null,'max'=>50],
        'Gender' => ['empty'=>true,'min'=>null,'max'=>1],
        
    ];

    public function validateCard($card)
    {
        foreach ($this->validateCard as $name => $rule) {
            $value = $card->{"get{$name}"}();
            if (!$rule['empty'] && empty($value)) {
                throw new InvalidRequestException("$name must be set.");
            }
            if (isset($rule['min']) && $rule['min'] && strlen($value) < $rule['min']) {
                throw new InvalidRequestException("$name must be at least {$rule['min']} characters long.");
            }
            
            if (isset($rule['max']) && $rule['max'] && strlen($value) > $rule['max']) {
                throw new InvalidRequestException("$name must be at most {$rule['max']} characters long.");
            }
        }
    }
    
    public function getData()
    {
        $data = parent::getData();
        $card = $this->getCard();
        $this->validateCard($card);
        
        $data['description'] = $this->getDescription();
        $data['paymentPreferences']['profile'] = $this->getPaymentProfile();
        $data['paymentPreferences']['numberOfDaysToPay'] = $this->getPaymentDays();
        $data['merchantOrderReference'] = $this->getTransactionId();
        $data['totalGrossAmount'] = array('_' => $this->getAmountInteger(),'currency' => $this->getCurrency());

        $data['shopper']['id'] = $this->getShopperId();
        $data['shopper']['name']['title'] = $card->getTitle();
        $data['shopper']['name']['first'] = $card->getFirstName();
        $data['shopper']['name']['last'] = $card->getLastName();
        $data['shopper']['email'] = $card->getEmail();
        $data['shopper']['language']['code'] = $this->getLanguage();
        if ($card->getGender() == 'M' || $card->getGender() == 'F') {
            $data['shopper']['gender'] = $card->getGender();
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
            throw new InvalidRequestException('Billing country must be an ISO-3166 two-digit code.');
        } else {
            $data['billTo']['address']['country']['code'] = $card->getBillingCountry();
        }
        
        //non mandatory
        $data['billTo']['name']['prefix'] = $card->getBillingTitle();
        $data['billTo']['address']['company'] = $card->getBillingCountry();
        $data['billTo']['address']['state'] = $card->getBillingState();
        
        /*$data['invoice']['shipTo']['name']['first'] = $card->getShippingFirstName();
        $data['invoice']['shipTo']['name']['last'] = $card->getShippingLastName();
        $data['invoice']['shipTo']['name']['title'] = $card->getShippingTitle();
        $data['invoice']['shipTo']['address']['company'] = $card->getShippingCompany();
        $data['invoice']['shipTo']['address']['street'] = $card->getShippingAddress1();
        $data['invoice']['shipTo']['address']['houseNumber'] = $card->getShippingAddress2();
        $data['invoice']['shipTo']['address']['postalCode'] = $card->getShippingPostcode();
        $data['invoice']['shipTo']['address']['city'] = $card->getShippingCity();
        $data['invoice']['shipTo']['address']['state'] = $card->getShippingState();
        $data['invoice']['shipTo']['address']['country']['code'] = $card->getShippingCountry();
        */
        return $data;
    }
    /**
     * Run the SOAP transaction
     *
     * @param \SoapClient $soapClient
     * @param array       $data
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function runTransaction(\SoapClient $soapClient, array $data): array
    {
        $this->responseName = '\Omnipay\DocdataPayments\Message\CreateResponse';
        return $soapClient->__soapCall('create', $data);
    }
}
