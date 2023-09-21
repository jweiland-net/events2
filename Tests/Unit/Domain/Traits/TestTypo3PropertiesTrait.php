<?php

declare(strict_types=1);

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
    public function getCrdateInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function setCrdateSetsCrdate(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setCrdate($date);

        self::assertSame(
            $date,
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function setCrdateWithNullSetsCrdate(): void
    {
        $this->subject->setCrdate(null);

        self::assertNull(
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function getTstampInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function setTstampSetsTstamp(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setTstamp($date);

        self::assertSame(
            $date,
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function setTstampWithNullSetsTstamp(): void
    {
        $this->subject->setTstamp(null);

        self::assertNull(
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function getSysLanguageUidInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getSysLanguageUid()
        );
    }

    /**
     * @test
     */
    public function setSysLanguageUidSetsSysLanguageUid(): void
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
    public function getL10nParentInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getL10nParent()
        );
    }

    /**
     * @test
     */
    public function setL10nParentSetsL10nParent(): void
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
    public function getStarttimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getStarttime()
        );
    }

    /**
     * @test
     */
    public function setStarttimeSetsStarttime(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setStarttime($date);

        self::assertSame(
            $date,
            $this->subject->getStarttime()
        );
    }

    /**
     * @test
     */
    public function setStarttimeWithNullSetsStarttime(): void
    {
        $this->subject->setStarttime(null);

        self::assertNull(
            $this->subject->getStarttime()
        );
    }

    /**
     * @test
     */
    public function getEndtimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getEndtime()
        );
    }

    /**
     * @test
     */
    public function setEndtimeSetsEndtime(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setEndtime($date);

        self::assertSame(
            $date,
            $this->subject->getEndtime()
        );
    }

    /**
     * @test
     */
    public function setEndtimeWithNullSetsEndtime(): void
    {
        $this->subject->setEndtime(null);

        self::assertNull(
            $this->subject->getEndtime()
        );
    }

    /**
     * @test
     */
    public function getHiddenInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function setHiddenSetsHidden(): void
    {
        $this->subject->setHidden(true);
        self::assertTrue(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function getDeletedInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getDeleted()
        );
    }

    /**
     * @test
     */
    public function setDeletedSetsDeleted(): void
    {
        $this->subject->setDeleted(true);
        self::assertTrue(
            $this->subject->getDeleted()
        );
    }
}
