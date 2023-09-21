<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Task;

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/*
 * Additional FieldProvider for events2 Importer
 */
class AdditionalFieldsForImport extends AbstractAdditionalFieldProvider
{
    /**
     * @var array
     */
    protected $taskInfo = [];

    /**
     * @var AbstractTask|Import
     */
    protected $task;

    /**
     * @var SchedulerModuleController
     */
    protected $schedulerModule;

    /**
     * @var array
     */
    protected $defaultAttributes = [
        'type' => 'text',
        'size' => 30,
    ];

    /**
     * List of fields to create input fields for
     *
     * @var array
     */
    protected $createFieldsFor = [
        'path' => [
            'default' => '',
            'attr' => [
                'placeholder' => '1:/event_import/Import.xml',
            ],
        ],
        'storagePid' => [
            'default' => '0',
            'attr' => [
                'placeholder' => '123',
            ],
        ],
    ];

    /**
     * little template for an input field
     *
     * @var string
     */
    protected $htmlForInputField = '<input type="text" name="tx_scheduler[%s]" id="%s" value="%s" size="%s" />';

    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param AbstractTask|null $task The task object being edited. Null when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two dimensional array, ['Identifier' => ['fieldId' => ['code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => '']]]
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        // make variables available for all methods in this class
        $this->initialize($taskInfo, $task, $schedulerModule);

        $additionalFields = [];
        foreach ($this->createFieldsFor as $fieldName => $configuration) {
            $this->createInputField($fieldName, (array)$configuration, $additionalFields);
        }

        return $additionalFields;
    }

    protected function initialize(
        array $taskInfo,
        ?AbstractTask $task,
        SchedulerModuleController $schedulerModule
    ): void {
        $this->taskInfo = $taskInfo;
        $this->task = $task;
        $this->schedulerModule = $schedulerModule;
    }

    protected function createInputField(string $fieldName, array $configuration, array &$additionalFields): void
    {
        $attributes = $this->defaultAttributes;
        $attributes['id'] = 'task_' . $fieldName;
        $attributes['name'] = 'tx_scheduler[' . $fieldName . ']';
        $attributes['value'] = $this->getValueForInputField($fieldName, $configuration);
        if (isset($configuration['attr']) && is_array($configuration['attr'])) {
            $attributes = array_merge($attributes, $configuration['attr']);
        }

        $tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);
        $tagBuilder->setTagName('input');
        $tagBuilder->addAttributes($attributes);

        $additionalFields[$attributes['id']] = [
            'code'     => $tagBuilder->render(),
            'label'    => LocalizationUtility::translate('scheduler.' . $fieldName, 'Events2'),
            'cshKey'   => '_MOD_events2_scheduler',
            'cshLabel' => $fieldName,
        ];
    }

    protected function getValueForInputField(string $fieldName, array $configuration): string
    {
        $value = '';
        // if field is empty try to find the needed value
        if (empty($this->taskInfo[$fieldName])) {
            if (
                isset($configuration['default'])
                && $this->schedulerModule->getCurrentAction()->equals('add')
            ) {
                // In case of new task override value with value from configuration
                $value = $configuration['default'];
            }
            if ($this->schedulerModule->getCurrentAction()->equals('edit')) {
                // In case of edit, set to internal value
                $value = $this->task->$fieldName;
            }
        } else {
            $value = $this->taskInfo[$fieldName];
        }

        return $value;
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool true if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        $errorExists = false;
        foreach (array_keys($this->createFieldsFor) as $fieldName) {
            $value = trim($submittedData[$fieldName]);
            if (empty($value)) {
                // Issue error message
                $errorExists = true;
                $this->addMessage('Field: ' . $fieldName . ' can not be empty', AbstractMessage::ERROR);
            } else {
                $submittedData[$fieldName] = $value;
            }
        }
        return !$errorExists;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param AbstractTask $task Reference to the scheduler backend module
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        foreach (array_keys($this->createFieldsFor) as $fieldName) {
            $task->$fieldName = $submittedData[$fieldName];
        }
    }
}
