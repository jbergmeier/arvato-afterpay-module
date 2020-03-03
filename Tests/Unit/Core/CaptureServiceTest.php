<?php
/**
 * This Software is the property of OXID eSales and is protected
 * by copyright law - it is NOT Freeware.
 *
 * Any unauthorized use of this software without a valid license key
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 *
 * @category  module
 * @package   afterpay
 * @author    OXID Professional services
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

namespace OxidProfessionalServices\ArvatoAfterPayModule\Tests\Unit\Core;

use \OxidEsales\Eshop\Core\Registry;
use \OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Class CaptureServiceTest: Tests for CaptureService.
 */
class CaptureServiceTest extends \OxidEsales\TestingLibrary\UnitTestCase
{

    /**
     * Read DB Fixtures
     */
    public function setUp()
    {
        parent::setUp();
        $sql = file_get_contents(Registry::getConfig()->getConfigParam('sShopDir') . '/modules/arvato/afterpay/Tests/Fixtures/orders_setUp.sql');
        foreach (explode(';', $sql) as $query) {
            $query = trim($query);
            if ($query) {
                DatabaseProvider::getDb()->execute($query);
            }
        }
    }

    /**
     * Delete DB Fixtures
     */
    public function tearDown()
    {
        parent::tearDown();
        $sql = file_get_contents(Registry::getConfig()->getConfigParam('sShopDir') . '/modules/arvato/afterpay/Tests/Fixtures/generalTearDown.sql');
        foreach (explode(';', $sql) as $query) {
            $query = trim($query);
            if ($query) {
                DatabaseProvider::getDb()->execute($query);
            }
        }
    }

    /**
     * Testing method capture - success
     */
    public function testCapture_success()
    {
        $oxOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        $oxOrder->load('unitauthorizedorder');
        $sut = $this->getSutThatWillSucceedCapture($oxOrder);

        $CaptureResponseEntity = $sut->capture('SomeApiKey');

        $this->assertEquals(123.45, $CaptureResponseEntity->getCapturedAmount());
        $this->assertEquals(123.45, $CaptureResponseEntity->getAuthorizedAmount());
        $this->assertEquals(0, $CaptureResponseEntity->getRemainingAuthorizedAmount());

        $this->assertEquals(
            'captured',
            $oxOrder->getAfterpayOrder()->getStatus()
        );

    }

    /**
     * Testing method capture - failure
     */
    public function testCapture_failure()
    {
        $oxOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        $oxOrder->load('unitauthorizedorder');
        $sut = $this->getSutThatWillFailCapture($oxOrder);
        $CaptureResponseEntity = $sut->capture('SomeApiKey');

        $this->assertNull($CaptureResponseEntity->getCapturedAmount());
        $this->assertNull($CaptureResponseEntity->getAuthorizedAmount());
        $this->assertNull($CaptureResponseEntity->getRemainingAuthorizedAmount());

        //
        $this->assertEquals(
            'authorized',
            $oxOrder->getAfterpayOrder()->getStatus(),
            'Assert that order is not set to captures-status on failure'
        );

    }

    /**
     * Testing method getErrorMessages - Capture fails, Error Message present
     */
    public function testGetErrorMessages_onErrors()
    {
        $oxOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        $oxOrder->load('unitauthorizedorder');
        $sut = $this->getSutThatWillFailCapture($oxOrder);
        $sut->capture('SomeApiKey');
        $errorMessages = $sut->getErrorMessages();
        $this->assertTrue(0 < strlen($errorMessages));
        $this->assertEquals('Some ErrorMessage', $errorMessages);
    }

    /**
     * Testing method getErrorMessages - Capture Successfull, no Errors
     */
    public function testGetErrorMessages_onNoErrors()
    {
        $oxOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        $oxOrder->load('unitauthorizedorder');
        $sut = $this->getSutThatWillSucceedCapture($oxOrder);
        $sut->capture('SomeApiKey');
        $errorMessages = $sut->getErrorMessages();
        $this->assertEquals('', $errorMessages);
    }

    public function test_executeRequestFromOrderData() {

        $sut =
            $this->getMockBuilder(\OxidProfessionalServices\ArvatoAfterPayModule\Core\CaptureService::class)
                ->disableOriginalConstructor()
                ->setMethods(['getCaptureDataForApi', 'getCaptureClientForApi'])
                ->getMock();

        // Client

        $mockClient =
            $this->getMockBuilder(\OxidProfessionalServices\ArvatoAfterPayModule\Core\WebServiceClient::class)
                ->setMethods(['execute'])
                ->getMock();

        $mockClient
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue('###OK###'));

        // SUT

        $sut
            ->expects($this->once())
            ->method('getCaptureClientForApi')
            ->will($this->returnValue($mockClient));

        $sut
            ->expects($this->once())
            ->method('getCaptureDataForApi')
            ->will($this->returnValue('###OK###'));

        // run
        $this->assertEquals('###OK###', $sut->test_executeRequestFromOrderData('SomeApiKey'));
    }

    /**
     * @param stdClass|stdClass[] $response
     * @param oxOrder $mockOxOrder
     *
     * @return CaptureService Mocked
     */
    protected function getMockedCaptureService($response, \OxidEsales\Eshop\Application\Model\Order $mockOxOrder)
    {
        $mockCaptureService =
            $this->getMockBuilder(\OxidProfessionalServices\ArvatoAfterPayModule\Core\CaptureService::class)
                ->setConstructorArgs([$mockOxOrder])
                ->setMethods(array('_executeRequestFromOrderData'))
                ->getMock();

        $mockCaptureService
            ->expects($this->once())
            ->method('_executeRequestFromOrderData')
            ->will($this->returnValue($response));

        return $mockCaptureService;
    }


    /**
     * Return session Mock that tests if reservationId and checkoutId is stored correctly
     *
     * @return oxSession Mocked
     */
    protected function getMockOxSession()
    {
        $mockOxSession =
            $this->getMockBuilder(\OxidEsales\Eshop\Core\Session::class)
                ->disableOriginalConstructor()
                ->disableOriginalClone()
                ->setMethods(array('setVariable'))
                ->getMock();
        return $mockOxSession;
    }

    /**
     * @param oxOrder $orOrder
     *
     * @return CaptureService
     */
    protected function getSutThatWillFailCapture(\OxidEsales\Eshop\Application\Model\Order $oxOrder)
    {
        $response = json_decode(file_get_contents(
                Registry::getConfig()->getConfigParam('sShopDir')
                . '/modules/arvato/afterpay/Tests/Fixtures/captureErrorResponse.json')
        );

        // Self-Testing Fixtures:
        $this->assertNotNull($oxOrder->getAfterpayOrder());
        $this->assertEquals('unitauthorizedorder', $oxOrder->getAfterpayOrder()->getId());
        $this->assertEquals(
            'authorized',
            $oxOrder->getAfterpayOrder()->getStatus(),
            'Self-testing fixture: Failing order has to start out "authorized"'
        );
        // End of Self-Test

        $sut = $this->getMockedCaptureService($response, $oxOrder);
        return $sut;
    }

    /**
     * @param $oxOrder
     *
     * @return CaptureService
     */
    protected function getSutThatWillSucceedCapture($oxOrder)
    {

        // Build SUT : Get ResponseData to inject

        $response = json_decode(file_get_contents(
                Registry::getConfig()->getConfigParam('sShopDir')
                . '/modules/arvato/afterpay/Tests/Fixtures/captureSuccessResponse.json')
        );

        // Self-Testing Fixtures:
        $this->assertNotNull($oxOrder->getAfterpayOrder());
        $this->assertEquals('unitauthorizedorder', $oxOrder->getAfterpayOrder()->getId());
        $this->assertEquals(
            'authorized',
            $oxOrder->getAfterpayOrder()->getStatus(),
            'Self-testing fixture: Succeeding order has to start out "authorized"'
        );
        // End of Self-Test

        $sut = $this->getMockedCaptureService($response, $oxOrder);
        return $sut;
    }

}