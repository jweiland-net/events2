<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Finisher;

use JWeiland\Events2\Domain\Repository\UserRepository;
use JWeiland\Events2\Helper\PathSegmentHelper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

/**
 * Finisher to save form values as event record incl. related records like time and images
 * Currently, needed by EXT:form to store new event records in FE
 */
class SaveEventFinisher extends AbstractFinisher
{
    /**
     * @var array
     */
    protected $defaultOptions = [
        'pid' => 'tx_formtools_requests',
    ];

    /**
     * @var Connection
     */
    protected $databaseConnection;

    /**
     * @var PathSegmentHelper
     */
    protected $pathSegmentHelper;

    public function injectPathSegmentHelper(PathSegmentHelper $pathSegmentHelper): void
    {
        $this->pathSegmentHelper = $pathSegmentHelper;
    }

    /**
     * Executes this finisher
     *
     * @see AbstractFinisher::execute()
     * @throws FinisherException
     */
    protected function executeInternal()
    {
        $options = [];
        if (isset($this->options['table'])) {
            $options[] = $this->options;
        } else {
            $options = $this->options;
        }

        foreach ($options as $optionKey => $option) {
            $this->options = $option;
            $this->process($optionKey);
        }
    }

    /**
     * Perform the current database operation
     *
     * @param int $iterationCount
     */
    protected function process(int $iterationCount)
    {
        $this->throwExceptionOnInconsistentConfiguration();

        $table = $this->parseOption('table');
        $table = is_string($table) ? $table : '';
        $elementsConfiguration = $this->parseOption('elements');
        $elementsConfiguration = is_array($elementsConfiguration) ? $elementsConfiguration : [];
        $databaseColumnMappingsConfiguration = $this->parseOption('databaseColumnMappings');

        $this->databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        $databaseData = [];
        foreach ($databaseColumnMappingsConfiguration as $databaseColumnName => $databaseColumnConfiguration) {
            $value = $this->parseOption('databaseColumnMappings.' . $databaseColumnName . '.value');
            if (
                empty($value)
                && $databaseColumnConfiguration['skipIfValueIsEmpty'] === true
            ) {
                continue;
            }

            $databaseData[$databaseColumnName] = $value;
        }

        $databaseData = $this->prepareData($elementsConfiguration, $databaseData);

        if ($table === 'sys_category_record_mm' && ($databaseData['uid_foreign'] ?? false)) {
            $this->saveDataForCategories($databaseData, $table, $iterationCount);
        } elseif ($table === 'tx_events2_event_organizer_mm' && ($databaseData['uid_local'] ?? false)) {
            $this->saveDataForOrganizers($databaseData, $table, $iterationCount);
        } elseif ($table === 'sys_file_reference' && !isset($databaseData['uid_local'])) {
            $this->finisherContext->getFinisherVariableProvider()->add(
                $this->shortFinisherIdentifier,
                'insertedUids.' . $iterationCount,
                0
            );
        } elseif ($table === 'tx_events2_domain_model_link' && isset($databaseData['link']) && $databaseData['link'] === '') {
            $this->finisherContext->getFinisherVariableProvider()->add(
                $this->shortFinisherIdentifier,
                'insertedUids.' . $iterationCount,
                0
            );
        } else {
            $this->saveToDatabase($databaseData, $table, $iterationCount);
        }
    }

    protected function prepareData(array $elementsConfiguration, array $databaseData): array
    {
        foreach ($this->getFormValues() as $elementIdentifier => $elementValue) {
            if (
                ($elementValue === null || $elementValue === '')
                && isset($elementsConfiguration[$elementIdentifier]['skipIfValueIsEmpty'])
                && $elementsConfiguration[$elementIdentifier]['skipIfValueIsEmpty'] === true
            ) {
                continue;
            }

            $element = $this->getElementByIdentifier($elementIdentifier);
            if (
                !isset($elementsConfiguration[$elementIdentifier]['mapOnDatabaseColumn'])
                || !$element instanceof FormElementInterface
            ) {
                continue;
            }

            if ($elementValue instanceof FileReference) {
                $elementValue = $elementValue->getOriginalResource()->getProperty('uid_local');
            } elseif ($elementsConfiguration[$elementIdentifier]['useBinary'] ?? false) {
                $elementValue = (int)array_sum(array_map('intval', $elementValue));
            } elseif (is_array($elementValue)) {
                $elementValue = implode(',', $elementValue);
            } elseif ($elementValue instanceof \DateTimeInterface) {
                $format = $elementsConfiguration[$elementIdentifier]['dateFormat'] ?? 'U';
                $elementValue = $elementValue->format($format);
            }

            $databaseData[$elementsConfiguration[$elementIdentifier]['mapOnDatabaseColumn']] = $elementValue;
        }

        return $databaseData;
    }

    /**
     * Save or insert the values from $databaseData into the table $table
     */
    protected function saveToDatabase(array $databaseData, string $table, int $iterationCount): void
    {
        if (
            $table === 'tx_events2_domain_model_time'
            && (
                (
                    array_key_exists('time_begin', $databaseData)
                    && $databaseData['time_begin'] === ''
                )
                || !array_key_exists('time_begin', $databaseData)
            )
        ) {
            // ToDo: If we implement editing of records in FE in future, we should check for empty time and delete existing time record
            $this->finisherContext->getFinisherVariableProvider()->add(
                $this->shortFinisherIdentifier,
                'insertedUids.' . $iterationCount,
                0
            );
            return;
        }

        if (!empty($databaseData)) {
            if ($this->options['mode'] === 'update') {
                $whereClause = $this->options['whereClause'];
                foreach ($whereClause as $columnName => $columnValue) {
                    $whereClause[$columnName] = $this->parseOption('whereClause.' . $columnName);
                }
                $this->databaseConnection->update(
                    $table,
                    $databaseData,
                    $whereClause
                );
            } else {
                $this->databaseConnection->insert($table, $databaseData);
                $insertedUid = (int)$this->databaseConnection->lastInsertId($table);
                $this->finisherContext->getFinisherVariableProvider()->add(
                    $this->shortFinisherIdentifier,
                    'insertedUids.' . $iterationCount,
                    $insertedUid
                );

                // Update slug for event record
                if (
                    $table === 'tx_events2_domain_model_event'
                    && array_key_exists('title', $databaseData)
                    && $databaseData['title'] !== ''
                ) {
                    $databaseData['uid'] = $insertedUid;
                    $this->databaseConnection->update(
                        'tx_events2_domain_model_event',
                        [
                            'path_segment' => $this->pathSegmentHelper->generatePathSegment($databaseData)
                        ],
                        [
                            'uid' => $insertedUid
                        ]
                    );
                }
            }
        }
    }

    protected function saveDataForCategories(array $databaseData, string $table, int $iterationCount): void
    {
        // Delete previously stored categories
        $this->databaseConnection->delete(
            $table,
            [
                'uid_foreign' => $databaseData['uid_foreign']
            ]
        );

        // Store new category relations
        $sorting = 0;
        $categories = $databaseData['categories'];
        unset($databaseData['categories']);
        foreach (GeneralUtility::intExplode(',', $categories, true) as $categoryUid) {
            $databaseData['uid_local'] = $categoryUid;
            $databaseData['sorting_foreign'] = $sorting;
            $sorting++;

            $this->saveToDatabase($databaseData, $table, $iterationCount);
        }

        // Update count in event table
        $this->databaseConnection->update(
            'tx_events2_domain_model_event',
            [
                'categories' => $sorting
            ],
            [
                'uid' => $databaseData['uid_foreign']
            ]
        );
    }

    protected function saveDataForOrganizers(array $databaseData, string $table, int $iterationCount): void
    {
        // Delete previously stored organizers
        $this->databaseConnection->delete(
            $table,
            [
                'uid_local' => $databaseData['uid_local']
            ]
        );

        // Store new organizers relations
        $sorting = 0;
        $organizers = $databaseData['organizers'];
        unset($databaseData['organizers']);
        foreach (GeneralUtility::intExplode(',', $organizers, true) as $organizerUid) {
            $databaseData['uid_foreign'] = $organizerUid;
            $databaseData['sorting_foreign'] = $sorting;
            $sorting++;

            $this->saveToDatabase($databaseData, $table, $iterationCount);
        }

        // Update count in event table
        $this->databaseConnection->update(
            'tx_events2_domain_model_event',
            [
                'organizers' => $sorting
            ],
            [
                'uid' => $databaseData['uid_local']
            ]
        );
    }

    /**
     * Read the option called $optionName from $this->options, and parse {...}
     * as object accessors.
     *
     * Then translate the value.
     *
     * If $optionName was not found, the corresponding default option is returned (from $this->defaultOptions)
     *
     * @param string $optionName
     * @return string|array|null
     */
    protected function parseOption(string $optionName)
    {
        $optionValue = parent::parseOption($optionName);

        if (!is_string($optionValue)) {
            return $optionValue;
        }

        if (preg_match('/^{([^}]+)}$/', $optionValue, $matches)) {
            if ($matches[1] === '__currentWeekday') {
                $currentDate = new \DateTime('now');
                return strtolower($currentDate->format('l'));
            }

            if ($matches[1] === '__currentOrganizer') {
                return GeneralUtility::makeInstance(UserRepository::class)
                    ->getFieldFromUser('tx_events2_organizer');
            }
        }

        return $optionValue;
    }

    /**
     * Throws an exception if some inconsistent configuration
     * are detected.
     *
     * @throws FinisherException
     */
    protected function throwExceptionOnInconsistentConfiguration()
    {
        if (
            $this->options['mode'] === 'update'
            && empty($this->options['whereClause'])
        ) {
            throw new FinisherException(
                'An empty option "whereClause" is not allowed in update mode.',
                1480469086
            );
        }
    }

    protected function getFormValues(): array
    {
        return $this->finisherContext->getFormValues();
    }

    protected function getElementByIdentifier(string $elementIdentifier): ?FormElementInterface
    {
        return $this
            ->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getElementByIdentifier($elementIdentifier);
    }
}
