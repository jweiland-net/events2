<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks\Form;

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

/**
 * Prefill EXT:form elements with values from requested event record
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
            isset($_GET['tx_events2_management']['event'])
            && MathUtility::canBeInterpretedAsInteger($_GET['tx_events2_management']['event'])
            && ($eventRecord = $this->getEventRecord((int)$_GET['tx_events2_management']['event']))
            && $eventRecord !== []
        ) {
            $formElement->getRootForm()->addElementDefaultValue(
                'events2-event-uid',
                (int)$_GET['tx_events2_management']['event']
            );
            $this->setFormDefaultValues($formElement, $eventRecord);
        }
    }

    protected function setFormDefaultValues(AbstractFormElement $formElement, array $eventRecord): void
    {
        $properties = $formElement->getProperties();
        $defaultValue = null;

        if (
            isset($properties['dbMapping']['column'])
            && $properties['dbMapping']['column'] !== ''
        ) {
            $defaultValue = $eventRecord[$properties['dbMapping']['column']] ?? null;
        } elseif (
            isset(
                $properties['dbMapping']['relation']['table'],
                $properties['dbMapping']['relation']['expressions']
            )
            && $properties['dbMapping']['relation']['table'] !== ''
            && is_array($properties['dbMapping']['relation']['expressions'])
        ) {
            if (
                isset($properties['dbMapping']['relation']['labelColumn'])
                && $properties['dbMapping']['relation']['labelColumn'] !== ''
            ) {
                $defaultValue = $this->getLabel(
                    $formElement,
                    $properties['dbMapping']['relation']['table'],
                    $properties['dbMapping']['relation']['labelColumn'],
                    $eventRecord,
                    $properties['dbMapping']['relation']['expressions']
                );
            } elseif (
                isset($properties['dbMapping']['relation']['valueColumn'])
                && $properties['dbMapping']['relation']['valueColumn'] !== ''
            ) {
                $defaultValue = $this->getValues(
                    $properties['dbMapping']['relation']['table'],
                    $properties['dbMapping']['relation']['valueColumn'],
                    $eventRecord,
                    $properties['dbMapping']['relation']['expressions']
                );
            }
        }

        if ($defaultValue === null) {
            return;
        }

        if (isset($properties['dbMapping']['dataType'])) {
            if ($properties['dbMapping']['dataType'] === 'date') {
                $date = new \DateTime('@' . strtotime('@' . $defaultValue));
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                $defaultValue = $date->format('Y-m-d');
            } elseif ($properties['dbMapping']['dataType'] === 'binary') {
                $values = [];
                foreach ([1, 2, 4, 8, 16, 32, 64] as $key => $value) {
                    if ($defaultValue & 2 ** $key) {
                        $values[] = $value;
                    }
                }
                $defaultValue = $values;
            } elseif ($properties['dbMapping']['dataType'] === 'file') {
                $pos = (int)($properties['dbMapping']['position'] ?? 1);
                $defaultValue = $this->getFileReference($eventRecord['uid'], $pos);
            }
        }

        if ($defaultValue !== null) {
            $formElement->setDefaultValue($defaultValue);
        }
    }

    protected function getLabel(
        AbstractFormElement $formElement,
        string $table,
        string $labelColumn,
        array $eventRecord,
        array $expressions = []
    ): string {
        $queryBuilder = $this
            ->getQueryBuilderForTable($table)
            ->select('uid', $labelColumn);

        $this->resolveExpressions($queryBuilder, $eventRecord, $expressions);

        $record = $queryBuilder
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($record)) {
            return '';
        }

        $formElement->getRootForm()->addElementDefaultValue(
            'events2-' . $formElement->getIdentifier() . '-uid',
            (int)$record['uid']
        );

        return $record[$labelColumn];
    }

    protected function getValues(
        string $table,
        string $valueColumn,
        array $eventRecord,
        array $expressions = []
    ): array {
        $queryBuilder = $this
            ->getQueryBuilderForTable($table)
            ->select($valueColumn);

        $this->resolveExpressions($queryBuilder, $eventRecord, $expressions);
        $queryResult = $queryBuilder->executeQuery();

        $values = [];
        while ($record = $queryResult->fetchAssociative()) {
            $values[] = $record[$valueColumn];
        }

        return $values;
    }

    protected function resolveExpressions(
        QueryBuilder $queryBuilder,
        array $eventRecord,
        array $expressions
    ): void {
        $constraints = [];
        foreach ($expressions as $expression) {
            if (
                isset(
                    $expression['column'],
                    $expression['expression'],
                    $expression['value']
                )
                && method_exists($queryBuilder->expr(), $expression['expression'])
            ) {
                $value = $expression['value'];
                $stringExplodeValue = isset($expression['strExplodeValue']) && (int)$expression['strExplodeValue'] === 1;
                $intExplodeValue = isset($expression['intExplodeValue']) && (int)$expression['intExplodeValue'] === 1;

                if (str_contains($value, ':')) {
                    [$type, $column] = GeneralUtility::trimExplode(':', trim($value, '{}'));
                    if ($type === 'event') {
                        $value = $eventRecord[$column] ?? '';
                    }
                }

                $type = Connection::PARAM_STR;
                if ($stringExplodeValue) {
                    $value = GeneralUtility::trimExplode(',', $value, true);
                    $type = ArrayParameterType::STRING;
                } elseif ($intExplodeValue) {
                    $value = GeneralUtility::intExplode(',', $value, true);
                    $type = ArrayParameterType::INTEGER;
                }

                $constraints[] = call_user_func(
                    [$queryBuilder->expr(), $expression['expression']],
                    $expression['column'],
                    $queryBuilder->createNamedParameter($value, $type)
                );
            }
        }

        if ($constraints !== []) {
            $queryBuilder->where(...$constraints);
        }
    }

    protected function getFileReference(int $eventUid, int $position): ?FileReference
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_reference');
        $queryResult = $queryBuilder
            ->select('uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_events2_domain_model_event')
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('images')
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)
                )
            )
            ->orderBy('sorting_foreign', 'ASC')
            ->executeQuery();

        $coreReferences = [];
        $counter = 1;
        while ($coreReferenceRecord = $queryResult->fetchAssociative()) {
            $coreReferences[$counter] = $coreReferenceRecord['uid'];
            $counter++;
        }

        if (isset($coreReferences[$position])) {
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $coreReference = $resourceFactory->getFileReferenceObject((int)$coreReferences[$position]);
            if ($coreReference instanceof \TYPO3\CMS\Core\Resource\FileReference) {
                $extbaseFileReference = GeneralUtility::makeInstance(FileReference::class);
                $extbaseFileReference->setOriginalResource($coreReference);

                return $extbaseFileReference;
            }
        }

        return null;
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
                    $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

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
