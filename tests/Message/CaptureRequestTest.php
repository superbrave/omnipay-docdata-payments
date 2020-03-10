<?php

namespace Omnipay\DocdataPayments\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\DocdataPayments\Message\CaptureRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SoapClient;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for {@see CaptureRequest}.
 */
class CaptureRequestTest extends TestCase
{
    /**
     * @var CaptureRequest
     */
    private $request;

    /**
     * @var MockObject
     */
    private $soapClientMock;

    /**
     * Creates a new {@see CaptureRequest} and related mocks for testing.
     */
    protected function setUp(): void
    {
        $httpClientMock = $this->getMockBuilder(ClientInterface::class)
            ->getMock();

        $this->soapClientMock = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new CaptureRequest($httpClientMock, Request::create('/'), $this->soapClientMock);
        $this->request->setMerchantName('superbrave_nl');
        $this->request->setMerchantPassword('12345');
        $this->request->setTransactionReference('79A783DEFG6D38F1C74B4B4AECC8F2E1');
    }

    /**
     * Tests if {@see CaptureRequest::send} throws an {@see InvalidRequestException} when
     * the transaction reference is not set.
     */
    public function testSendThrowsInvalidRequestExceptionWhenTransactionReferenceNotAvailable(): void
    {
        $this->request->setTransactionReference(null);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('The transactionReference parameter is required');

        $this->request->send();
    }

    /**
     * Tests if {@see CaptureRequest::send} returns a successful {@see CaptureResponse} when it successfully
     * sends a capture request for a payment.
     *
     * @depends testSendThrowsInvalidRequestExceptionWhenTransactionReferenceNotAvailable
     */
    public function testSendSuccessfulCapture(): void
    {
        $this->soapClientMock->expects($this->exactly(2))
            ->method('__soapCall')
            ->withConsecutive(
                [
                    'status',
                    $this->callback(function ($data) {
                        unset($data[0]['integrationInfo']);

                        $this->assertSame(
                            [
                                [
                                    'version' => '1.3',
                                    'merchant' => [
                                        'name' => 'superbrave_nl',
                                        'password' => '12345',
                                    ],
                                    'paymentOrderKey' => '79A783DEFG6D38F1C74B4B4AECC8F2E1',
                                ],
                            ],
                            $data
                        );

                        return true;
                    }),
                ],
                [
                    'capture',
                    $this->callback(function ($data) {
                        unset($data[0]['integrationInfo']);

                        $this->assertSame(
                            [
                                [
                                    'version' => '1.3',
                                    'merchant' => [
                                        'name' => 'superbrave_nl',
                                        'password' => '12345',
                                    ],
                                    'paymentId' => 3058909231,
                                ],
                            ],
                            $data
                        );

                        return true;
                    }),
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createStatusSuccessResponse(),
                $this->createCaptureSuccessResponse()
            );

        $response = $this->request->send();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * Tests if {@see CaptureRequest::send} returns a successful {@see CaptureResponse} when it successfully
     * sends a capture request one of multiple payments.
     *
     * @depends testSendSuccessfulCapture
     */
    public function testSendSuccessfulCaptureWithMultiplePaymentsOnOrder(): void
    {
        $this->soapClientMock->expects($this->exactly(2))
            ->method('__soapCall')
            ->withConsecutive(
                [
                    'status',
                    $this->isType('array'),
                ],
                [
                    'capture',
                    $this->callback(function ($data) {
                        unset($data[0]['integrationInfo']);

                        $this->assertSame(
                            [
                                [
                                    'version' => '1.3',
                                    'merchant' => [
                                        'name' => 'superbrave_nl',
                                        'password' => '12345',
                                    ],
                                    'paymentId' => 3058909232,
                                ],
                            ],
                            $data
                        );

                        return true;
                    }),
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createStatusSuccessResponseWithMultiplePayments(),
                $this->createCaptureSuccessResponse()
            );

        $response = $this->request->send();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * Tests if {@see CaptureRequest::send} returns an unsuccessful {@see CaptureResponse} when
     * the internal status request fails.
     *
     * @depends testSendSuccessfulCapture
     */
    public function testSendNotSuccessfulCaptureWhenStatusRequestFails(): void
    {
        $this->soapClientMock->expects($this->exactly(2))
            ->method('__soapCall')
            ->withConsecutive(
                [
                    'status',
                    $this->isType('array'),
                ],
                [
                    'capture',
                    $this->callback(function ($data) {
                        unset($data[0]['integrationInfo']);

                        $this->assertSame(
                            [
                                [
                                    'version' => '1.3',
                                    'merchant' => [
                                        'name' => 'superbrave_nl',
                                        'password' => '12345',
                                    ],
                                    'paymentId' => 0,
                                ],
                            ],
                            $data
                        );

                        return true;
                    }),
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createStatusErrorResponse(),
                $this->createCapturePaymentIdIncorrectErrorResponse()
            );

        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
    }

    /**
     * Tests if {@see CaptureRequest::send} returns an unsuccessful {@see CaptureResponse} when trying to capture
     * an already captured payment.
     *
     * @depends testSendSuccessfulCapture
     */
    public function testSendNotSuccessfulCaptureWhenPaymentIdIncorrect(): void
    {
        $this->markTestIncomplete();
        $this->soapClientMock->expects($this->exactly(2))
            ->method('__soapCall')
            ->withConsecutive(
                [
                    'status',
                    $this->isType('array'),
                ],
                [
                    'capture',
                    $this->callback(function ($data) {
                        unset($data[0]['integrationInfo']);

                        $this->assertSame(
                            [
                                [
                                    'version' => '1.3',
                                    'merchant' => [
                                        'name' => 'superbrave_nl',
                                        'password' => '12345',
                                    ],
                                    'paymentId' => 3058909231,
                                ],
                            ],
                            $data
                        );

                        return true;
                    }),
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createStatusSuccessResponse(),
                $this->createCapturePaymentIdIncorrectErrorResponse()
            );

        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
    }

    /**
     * Tests if {@see CaptureRequest::send} returns an unsuccessful {@see CaptureResponse} when trying to capture
     * an already captured payment.
     *
     * @depends testSendSuccessfulCapture
     */
    public function testSendNotSuccessfulCaptureWhenAlreadyCaptured(): void
    {
        $this->soapClientMock->expects($this->exactly(2))
            ->method('__soapCall')
            ->withConsecutive(
                [
                    'status',
                    $this->isType('array'),
                ],
                [
                    'capture',
                    $this->callback(function ($data) {
                        unset($data[0]['integrationInfo']);

                        $this->assertSame(
                            [
                                [
                                    'version' => '1.3',
                                    'merchant' => [
                                        'name' => 'superbrave_nl',
                                        'password' => '12345',
                                    ],
                                    'paymentId' => 3058909231,
                                ],
                            ],
                            $data
                        );

                        return true;
                    }),
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createStatusSuccessResponseWithAlreadyCapturedPayment(),
                $this->createCaptureAlreadyCapturedErrorResponse()
            );

        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
    }

    /**
     * Returns a new stdClass representing a successful status SOAP call.
     *
     * @return stdClass
     */
    private function createStatusSuccessResponse(): stdClass
    {
        $response = new stdClass();
        $response->statusSuccess = new stdClass();
        $response->statusSuccess->_ = 'Operation successful.';
        $response->statusSuccess->code = 'SUCCESS';
        $response->statusSuccess->report = new stdClass();
        $response->statusSuccess->report->approximateTotals = new stdClass();
        $response->statusSuccess->report->approximateTotals->totalRegistered = 1000;
        $response->statusSuccess->report->approximateTotals->totalShopperPending = 0;
        $response->statusSuccess->report->approximateTotals->totalAcquirerPending = 0;
        $response->statusSuccess->report->approximateTotals->totalAcquirerApproved = 1000;
        $response->statusSuccess->report->approximateTotals->totalCaptured = 0;
        $response->statusSuccess->report->approximateTotals->totalRefunded = 0;
        $response->statusSuccess->report->approximateTotals->totalChargedback = 0;
        $response->statusSuccess->report->approximateTotals->totalReversed = 0;
        $response->statusSuccess->report->approximateTotals->exchangedTo = 'EUR';
        $response->statusSuccess->report->approximateTotals->exchangeRateDate = '2020-03-10 10:58:16';
        $response->statusSuccess->report->payment = new stdClass();
        $response->statusSuccess->report->payment->id = 3058909231;
        $response->statusSuccess->report->payment->paymentMethod = 'ELV';
        $response->statusSuccess->report->payment->authorization = new stdClass();
        $response->statusSuccess->report->payment->authorization->status = 'AUTHORIZED';
        $response->statusSuccess->report->payment->authorization->amount = new stdClass();
        $response->statusSuccess->report->payment->authorization->amount->_ = 1000;
        $response->statusSuccess->report->payment->authorization->amount->currency = 'EUR';
        $response->statusSuccess->report->payment->authorization->amount->confidenceLevel = 'ACQUIRER_APPROVED';
        $response->statusSuccess->report->consideredSafe = new stdClass();
        $response->statusSuccess->report->consideredSafe->value = true;
        $response->statusSuccess->report->consideredSafe->level = 'SAFE';
        $response->statusSuccess->report->consideredSafe->date = '2020-03-06T12:05:50.846+01:00';
        $response->statusSuccess->report->consideredSafe->reason = 'EXACT_MATCH';
        $response->statusSuccess->report->apiInformation = new stdClass();
        $response->statusSuccess->report->apiInformation->originalVersion = '1.3';
        $response->statusSuccess->report->apiInformation->conversionApplied = false;
        $response->statusSuccess->ddpXsdVersion = '1.3.14';

        return $response;
    }

    /**
     * Returns a new stdClass representing a successful status SOAP call with multiple payments.
     *
     * @return stdClass
     */
    private function createStatusSuccessResponseWithMultiplePayments(): stdClass
    {
        $response = $this->createStatusSuccessResponse();

        $response->statusSuccess->report->approximateTotals->totalRegistered = 2000;
        $response->statusSuccess->report->approximateTotals->totalAcquirerApproved = 1000;
        $response->statusSuccess->report->approximateTotals->totalCaptured = 1000;

        $firstPayment = new stdClass();
        $firstPayment->id = 3058909231;
        $firstPayment->paymentMethod = 'ELV';
        $firstPayment->authorization = new stdClass();
        $firstPayment->authorization->status = 'AUTHORIZED';
        $firstPayment->authorization->amount = new stdClass();
        $firstPayment->authorization->amount->_ = 1000;
        $firstPayment->authorization->amount->currency = 'EUR';
        $firstPayment->authorization->amount->confidenceLevel = 'ACQUIRER_APPROVED';
        $firstPayment->authorization->capture = new stdClass();
        $firstPayment->authorization->capture->status = 'CAPTURED';
        $firstPayment->authorization->capture->amount = new stdClass();
        $firstPayment->authorization->capture->amount->_ = 1000;
        $firstPayment->authorization->capture->amount->currency = 'EUR';

        $secondPayment = new stdClass();
        $secondPayment->id = 3058909232;
        $secondPayment->paymentMethod = 'ELV';
        $secondPayment->authorization = new stdClass();
        $secondPayment->authorization->status = 'AUTHORIZED';
        $secondPayment->authorization->amount = new stdClass();
        $secondPayment->authorization->amount->_ = 1000;
        $secondPayment->authorization->amount->currency = 'EUR';
        $secondPayment->authorization->amount->confidenceLevel = 'ACQUIRER_APPROVED';

        $response->statusSuccess->report->payment = [$firstPayment, $secondPayment];

        return $response;
    }

    /**
     * Returns a new stdClass representing a successful status SOAP call with an already captured payment.
     *
     * @return stdClass
     */
    private function createStatusSuccessResponseWithAlreadyCapturedPayment(): stdClass
    {
        $response = $this->createStatusSuccessResponse();

        $response->statusSuccess->report->approximateTotals->totalAcquirerApproved = 1000;
        $response->statusSuccess->report->approximateTotals->totalCaptured = 1000;

        $response->statusSuccess->report->payment->authorization->capture = new stdClass();
        $response->statusSuccess->report->payment->authorization->capture->status = 'CAPTURED';
        $response->statusSuccess->report->payment->authorization->capture->amount = new stdClass();
        $response->statusSuccess->report->payment->authorization->capture->amount->_ = 1000;
        $response->statusSuccess->report->payment->authorization->capture->amount->currency = 'EUR';

        return $response;
    }

    /**
     * Returns a new stdClass representing an unsuccessful status SOAP call.
     *
     * @return stdClass
     */
    private function createStatusErrorResponse(): stdClass
    {
        $response = new stdClass();
        $response->statusErrors = new stdClass();
        $response->statusErrors->error = new stdClass();
        $response->statusErrors->error->_ = 'Order could not be found with the given key.';
        $response->statusErrors->error->code = 'REQUEST_DATA_INCORRECT';

        return $response;
    }

    /**
     * Returns a new stdClass representing a successful capture SOAP call.
     *
     * @return stdClass
     */
    private function createCaptureSuccessResponse(): stdClass
    {
        $response = new stdClass();
        $response->captureSuccess = new stdClass();
        $response->captureSuccess->success = new stdClass();
        $response->captureSuccess->success->_ = 'Operation successful.';
        $response->captureSuccess->success->code = 'SUCCESS';

        return $response;
    }

    /**
     * Returns a new stdClass representing an unsuccessful capture SOAP call when the provided payment id is incorrect.
     *
     * @return stdClass
     */
    private function createCapturePaymentIdIncorrectErrorResponse(): stdClass
    {
        $response = new stdClass();
        $response->captureErrors = new stdClass();
        $response->captureErrors->error = new stdClass();
        $response->captureErrors->error->_ = 'Payment id incorrect.';
        $response->captureErrors->error->code = 'REQUEST_DATA_INCORRECT';

        return $response;
    }

    /**
     * Returns a new stdClass representing an unsuccessful capture SOAP call when the payment is already captured.
     *
     * @return stdClass
     */
    private function createCaptureAlreadyCapturedErrorResponse(): stdClass
    {
        $response = new stdClass();
        $response->captureErrors = new stdClass();
        $response->captureErrors->error = new stdClass();
        $response->captureErrors->error->_ = 'No amount authorized available to capture.';
        $response->captureErrors->error->code = 'REQUEST_DATA_INCORRECT';

        return $response;
    }
}
