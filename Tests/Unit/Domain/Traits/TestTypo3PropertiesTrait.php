<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Traits;

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
        self::assertNull(
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

        self::assertSame(
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

        self::assertNull(
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function getTstampInitiallyReturnsNull()
    {
        self::assertNull(
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

        self::assertSame(
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

        self::assertNull(
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function getSysLanguageUidInitiallyReturnsNull()
    {
        self::assertNull(
            $this->subject->getSysLanguageUid()
        );
    }

    /**
     * @test
     */
    public function setSysLanguageUidSetsSysLanguageUid()
    {
        $this->subject->setSysLanguageUid(123456);

        self::assertSame(
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

        self::assertSame(
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

        self::assertSame(
            1,
            $this->subject->getSysLanguageUid()
        );
    }

    /**
     * @test
     */
    public function getL10nParentInitiallyReturnsZero()
    {
        self::assertSame(
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

        self::assertSame(
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

        self::assertSame(
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

        self::assertSame(
            1,
            $this->subject->getL10nParent()
        );
    }

    /**
     * @test
     */
    public function getStarttimeInitiallyReturnsNull()
    {
        self::assertNull(
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

        self::assertSame(
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

        self::assertNull(
            $this->subject->getStarttime()
        );
    }

    /**
     * @test
     */
    public function getEndtimeInitiallyReturnsNull()
    {
        self::assertNull(
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

        self::assertSame(
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

        self::assertNull(
            $this->subject->getEndtime()
        );
    }

    /**
     * @test
     */
    public function getHiddenInitiallyReturnsFalse()
    {
        self::assertFalse(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function setHiddenSetsHidden()
    {
        $this->subject->setHidden(true);
        self::assertTrue(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function setHiddenWithStringReturnsTrue()
    {
        $this->subject->setHidden('foo bar');
        self::assertTrue($this->subject->getHidden());
    }

    /**
     * @test
     */
    public function setHiddenWithZeroReturnsFalse()
    {
        $this->subject->setHidden(0);
        self::assertFalse($this->subject->getHidden());
    }

    /**
     * @test
     */
    public function getDeletedInitiallyReturnsFalse()
    {
        self::assertFalse(
            $this->subject->getDeleted()
        );
    }

    /**
     * @test
     */
    public function setDeletedSetsDeleted()
    {
        $this->subject->setDeleted(true);
        self::assertTrue(
            $this->subject->getDeleted()
        );
    }

    /**
     * @test
     */
    public function setDeletedWithStringReturnsTrue()
    {
        $this->subject->setDeleted('foo bar');
        self::assertTrue($this->subject->getDeleted());
    }

    /**
     * @test
     */
    public function setDeletedWithZeroReturnsFalse()
    {
        $this->subject->setDeleted(0);
        self::assertFalse($this->subject->getDeleted());
    }

    /**
     * @test
     */
    public function getCruserIdInitiallyReturnsZero()
    {
        self::assertSame(
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

        self::assertSame(
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

        self::assertSame(
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

        self::assertSame(
            1,
            $this->subject->getCruserId()
        );
    }
}
