<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use JWeiland\Events2\Domain\Traits\Typo3PropertiesTrait;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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
     */
    protected $link;

    public function getOrganizer(): string
    {
        return $this->organizer;
    }

    public function setOrganizer(string $organizer)
    {
        $this->organizer = $organizer;
    }

    public function getLink(): ?Link
    {
        return $this->link;
    }

    public function setLink(?Link $link = null)
    {
        $this->link = $link;
    }
}
