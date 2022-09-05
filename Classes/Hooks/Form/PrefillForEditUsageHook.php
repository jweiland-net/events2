<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks\Form;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

/*
 * Prefill EXT:form element of type checkboxes with categories from database
 */
class PrefillForEditUsageHook
{
    /**
     * This method will be called by Form Framework.
     * It was checked by method_exists() before
     */
    public function afterBuildingFinished(RenderableInterface $formElement): void
    {
        if (!$formElement instanceof AbstractFormElement) {
            return;
        }

        if (
            isset($_GET['tx_events2_events']['event'])
            && MathUtility::canBeInterpretedAsInteger($_GET['tx_events2_events']['event'])
            && ($eventRecord = $this->getEventRecord((int)$_GET['tx_events2_events']['event']))
            && $eventRecord !== []
        ) {
            $this->setFormDefaultValues($formElement, $eventRecord);
        }
    }

    protected function setFormDefaultValues(AbstractFormElement $formElement, array $eventRecord): void
    {
        switch ($formElement->getIdentifier()) {
            case 'choose-event-type':
                $formElement->setDefaultValue($eventRecord['event_type'] ?? 'single');
                break;
            case 'title':
                $formElement->setDefaultValue($eventRecord['title'] ?? '');
                break;
            case 'event-begin':
                $date = new \DateTime('@' . strtotime('@' . $eventRecord['event_begin']));
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                $formElement->setDefaultValue($date->format('Y-m-d'));
                break;
            case 'event-end':
                $date = new \DateTime('@' . strtotime('@' . $eventRecord['event_end']));
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                $formElement->setDefaultValue($date->format('Y-m-d'));
                break;
            case 'recurring-end':
                $date = new \DateTime('@' . strtotime('@' . $eventRecord['recurring_end']));
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                $formElement->setDefaultValue($date->format('Y-m-d'));
                break;
            case 'time-begin':
                $timeRecord = $this->getTimeRecord($eventRecord['uid'], 'event_time');
                $formElement->setDefaultValue($timeRecord['time_begin'] ?? '');
                break;
            case 'short-description':
                $formElement->setDefaultValue($eventRecord['teaser'] ?? '');
                break;
            case 'detail-description':
                $formElement->setDefaultValue($eventRecord['detail_information'] ?? '');
                break;
            case 'ticket-link':
                $linkRecord = $this->getLinkRecord((int)$eventRecord['ticket_link']);
                $formElement->setDefaultValue($linkRecord['link'] ?? '');
                break;
            case 'event-location':
                $formElement->setDefaultValue($eventRecord['location']);
                break;
            case 'download-link':
                $linkUids = GeneralUtility::trimExplode(',', $eventRecord['download_links'], true);
                reset($linkUids);
                if ($linkUids !== []) {
                    $linkRecord = $this->getLinkRecord((int)current($linkUids));
                    $formElement->setDefaultValue($linkRecord['link'] ?? '');
                }
                break;
            case 'youtube-link':
                $linkRecord = $this->getLinkRecord((int)$eventRecord['video_link']);
                $formElement->setDefaultValue($linkRecord['link'] ?? '');
                break;
            case 'categories':
                $formElement->setDefaultValue($this->getCategories($eventRecord['uid']));
                break;
            case 'weekday':
                $formElement->setDefaultValue($this->getWeekdays($eventRecord['weekday']));
                break;
        }
    }

    protected function getCategories(int $eventUid): array
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_category_record_mm');
        $statement = $queryBuilder
            ->select('uid_local')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_events2_domain_model_event', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('categories', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                )
            )
            ->execute();

        $categories = [];
        while ($categoryRecord = $statement->fetch()) {
            $categories[] = $categoryRecord['uid_local'];
        }

        return $categories;
    }

    protected function getWeekdays(int $weekday): array
    {
        $weekdays = [];
        foreach ([1, 2, 4, 8, 16, 32, 64] as $key => $value) {
            if ($weekday & 2 ** $key) {
                $weekdays[] = $value;
            }
        }

        return $weekdays;
    }

    protected function getTimeRecord(int $eventUid, string $column): array
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_time');
        $timeRecord = $queryBuilder
            ->select('uid', 'time_begin')
            ->where(
                $queryBuilder->expr()->eq(
                    'event',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'type',
                    $queryBuilder->createNamedParameter($column, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();

        return is_array($timeRecord) ? $timeRecord : [];
    }

    protected function getLinkRecord(int $linkUid): array
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_link');
        $linkRecord = $queryBuilder
            ->select('uid', 'title', 'link')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($linkUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        return is_array($linkRecord) ? $linkRecord : [];
    }

    protected function getEventRecord(int $eventUid): array
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_event');

        // An admin needs possibility to edit hidden events
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $record = $queryBuilder
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        return $record ?: [];
    }

    protected function getQueryBuilderForTable(string $table): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->select('*')
            ->from($table);

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
