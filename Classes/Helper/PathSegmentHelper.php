<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use Doctrine\DBAL\Exception;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Helper\Exception\NoUniquePathSegmentException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Helper class to generate a path segment (slug) for an event record.
 * Used while executing the UpgradeWizard and saving records in frontend.
 */
class PathSegmentHelper
{
    protected const TABLE = 'tx_events2_domain_model_event';

    protected const SLUG_COLUMN = 'path_segment';

    public function __construct(
        protected readonly EventDispatcher $eventDispatcher,
        protected readonly PersistenceManagerInterface $persistenceManager,
        protected readonly QueryBuilder $queryBuilder,
        protected readonly ExtConf $extConf,
    ) {}

    /**
     * @param array $baseRecord Please make sure that the given record is stored, so that contained UID column is not 0
     * @throws NoUniquePathSegmentException
     */
    public function generatePathSegment(array $baseRecord): string
    {
        // We don't check for stored $baseRecord here. We do that in our modifier hook. It will be logged there
        // and an empty path segment will be returned.

        // Normally "generate" will not build unique slugs, but because of our registered modifier hook it will.
        $uniquePathSegment = $this->getSlugHelper()->generate(
            $baseRecord,
            (int)($baseRecord['pid'] ?? 0),
        );

        if ($uniquePathSegment === '') {
            throw new NoUniquePathSegmentException(
                'Generated path segment is not unique, please have a look into logs for more details',
                1726125713,
            );
        }

        return $uniquePathSegment;
    }

    public function updatePathSegmentForEvent(Event $event): void
    {
        // We have to make sure we are working with stored records here. Column "uid" is not 0.
        if (!$event->getUid()) {
            if ($this->persistenceManager->isNewObject($event)) {
                $this->persistenceManager->add($event);
            }
            $this->persistenceManager->persistAll();
        }

        $event->setPathSegment(
            $this->generatePathSegment(
                $this->getEventRecord($event->getUid()),
            ),
        );

        // Persist updated path segment
        $this->persistenceManager->update($event);
        $this->persistenceManager->persistAll();
    }

    /**
     * For generating unique slugs the SlugHelper needs specific TYPO3 internal (workspace/language) columns which we
     * do not provide within our Event model. That's why we do an additional select here.
     */
    protected function getEventRecord(int $eventUid): array
    {
        // Event is hidden while creation. Remove enableFields, except deleted
        $queryBuilder = $this->queryBuilder;
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        try {
            $queryResult = $queryBuilder
                ->select('*')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT),
                    ),
                )
                ->executeQuery();

            return $queryResult->fetchAssociative() ?: [];
        } catch (Exception $e) {
        }

        return [];
    }

    /**
     * Here you get a SlugHelper with a modified version of the generator options of TCA.
     * For TCE forms there is already a check for uniqueness in path_segment while storing a record.
     * But while importing or upgrading records with an UpgradeWizard there is no path_segment. We have to build
     * one on our own. For this special case we provide you 3 options in extension settings:
     *
     * - empty (default): You, as a Dev. have to use an EventListener to build the path_segment on your own
     * - uid: We use TYPO3 API to build the slug, and we append the record uid to this slug: [title]-[uid]
     * - realurl: We use TYPO3 API to build the slug, and we append an inkrement to this slug: [title]-[1, 2, 3, 4, ...]
     */
    protected function getSlugHelper(): SlugHelper
    {
        $config = $GLOBALS['TCA'][self::TABLE]['columns'][self::SLUG_COLUMN]['config'];

        $config['generatorOptions']['postModifiers']['events2-post-modifier']
            = \JWeiland\Events2\Hooks\SlugPostModifierHook::class . '->modify';

        // Make sure column "uid" is appended in list of generator fields, if "uid" is set in extension settings
        if (
            $this->extConf->getPathSegmentType() === 'uid'
            && !in_array('uid', $config['generatorOptions']['fields'], true)
        ) {
            $config['generatorOptions']['fields'][] = 'uid';
        }

        return GeneralUtility::makeInstance(
            SlugHelper::class,
            self::TABLE,
            self::SLUG_COLUMN,
            $config,
        );
    }
}
