<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use Doctrine\DBAL\Driver\Statement;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Event;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/*
 * Helper class to generate a path segment (slug) for an event record.
 * Used while executing the UpgradeWizard and saving records in frontend.
 */
class PathSegmentHelper
{
    /**
     * @var string
     */
    protected $tableName = 'tx_events2_domain_model_event';

    /**
     * @var string
     */
    protected $slugColumn = 'path_segment';

    /**
     * @var string
     */
    protected $titleColumn = 'title';

    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var array
     */
    protected $slugCache = [];

    public function __construct(ExtConf $extConf = null)
    {
        $this->extConf = $extConf ?? GeneralUtility::makeInstance(ExtConf::class);
    }

    public function generatePathSegment(array $baseRecord): string
    {
        $pathSegment = $this->getSlugHelper()->generate(
            $baseRecord,
            (int)$baseRecord['pid']
        );

        if ($this->extConf->getPathSegmentType() === 'realurl') {
            $pathSegment = $this->getUniqueValue((int)$baseRecord['uid'], $pathSegment);
        }

        return $pathSegment;
    }

    public function updatePathSegmentForEvent(Event $event): void
    {
        // First of all, we have to check, if an UID is available
        if (!$event->getUid()) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $persistenceManager = $objectManager->get(PersistenceManagerInterface::class);
            $persistenceManager->persistAll();
        }

        $event->setPathSegment(
            $this->generatePathSegment(
                $event->getBaseRecordForPathSegment()
            )
        );
    }

    protected function getUniqueValue(int $uid, string $slug): string
    {
        $statement = $this->getUniqueSlugStatement($uid, $slug);
        $counter = $this->slugCache[$slug] ?? 1;
        while ($statement->fetch()) {
            $newSlug = $slug . '-' . $counter;
            $statement->bindValue(1, $newSlug);
            $statement->execute();

            // Do not cache every slug, because of memory consumption. I think 5 is a good value to start caching.
            if ($counter > 5) {
                $this->slugCache[$slug] = $counter;
            }
            $counter++;
        }

        return $newSlug ?? $slug;
    }

    protected function getUniqueSlugStatement(int $uid, string $slug): Statement
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($this->tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    $this->slugColumn,
                    $queryBuilder->createPositionalParameter($slug, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createPositionalParameter($uid, Connection::PARAM_INT)
                )
            )
            ->execute();
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

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
