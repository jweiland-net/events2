<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use Doctrine\DBAL\Statement;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Event\GeneratePathSegmentEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/*
 * Helper class to generate a path segment (slug) for an event record.
 * Used while executing the UpgradeWizard and saving records in frontend.
 */
class PathSegmentHelper
{
    protected string $tableName = 'tx_events2_domain_model_event';

    protected string $slugColumn = 'path_segment';

    protected string $titleColumn = 'title';

    protected array $slugCache = [];

    public function __construct(
        protected readonly ExtConf $extConf,
        protected readonly EventDispatcher $eventDispatcher
    ) {
    }

    public function generatePathSegment(array $baseRecord): string
    {
        if ($this->extConf->getPathSegmentType() === 'empty') {
            /** @var GeneratePathSegmentEvent $generatePathSegmentEvent */
            $generatePathSegmentEvent = $this->eventDispatcher->dispatch(
                new GeneratePathSegmentEvent($baseRecord)
            );
            $pathSegment = $generatePathSegmentEvent->getPathSegment();
            if ($pathSegment === '' || $pathSegment === '/') {
                throw new \Exception(
                    'You have configured "empty" in Extension Settings for path segment generation. Please check your configured Event or change path generation to "realurl" or "uid"',
                    1623682407
                );
            }
        } else {
            // We configure path segment type "uid" in getSlugHelper()
            $pathSegment = $this->getSlugHelper()->generate(
                $baseRecord,
                (int)$baseRecord['pid']
            );

            if ($this->extConf->getPathSegmentType() === 'realurl') {
                $pathSegment = $this->getUniqueValue((int)$baseRecord['uid'], $pathSegment);
            }
        }

        return $pathSegment;
    }

    public function updatePathSegmentForEvent(Event $event): void
    {
        // First of all, we have to check, if an UID is available
        if (!$event->getUid()) {
            $this->getPersistenceManager()->persistAll();
        }

        $event->setPathSegment(
            $this->generatePathSegment(
                $event->getBaseRecordForPathSegment()
            )
        );
    }

    protected function getUniqueValue(int $uid, string $slug): string
    {
        $newSlug = null;
        $statement = $this->getUniqueSlugStatement($uid, $slug);
        $queryResult = $statement->executeQuery();
        if ($queryResult->fetchOne()) {
            for ($counter = 1; $counter <= 100; $counter++) {
                $queryResult->free();
                $newSlug = $slug . '-' . $counter;
                $statement->bindValue(1, $newSlug);
                $result = $statement->executeQuery();
                if (!$result->fetchOne()) {
                    break;
                }
            }
            $result->free();
        }

        return $newSlug ?? $slug;
    }

    /**
     * Returns a query statement to test, if given slug is already in database. If yes, we will use the statement
     * again, to check against various slug-[counter]. If no, we will just return the just generated slug.
     */
    protected function getUniqueSlugStatement(int $uid, string $slug): Statement
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    $this->slugColumn,
                    $queryBuilder->createPositionalParameter($slug)
                ),
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createPositionalParameter($uid, Connection::PARAM_INT)
                )
            )
            ->prepare();
    }

    protected function getSlugHelper(): SlugHelper
    {
        $config = $GLOBALS['TCA'][$this->tableName]['columns'][$this->slugColumn]['config'];

        if ($this->extConf->getPathSegmentType() === 'uid') {
            $config['generatorOptions']['fields'] = ['title', 'uid'];
        }

        return GeneralUtility::makeInstance(
            SlugHelper::class,
            $this->tableName,
            $this->slugColumn,
            $config
        );
    }

    protected function getPersistenceManager(): PersistenceManagerInterface
    {
        return GeneralUtility::makeInstance(PersistenceManagerInterface::class);
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
