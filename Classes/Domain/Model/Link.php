<?php

declare(strict_types = 1);

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
 * This class contains all getter and setters for a Link.
 */
class Link extends AbstractEntity
{
    use Typo3PropertiesTrait;

    /**
     * @var string
     */
    protected $link = '';

    /**
     * @var string
     */
    protected $title = 'Video';

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link)
    {
        $this->link = $link;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }
}
