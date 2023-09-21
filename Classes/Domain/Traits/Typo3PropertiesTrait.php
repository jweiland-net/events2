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
    protected ?\DateTimeImmutable $crdate = null;

    protected ?\DateTimeImmutable $tstamp = null;

    protected int $l10nParent= 0;

    protected ?\DateTimeImmutable $starttime = null;

    protected ?\DateTimeImmutable $endtime = null;

    protected bool $hidden = false;

    protected bool $deleted = false;

    public function getCrdate(): ?\DateTimeImmutable
    {
        return $this->crdate;
    }

    public function setCrdate(?\DateTimeImmutable $crdate): void
    {
        $this->crdate = $crdate;
    }

    public function getTstamp(): ?\DateTimeImmutable
    {
        return $this->tstamp;
    }

    public function setTstamp(?\DateTimeImmutable $tstamp): void
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

    public function getStarttime(): ?\DateTimeImmutable
    {
        return $this->starttime;
    }

    public function setStarttime(?\DateTimeImmutable $starttime): void
    {
        $this->starttime = $starttime;
    }

    public function getEndtime(): ?\DateTimeImmutable
    {
        return $this->endtime;
    }

    public function setEndtime(?\DateTimeImmutable $endtime): void
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
}
