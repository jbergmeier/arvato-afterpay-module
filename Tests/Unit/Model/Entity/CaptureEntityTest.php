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

namespace OxidProfessionalServices\ArvatoAfterpayModule\Tests\Unit\Model\Entity;

/**
 * Class CaptureEntityTest: Tests for CaptureEntity.
 */
class CaptureEntityTest extends \OxidProfessionalServices\ArvatoAfterpayModule\Tests\Unit\Model\Entity\EntityAbstract
{

    /**
     * Testing method getOrderDetails
     * Testing method setOrderDetails
     */
    public function testGetAndSetOrderDetails()
    {
        $orderDetails = new \stdClass();
        $orderDetails->lorem = 'ipsum';

        $testData = [
            'orderDetails' => $orderDetails
        ];

        $testObject = $this->getSUT();
        $this->_testGetSet($testObject, $testData);
        $this->assertEquals((object) $testData, $testObject->exportData(), 'exported object not valid');
    }

    /**
     * SUT generator
     *
     * @return CaptureEntity
     */
    protected function getSUT()
    {
        return oxNew(\OxidProfessionalServices\ArvatoAfterpayModule\Application\Model\Entity\CaptureEntity::class);
    }
}
