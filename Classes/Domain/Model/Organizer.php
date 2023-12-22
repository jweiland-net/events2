<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use JWeiland\Events2\Domain\Traits\Typo3PropertiesTrait;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;

/*
 * This class contains all getter and setters for an Organizer.
 */
class Organizer extends AbstractEntity
{
    use Typo3PropertiesTrait;

    /**
     * @var string
     */
    protected $organizer = '';

    /**
     * @var \JWeiland\Events2\Domain\Model\Link
     * @Extbase\ORM\Lazy
     */
    protected $link;

    public function getOrganizer(): string
    {
        return $this->organizer;
    }

    public function setOrganizer(string $organizer): void
    {
        $this->organizer = $organizer;
    }

    public function getLink(): ?Link
    {
        return $this->link instanceof LazyLoadingProxy
            ? $this->link->_loadRealInstance()
            : $this->link;
    }

    public function setLink(?Link $link = null): void
    {
        $this->link = $link;
    }
}
