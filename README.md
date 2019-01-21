# omnipay-docdata-payments
Docdata/CM Payments support for Omnipay payment processing library.

This package is geared towards the Webdirect method of handling orders in Docdata Payments.


## Authorize data
#### Generic
```php
public function getAuthorizeData() {
    $card = new CreditCard(); // Omnipay CC model
    $card->setTitle(null);
    $card->setFirstName($invoice->getCustomerFirstname());
    $card->setLastName($invoice->getCustomerLastname());
    $card->setEmail($invoice->getCustomerEmailAddress());
    $card->setGender('U'); // U, F, M
    $card->setPhone(null);
    $card->setBillingFirstName($invoice->getCustomerFirstname());
    $card->setBillingLastName($invoice->getCustomerLastname());
    $card->setBillingAddress1($formatedAddress['street']);
    $card->setBillingAddress2($formatedAddress['houseNumber']);
    $card->setBillingPostcode($address->getPostalCode());
    $card->setBillingCity(mb_substr($address->getCity(), 0, 35)); // max 35
    $card->setBillingCountry(strtoupper($address->getCountry()));
    $card->setBillingTitle(null);
    $card->setBillingState(null);
    
    $data = [
        'description' => sprintf('Invoice #%s', $invoice->getReference()),
        'paymentProfile' => $this->getDocDataPaymentProfile(), // set in Docdata backoffice
        'paymentDays' => self::DOCDATA_INVOICE_VALIDITY_DAYS, // own choice
        'transactionId' => sprintf('%s _ %s', $invoice->getReference(), Uuid::uuid4()), // shown to customer. Needs to be unique
        'currency' => 'EUR', // capitalized
        'amount' => 100,
        'shopperId' => $invoice->getCustomerReference(),
        'language' => 'en', // lowercase
        'card' => $card,
        'houseNumberAddition' => $formatedAddress['houseNumberAddition'],
        'paymentMethod' => 'IDEAL',
    ];
    
    /* Payment methods (defined by docdata)
     * https://www.cmpayments.com/file_uploads/API_Integration_Manual.pdf #figure 9
     *
     * BanContact => 'MISTERCASH'
     * Bank_transfer => 'BANK_TRANSFER'
     * Elv => 'ELV'
     * Eps => 'EPS'
     * Giropay => 'GIROPAY'
     * Ideal => 'IDEAL'
     * Sofort => 'SOFORT_UEBERWEISUNG'
     */
     
     return $data;
 }
```


#### Ideal
Ideal has some special quirks. Add the issuer to the getAuthorizeData method thusly:
```php
    $data['paymentInputType'] = 'iDealPaymentInput';
    $data['paymentInput'] = [
        'issuerId' => $request['issuerId'],
    ];
```
IssuerId is set by Docdata and not documented in the implementation manual.
```
      iban => docdata code  // bank name
----------------------------------------------------
'ABNANL2A' => 'ABNAMRO'     // ABN AMRO
'ASNBNL21' => 'ASN'         // ASN
'BUNQNL2A' => 'BUNQ'        // Bunq
'HANDNL2A' => 'HAND'        // Svenska Handelsbanken
'INGBNL2A' => 'ING'         // ING
'KNABNL2H' => 'KNAB'        // Knab
'MOYONL21' => 'MOYO'        // MoneYou
'RABONL2U' => 'RABO'        // RABO
'RBRBNL21' => 'REGIOBANK'   // Regiobank
'SNSBNL2A' => 'SNS'         // SNS
'TRIONL2U' => 'TRIODOS'     // Triodos
'FVLBNL22' => 'VANLANSCHOT' // Van Lanschot
```


## CompleteAuthorize
Some payment methods require (most, or a lot) of information also sent on the Authorize call, so getting the same data and adding the transactionReference is the most foolproof way.
```php
public function getCompleteAuthorizeData(): array {
    $data = $this->getAuthorizeData();
    $data['transactionReference'] = $paymentTransaction->getReference();
    return $data;
}
```