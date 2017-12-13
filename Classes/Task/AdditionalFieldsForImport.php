<?php
namespace JWeiland\Events2\Task;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class AdditionalFieldsForImport
 *
 * @package JWeiland\Events2\Task
 */
class AdditionalFieldsForImport implements AdditionalFieldProviderInterface
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
     * list of fields to create input fields for
     *
     * @var array
     */
    protected $createFieldsFor = [
        'path' => [
            'default' => '',
            'attr' => [
                'placeholder' => 'fileadmin/'
            ],
        ],
        'storagePid' => [
            'default' => '0',
            'attr' => [
                'placeholder' => '123'
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
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The task object being eddited. Null when adding a task!
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     *
     * @return array A two dimensional array, ['Identifier' => ['fieldId' => ['code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => '']]]
     */
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule)
    {
        // make variables available for all methods in this class
        $this->initialize($taskInfo, $task, $schedulerModule);

        $additionalFields = [];
        foreach ($this->createFieldsFor as $fieldName => $configuration) {
            $this->createInputField($fieldName, (array)$configuration, $additionalFields);
        }

        return $additionalFields;
    }

    /**
     * initializes this object
     * and set some value available for all methods in this class
     *
     * @param array $taskInfo
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule
     */
    protected function initialize(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule)
    {
        $this->taskInfo = $taskInfo;
        $this->task = $task;
        $this->schedulerModule = $schedulerModule;
    }

    /**
     * create new input field
     *
     * @param string $fieldName
     * @param array $configuration
     * @param array $additionalFields
     *
     * @return void
     */
    protected function createInputField($fieldName, array $configuration, array &$additionalFields)
    {
        $attributes = $this->defaultAttributes;
        $attributes['id'] = 'task_' . $fieldName;
        $attributes['name'] = 'tx_scheduler[' . $fieldName . ']';
        $attributes['value'] = $this->getValueForInputField($fieldName, $configuration);
        if (isset($configuration['attr']) && is_array($configuration['attr'])) {
            $attributes = array_merge($attributes, $configuration['attr']);
        }

        /** @var TagBuilder $tagBuilder */
        $tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);
        $tagBuilder->setTagName('input');
        $tagBuilder->addAttributes($attributes);

        $additionalFields[$attributes['id']] = [
            'code'     => $tagBuilder->render(),
            'label'    => LocalizationUtility::translate('scheduler.' . $fieldName, 'Events2'),
            'cshKey'   => '_MOD_events2_scheduler',
            'cshLabel' => $fieldName
        ];
    }

    /**
     * get value for input field
     *
     * @param string $fieldName
     * @param array $configuration
     *
     * @return string Value for input field
     */
    protected function getValueForInputField($fieldName, array $configuration)
    {
        $value = '';
        // if field is empty try to find the needed value
        if (empty($this->taskInfo[$fieldName])) {
            if ($this->schedulerModule->CMD === 'add' && isset($configuration['default'])) {
                // In case of new task override value with value from configuration
                $value = $configuration['default'];
            }
            if ($this->schedulerModule->CMD == 'edit') {
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
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     *
     * @return boolean true if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule)
    {
        $errorExists = false;
        foreach (array_keys($this->createFieldsFor) as $fieldName) {
            $value = trim($submittedData[$fieldName]);
            if (empty($value)) {
                // Issue error message
                $errorExists = true;
                $schedulerModule->addMessage('Field: ' . $fieldName . ' can not be empty', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the scheduler backend module
     *
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        foreach (array_keys($this->createFieldsFor) as $fieldName) {
            $task->$fieldName = $submittedData[$fieldName];
        }
    }
}
