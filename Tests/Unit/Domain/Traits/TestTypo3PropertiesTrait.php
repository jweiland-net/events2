<?php
namespace JWeiland\Events2\Tests\Unit\Domain\Traits;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case.
 */
trait TestTypo3PropertiesTrait
{
    /**
     * @test
     */
    public function getCrdateInitiallyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function setCrdateSetsCrdate()
    {
        $date = new \DateTime();
        $this->subject->setCrdate($date);

        $this->assertSame(
            $date,
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function setCrdateWithNullSetsCrdate()
    {
        $this->subject->setCrdate(null);

        $this->assertNull(
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function getTstampInitiallyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function setTstampSetsTstamp()
    {
        $date = new \DateTime();
        $this->subject->setTstamp($date);

        $this->assertSame(
            $date,
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function setTstampWithNullSetsTstamp()
    {
        $this->subject->setTstamp(null);

        $this->assertNull(
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function getSysLanguageUidInitiallyReturnsNull()
    {
        $this->assertSame(
            null,
            $this->subject->getSysLanguageUid()
        );
    }

    /**
     * @test
     */
    public function setSysLanguageUidSetsSysLanguageUid()
    {
        $this->subject->setSysLanguageUid(123456);

        $this->assertSame(
            123456,
            $this->subject->getSysLanguageUid()
        );
    }

    /**
     * @test
     */
    public function setSysLanguageUidWithStringResultsInInteger()
    {
        $this->subject->setSysLanguageUid('123Test');

        $this->assertSame(
            123,
            $this->subject->getSysLanguageUid()
        );
    }

    /**
     * @test
     */
    public function setSysLanguageUidWithBooleanResultsInInteger()
    {
        $this->subject->setSysLanguageUid(true);

        $this->assertSame(
            1,
            $this->subject->getSysLanguageUid()
        );
    }

    /**
     * @test
     */
    public function getL10nParentInitiallyReturnsZero()
    {
        $this->assertSame(
            0,
            $this->subject->getL10nParent()
        );
    }

    /**
     * @test
     */
    public function setL10nParentSetsL10nParent()
    {
        $this->subject->setL10nParent(123456);

        $this->assertSame(
            123456,
            $this->subject->getL10nParent()
        );
    }

    /**
     * @test
     */
    public function setL10nParentWithStringResultsInInteger()
    {
        $this->subject->setL10nParent('123Test');

        $this->assertSame(
            123,
            $this->subject->getL10nParent()
        );
    }

    /**
     * @test
     */
    public function setL10nParentWithBooleanResultsInInteger()
    {
        $this->subject->setL10nParent(true);

        $this->assertSame(
            1,
            $this->subject->getL10nParent()
        );
    }

    /**
     * @test
     */
    public function getStarttimeInitiallyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getStarttime()
        );
    }

    /**
     * @test
     */
    public function setStarttimeSetsStarttime()
    {
        $date = new \DateTime();
        $this->subject->setStarttime($date);

        $this->assertSame(
            $date,
            $this->subject->getStarttime()
        );
    }

    /**
     * @test
     */
    public function setStarttimeWithNullSetsStarttime()
    {
        $this->subject->setStarttime(null);

        $this->assertNull(
            $this->subject->getStarttime()
        );
    }

    /**
     * @test
     */
    public function getEndtimeInitiallyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getEndtime()
        );
    }

    /**
     * @test
     */
    public function setEndtimeSetsEndtime()
    {
        $date = new \DateTime();
        $this->subject->setEndtime($date);

        $this->assertSame(
            $date,
            $this->subject->getEndtime()
        );
    }

    /**
     * @test
     */
    public function setEndtimeWithNullSetsEndtime()
    {
        $this->subject->setEndtime(null);

        $this->assertNull(
            $this->subject->getEndtime()
        );
    }

    /**
     * @test
     */
    public function getHiddenInitiallyReturnsFalse()
    {
        $this->assertFalse(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function setHiddenSetsHidden()
    {
        $this->subject->setHidden(true);
        $this->assertTrue(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function setHiddenWithStringReturnsTrue()
    {
        $this->subject->setHidden('foo bar');
        $this->assertTrue($this->subject->getHidden());
    }

    /**
     * @test
     */
    public function setHiddenWithZeroReturnsFalse()
    {
        $this->subject->setHidden(0);
        $this->assertFalse($this->subject->getHidden());
    }

    /**
     * @test
     */
    public function getDeletedInitiallyReturnsFalse()
    {
        $this->assertFalse(
            $this->subject->getDeleted()
        );
    }

    /**
     * @test
     */
    public function setDeletedSetsDeleted()
    {
        $this->subject->setDeleted(true);
        $this->assertTrue(
            $this->subject->getDeleted()
        );
    }

    /**
     * @test
     */
    public function setDeletedWithStringReturnsTrue()
    {
        $this->subject->setDeleted('foo bar');
        $this->assertTrue($this->subject->getDeleted());
    }

    /**
     * @test
     */
    public function setDeletedWithZeroReturnsFalse()
    {
        $this->subject->setDeleted(0);
        $this->assertFalse($this->subject->getDeleted());
    }

    /**
     * @test
     */
    public function getCruserIdInitiallyReturnsZero()
    {
        $this->assertSame(
            0,
            $this->subject->getCruserId()
        );
    }

    /**
     * @test
     */
    public function setCruserIdSetsCruserId()
    {
        $this->subject->setCruserId(123456);

        $this->assertSame(
            123456,
            $this->subject->getCruserId()
        );
    }

    /**
     * @test
     */
    public function setCruserIdWithStringResultsInInteger()
    {
        $this->subject->setCruserId('123Test');

        $this->assertSame(
            123,
            $this->subject->getCruserId()
        );
    }

    /**
     * @test
     */
    public function setCruserIdWithBooleanResultsInInteger()
    {
        $this->subject->setCruserId(true);

        $this->assertSame(
            1,
            $this->subject->getCruserId()
        );
    }
}
