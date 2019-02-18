<?php

namespace Omnipay\DocdataPayments\Message;

/**
 * Does a STATUS request, but returns a slightly different Response (due to bank transfer)
 *
 * @package Omnipay\DocdataPayments\Message
 */
class OnePageCompleteAuthorizeRequest extends StatusRequest
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseName(): string
    {
        return OnePageCompleteAuthorizeResponse::class;
    }
}