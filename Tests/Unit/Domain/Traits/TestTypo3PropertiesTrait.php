<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Traits;

use PHPUnit\Framework\Attributes\Test;

/**
 * Test case.
 */
trait TestTypo3PropertiesTrait
{
    #[Test]
    public function getCrdateInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getCrdate(),
        );
    }

    #[Test]
    public function setCrdateSetsCrdate(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setCrdate($date);

        self::assertSame(
            $date,
            $this->subject->getCrdate(),
        );
    }

    #[Test]
    public function setCrdateWithNullSetsCrdate(): void
    {
        $this->subject->setCrdate(null);

        self::assertNull(
            $this->subject->getCrdate(),
        );
    }

    #[Test]
    public function getTstampInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTstamp(),
        );
    }

    #[Test]
    public function setTstampSetsTstamp(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setTstamp($date);

        self::assertSame(
            $date,
            $this->subject->getTstamp(),
        );
    }

    #[Test]
    public function setTstampWithNullSetsTstamp(): void
    {
        $this->subject->setTstamp(null);

        self::assertNull(
            $this->subject->getTstamp(),
        );
    }

    #[Test]
    public function getSysLanguageUidInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getSysLanguageUid(),
        );
    }

    #[Test]
    public function setSysLanguageUidSetsSysLanguageUid(): void
    {
        $this->subject->setSysLanguageUid(123456);

        self::assertSame(
            123456,
            $this->subject->getSysLanguageUid(),
        );
    }

    #[Test]
    public function getL10nParentInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getL10nParent(),
        );
    }

    #[Test]
    public function setL10nParentSetsL10nParent(): void
    {
        $this->subject->setL10nParent(123456);

        self::assertSame(
            123456,
            $this->subject->getL10nParent(),
        );
    }

    #[Test]
    public function getStarttimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getStarttime(),
        );
    }

    #[Test]
    public function setStarttimeSetsStarttime(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setStarttime($date);

        self::assertSame(
            $date,
            $this->subject->getStarttime(),
        );
    }

    #[Test]
    public function setStarttimeWithNullSetsStarttime(): void
    {
        $this->subject->setStarttime(null);

        self::assertNull(
            $this->subject->getStarttime(),
        );
    }

    #[Test]
    public function getEndtimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getEndtime(),
        );
    }

    #[Test]
    public function setEndtimeSetsEndtime(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setEndtime($date);

        self::assertSame(
            $date,
            $this->subject->getEndtime(),
        );
    }

    #[Test]
    public function setEndtimeWithNullSetsEndtime(): void
    {
        $this->subject->setEndtime(null);

        self::assertNull(
            $this->subject->getEndtime(),
        );
    }

    #[Test]
    public function getHiddenInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getHidden(),
        );
    }

    #[Test]
    public function setHiddenSetsHidden(): void
    {
        $this->subject->setHidden(true);
        self::assertTrue(
            $this->subject->getHidden(),
        );
    }

    #[Test]
    public function getDeletedInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getDeleted(),
        );
    }

    #[Test]
    public function setDeletedSetsDeleted(): void
    {
        $this->subject->setDeleted(true);
        self::assertTrue(
            $this->subject->getDeleted(),
        );
    }
}
