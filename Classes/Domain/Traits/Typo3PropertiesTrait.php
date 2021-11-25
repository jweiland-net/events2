<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Traits;

/*
 * A Trait which contains all getters and setters for TYPO3 columns
 */
trait Typo3PropertiesTrait
{
    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $crdate;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $tstamp;

    protected int $l10nParent= 0;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $starttime;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $endtime;

    protected bool $hidden = false;

    protected bool $deleted = false;

    protected int $cruserId = 0;

    /**
     * @return \DateTime|\DateTimeImmutable|null
     */
    public function getCrdate(): ?\DateTimeInterface
    {
        return $this->crdate;
    }

    /**
     * @param \DateTime|\DateTimeImmutable|null $crdate
     */
    public function setCrdate(?\DateTimeInterface $crdate): void
    {
        $this->crdate = $crdate;
    }

    /**
     * @return \DateTime|\DateTimeImmutable|null
     */
    public function getTstamp(): ?\DateTimeInterface
    {
        return $this->tstamp;
    }

    /**
     * @param \DateTime|\DateTimeImmutable|null $tstamp
     */
    public function setTstamp(?\DateTimeInterface $tstamp): void
    {
        $this->tstamp = $tstamp;
    }

    public function getSysLanguageUid(): ?int
    {
        return $this->_languageUid;
    }

    public function setSysLanguageUid(int $sysLanguageUid = null): void
    {
        $this->_languageUid = $sysLanguageUid;
    }

    public function getL10nParent(): int
    {
        return $this->l10nParent;
    }

    public function setL10nParent(int $l10nParent): void
    {
        $this->l10nParent = $l10nParent;
    }

    /**
     * @return \DateTime|\DateTimeImmutable|null
     */
    public function getStarttime(): ?\DateTimeInterface
    {
        return $this->starttime;
    }

    /**
     * @param \DateTime|\DateTimeImmutable|null $starttime
     */
    public function setStarttime(?\DateTimeInterface $starttime): void
    {
        $this->starttime = $starttime;
    }

    /**
     * @return \DateTime|\DateTimeImmutable|null
     */
    public function getEndtime(): ?\DateTimeInterface
    {
        return $this->endtime;
    }

    /**
     * @param \DateTime|\DateTimeImmutable|null $endtime
     */
    public function setEndtime(?\DateTimeInterface $endtime): void
    {
        $this->endtime = $endtime;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function getCruserId(): int
    {
        return $this->cruserId;
    }

    public function setCruserId(int $cruserId): void
    {
        $this->cruserId = $cruserId;
    }
}
