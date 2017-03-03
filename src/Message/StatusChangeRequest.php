<?php

namespace Omnipay\SagePay\Message;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\DocdataPayments\Message\SoapAbstractRequest;
/**
 * Sage Pay Server Notification.
 * The gateway will send the results of Server transactions here.
 */
class ServerNotifyRequest extends SoapAbstractRequest implements NotificationInterface
{
    
}