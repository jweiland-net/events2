<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hook\Form;

use Doctrine\DBAL\ArrayParameterType;
use JWeiland\Events2\Converter\RteContentConverter;
use JWeiland\Events2\Service\ManagementAccessService;
use JWeiland\Events2\Traits\Typo3RequestTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

/**
 * Prefill EXT:form elements with values from requested event record
 */
readonly class PrefillForEditUsageHook
{
    use Typo3RequestTrait;

    /**
     * Allowed values for "expression" of "dbMapping.relation.expressions"
     */
    private const ALLOWED_EXPRESSIONS = ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'in', 'notIn', 'inSet'];

    public function __construct(
        private PageRepository $pageRepository,
        private ManagementAccessService $managementAccessService,
        private RteContentConverter $rteContentConverter = new RteContentConverter(),
    ) {}

    /**
     * This method will be called by Form Framework.
     * It was checked by method_exists() before.
     *
     * The form could be used outside the ManagementController (e.g. as form
     * content element). So the access is checked here, too. Else everyone
     * could read hidden events of other organizers.
     */
    public function afterBuildingFinished(RenderableInterface $formElement): void
    {
        $request = $this->getTypo3Request();
        $eventUid = $this->managementAccessService->getEventUid($request);
        if (
            !$formElement instanceof AbstractFormElement
            || $eventUid === 0
            || $this->managementAccessService->getDenialReason($request) !== ''
        ) {
            return;
        }

        $eventRecord = $this->getEventRecord($eventUid);
        if ($eventRecord !== []) {
            $formElement->getRootForm()->addElementDefaultValue('events2-event-uid', $eventUid);
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

            // The column value (e.g. a foreign key UID) is the value to be submitted and must stay untouched.
            // If a relation with labelColumn is configured additionally, resolve it purely for display purposes
            // and expose it as a generic "resolvedLabel" property instead of overwriting the default value.
            if (
                isset(
                    $properties['dbMapping']['relation']['table'],
                    $properties['dbMapping']['relation']['labelColumn'],
                    $properties['dbMapping']['relation']['expressions'],
                )
                && $properties['dbMapping']['relation']['table'] !== ''
                && $properties['dbMapping']['relation']['labelColumn'] !== ''
                && is_array($properties['dbMapping']['relation']['expressions'])
            ) {
                $formElement->setProperty(
                    'resolvedLabel',
                    $this->getLabel(
                        $formElement,
                        $properties['dbMapping']['relation']['table'],
                        $properties['dbMapping']['relation']['labelColumn'],
                        $eventRecord,
                        $properties['dbMapping']['relation']['expressions'],
                    ),
                );
            }
        } elseif (
            isset(
                $properties['dbMapping']['relation']['table'],
                $properties['dbMapping']['relation']['expressions'],
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
                    $properties['dbMapping']['relation']['expressions'],
                );
            } elseif (
                isset($properties['dbMapping']['relation']['valueColumn'])
                && $properties['dbMapping']['relation']['valueColumn'] !== ''
            ) {
                $defaultValue = $this->getValues(
                    $properties['dbMapping']['relation']['table'],
                    $properties['dbMapping']['relation']['valueColumn'],
                    $eventRecord,
                    $properties['dbMapping']['relation']['expressions'],
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
            } elseif ($properties['dbMapping']['dataType'] === 'plainText') {
                $defaultValue = $this->convertHtmlToPlainText((string)$defaultValue);
            } elseif ($properties['dbMapping']['dataType'] === 'richText') {
                // The rich text editor gets the stored HTML. Fluid escapes it for the textarea.
                $defaultValue = (string)$defaultValue;
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
        array $expressions = [],
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

        $record = $this->pageRepository->getLanguageOverlay($table, $record);

        if (!is_array($record)) {
            return '';
        }

        $formElement->getRootForm()->addElementDefaultValue(
            'events2-' . $formElement->getIdentifier() . '-uid',
            (int)$record['uid'],
        );

        return $record[$labelColumn];
    }

    protected function getValues(
        string $table,
        string $valueColumn,
        array $eventRecord,
        array $expressions = [],
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

    /**
     * Adds the "expressions" of the "dbMapping.relation" of a form element
     * as constraints. A value like "{event:uid}" is replaced by the column
     * of the event record.
     */
    protected function resolveExpressions(
        QueryBuilder $queryBuilder,
        array $eventRecord,
        array $expressions,
    ): void {
        $constraints = [];
        foreach ($expressions as $expression) {
            if (
                isset($expression['column'], $expression['expression'], $expression['value'])
                && in_array($expression['expression'], self::ALLOWED_EXPRESSIONS, true)
            ) {
                $constraints[] = $queryBuilder->expr()->{$expression['expression']}(
                    $expression['column'],
                    $this->createValueForExpression($queryBuilder, $expression, $eventRecord),
                );
            }
        }

        if ($constraints !== []) {
            $queryBuilder->where(...$constraints);
        }
    }

    /**
     * SQLite does not support placeholders for inSet(). So its value is
     * added as quoted literal. All other values are added as placeholders.
     */
    protected function createValueForExpression(QueryBuilder $queryBuilder, array $expression, array $eventRecord): string
    {
        $value = $this->resolveValue((string)$expression['value'], $eventRecord);

        if ($expression['expression'] === 'inSet') {
            return $queryBuilder->quote($value);
        }

        if ((int)($expression['strExplodeValue'] ?? 0) === 1) {
            $values = GeneralUtility::trimExplode(',', $value, true);

            return $queryBuilder->createNamedParameter($values, ArrayParameterType::STRING);
        }

        if ((int)($expression['intExplodeValue'] ?? 0) === 1) {
            $values = GeneralUtility::intExplode(',', $value, true);

            return $queryBuilder->createNamedParameter($values, ArrayParameterType::INTEGER);
        }

        return $queryBuilder->createNamedParameter($value);
    }

    protected function resolveValue(string $value, array $eventRecord): string
    {
        if (!str_starts_with($value, '{event:')) {
            return $value;
        }

        return (string)($eventRecord[substr($value, 7, -1)] ?? '');
    }

    protected function getFileReference(int $eventUid, int $position): ?FileReference
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_reference');
        $queryResult = $queryBuilder
            ->select('uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_events2_domain_model_event'),
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('images'),
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT),
                ),
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

    /**
     * There is no RTE for this element in frontend, so the plain textarea must
     * show the HTML of the backend RTE or of SaveEventFinisher as plain text.
     *
     * @see RteContentConverter::convertHtmlToPlainText()
     */
    protected function convertHtmlToPlainText(string $value): string
    {
        return $this->rteContentConverter->convertHtmlToPlainText($value);
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
                    $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT),
                ),
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
