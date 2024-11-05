<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Event\GeneratePathSegmentEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\DataHandling\Model\RecordState;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This hook will be executed just while generating a slug with TYPO3 API (SlugHelper). Problem with this API is, that
 * a call to "generate" will not consider the uniqueness options in "eval". There are further API calls you have to
 * call to take this option into account. Thanks to this hook we solve that in one run.
 */
class SlugPostModifierHook
{
    private const TABLE = 'tx_events2_domain_model_event';
    private const FIELD = 'path_segment';

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array{
     *     slug: string,
     *     workspaceId: int,
     *     configuration: array<mixed>,
     *     record: array<mixed>,
     *     pid: int,
     *     prefix: string,
     *     tableName: string,
     *     fieldName: string
     * } $parameters
     * @param SlugHelper $slugHelper This is a SlugHelper with slightly modified generator options
     * @return string Will return the generated and unique slug. We will trim the slug before and if it is just "/" we return an empty string. Empty string in general means something went wrong.
     */
    public function modify(array $parameters, SlugHelper $slugHelper): string
    {
        // Prevent executing this Hook for tables of other extensions
        if (
            ($parameters['tableName'] ?? '') !== self::TABLE
            || ($parameters['fieldName'] ?? '') !== self::FIELD
        ) {
            return $parameters['slug'];
        }

        $newSlug = match ($this->getExtConf()->getPathSegmentType()) {
            'uid' => $this->getPathSegmentWithAdditionalUid($parameters),
            'realurl' => $this->getPathSegmentWithIncrement($parameters, $slugHelper),
            default => $this->getPathSegmentByEventListener($parameters, $slugHelper),
        };

        $newSlug = trim($newSlug);

        if ($newSlug === '/') {
            $this->logger->error('While importing event records the new generated slug is empty', $parameters);
            return '';
        }

        return $newSlug;
    }

    protected function getPathSegmentWithAdditionalUid(array $parameters): string
    {
        // Because of our SlugHelper with modified generator options the generated slug should already have the record
        // UID appended. So, it is already unique
        return (string)($parameters['slug'] ?? '');
    }

    protected function getPathSegmentWithIncrement(array $parameters, SlugHelper $slugHelper): string
    {
        // SlugHelper::buildSlugForUniqueInTable will automatically append an increment up to 100. After that
        // it will append a md5 hash value instead
        return $this->buildUniqueSlug($parameters, $slugHelper);
    }

    protected function getPathSegmentByEventListener(array $parameters, SlugHelper $slugHelper): string
    {
        /** @var GeneratePathSegmentEvent $generatePathSegmentEvent */
        $generatePathSegmentEvent = $this->eventDispatcher->dispatch(
            new GeneratePathSegmentEvent($parameters, $slugHelper),
        );

        if (($pathSegment = $generatePathSegmentEvent->getPathSegment()) === '') {
            $this->logger->error(
                'While importing event records the generated slug with your own EventListener returns an empty slug. '
                . 'We fall back to the TYPO3 generated slug',
                $parameters,
            );
            $pathSegment = $parameters['slug'];
        }

        return $pathSegment;
    }

    protected function buildUniqueSlug(array $parameters, SlugHelper $slugHelper): string
    {
        $recordState = $this->getRecordState($parameters);

        // Early return, if RecordState is empty
        if (!$recordState instanceof RecordState) {
            $this->logger->error(
                'While importing this event record there is no UID given. '
                . 'Please make sure you have stored the record before building a slug',
                $parameters,
            );
            return '';
        }

        $originalSlug = (string)($parameters['slug'] ?? '');
        $uniqueSlug = '';

        try {
            $uniqueSlug = $slugHelper->buildSlugForUniqueInTable(
                $originalSlug,
                $recordState,
            );
        } catch (SiteNotFoundException $e) {
        }

        return $uniqueSlug;
    }

    protected function getRecordState(array $parameters): ?RecordState
    {
        $baseRecord = (array)($parameters['record'] ?? []);
        $table = (string)($parameters['tableName'] ?? '');
        $pid = (int)($parameters['pid'] ?? 0);
        $uid = (int)($baseRecord['uid'] ?? 0);

        // The record has to be stored before.
        if ($uid === 0) {
            return null;
        }

        return RecordStateFactory::forName($table)->fromArray($baseRecord, $pid, $uid);
    }

    protected function getExtConf(): ExtConf
    {
        return GeneralUtility::makeInstance(ExtConf::class);
    }
}
