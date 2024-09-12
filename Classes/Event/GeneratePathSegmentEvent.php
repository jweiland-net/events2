<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use TYPO3\CMS\Core\DataHandling\SlugHelper;

class GeneratePathSegmentEvent
{
    protected array $parameters = [];

    protected array $baseRecord = [];

    protected string $pathSegment = '';

    protected SlugHelper $slugHelper;

    public function __construct(array $parameters, SlugHelper $slugHelper)
    {
        $this->parameters = $parameters;
        $this->baseRecord = (array)($parameters['record'] ?? []);
        $this->slugHelper = $slugHelper;
    }

    /**
     * @return array{
     *     slug: string,
     *     workspaceId: int,
     *     configuration: array<mixed>,
     *     record: array<mixed>,
     *     pid: int,
     *     prefix: string,
     *     tableName: string,
     *     fieldName: string
     * }
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @deprecated Please use 'record' from getParameters
     */
    public function getBaseRecord(): array
    {
        return $this->baseRecord;
    }

    public function getSlugHelper(): SlugHelper
    {
        return $this->slugHelper;
    }

    public function getPathSegment(): string
    {
        return $this->pathSegment;
    }

    public function setPathSegment(string $pathSegment): void
    {
        $this->pathSegment = $pathSegment;
    }
}
