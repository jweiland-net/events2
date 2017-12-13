<?php

namespace JWeiland\Events2\ViewHelpers;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Sorts an instance of ObjectStorage, an Iterator implementation,
 * an Array or a QueryResult (including Lazy counterparts).
 *
 * Can be used inline, i.e.:
 * <f:for each="{dataset -> vhs:iterator.sort(sortBy: 'name')}" as="item">
 * 	// iterating data which is ONLY sorted while rendering this particular loop
 * </f:for>
 *
 * @author Claus Due <claus@wildside.dk>, Wildside A/S
 */
class SortViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        $this->registerArgument('sortBy', 'string', 'Which property/field to sort by - leave out for numeric sorting based on indexes(keys)');
        $this->registerArgument('order', 'string', 'ASC, DESC, RAND or SHUFFLE. RAND preserves keys, SHUFFLE does not - but SHUFFLE is faster', false, 'ASC');
        $this->registerArgument('sortFlags', 'string', 'Constant name from PHP for SORT_FLAGS: SORT_REGULAR, SORT_STRING, SORT_NUMERIC, SORT_NATURAL, SORT_LOCALE_STRING or SORT_FLAG_CASE', false, 'SORT_REGULAR');
    }

    /**
     * "Render" method - sorts a target list-type target. Either $array or
     * $objectStorage must be specified. If both are, ObjectStorage takes precedence.
     *
     * Returns the same type as $subject. Ignores NULL values which would be
     * OK to use in an f:for (empty loop as result)
     *
     * @param array|\Iterator $subject An array or Iterator implementation to sort
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function render($subject = null)
    {
        if (null === $subject) {
            // this case enables inline usage if the "as" argument
            // is not provided. If "as" is provided, the tag content
            // (which is where inline arguments are taken from) is
            // expected to contain the rendering rather than the variable.
            $subject = $this->renderChildren();
        }
        $sorted = null;
        if (is_array($subject) === true) {
            $sorted = $this->sortArray($subject);
        } else {
            if ($subject instanceof ObjectStorage || $subject instanceof LazyObjectStorage) {
                $sorted = $this->sortObjectStorage($subject);
            } elseif ($subject instanceof \Iterator) {
                /* @var \Iterator $subject */
                $array = iterator_to_array($subject, true);
                $sorted = $this->sortArray($array);
            } elseif ($subject instanceof QueryResultInterface) {
                /* @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $subject */
                $sorted = $this->sortArray($subject->toArray());
            } elseif ($subject !== null) {
                // a NULL value is respected and ignored, but any
                // unrecognized value other than this is considered a
                // fatal error.
                throw new \Exception('Unsortable variable type passed to Iterator/SortViewHelper. Expected any of Array, QueryResult, '.
                    ' ObjectStorage or Iterator implementation but got ' . gettype($subject), 1351958941);
            }
        }

        return $sorted;
    }

    /**
     * Sort an array.
     *
     * @param array $array
     *
     * @return array
     */
    protected function sortArray($array)
    {
        $sorted = [];
        foreach ($array as $index => $object) {
            if ($this->arguments['sortBy']) {
                $index = $this->getSortValue($object);
            }
            while (isset($sorted[$index])) {
                $index .= true === is_int($index) ? '.1' : '1';
            }
            $sorted[$index] = $object;
        }
        if ($this->arguments['order'] === 'ASC') {
            ksort($sorted, constant($this->arguments['sortFlags']));
        } elseif ($this->arguments['order'] === 'RAND') {
            $sortedKeys = array_keys($sorted);
            shuffle($sortedKeys);
            $backup = $sorted;
            $sorted = [];
            foreach ($sortedKeys as $sortedKey) {
                $sorted[$sortedKey] = $backup[$sortedKey];
            }
        } elseif ($this->arguments['order'] === 'SHUFFLE') {
            shuffle($sorted);
        } else {
            krsort($sorted, constant($this->arguments['sortFlags']));
        }

        return $sorted;
    }

    /**
     * Sort a \TYPO3\CMS\Extbase\Persistence\ObjectStorage instance.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $storage
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    protected function sortObjectStorage($storage)
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $temp */
        $temp = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
        foreach ($storage as $item) {
            $temp->attach($item);
        }
        $sorted = [];
        foreach ($storage as $index => $item) {
            if ($this->arguments['sortBy']) {
                $index = $this->getSortValue($item);
            }
            while (isset($sorted[$index])) {
                $index .= true === is_int($index) ? '.1' : '1';
            }
            $sorted[$index] = $item;
        }
        if ($this->arguments['order'] === 'ASC') {
            ksort($sorted, constant($this->arguments['sortFlags']));
        } elseif ($this->arguments['order'] === 'RAND') {
            $sortedKeys = array_keys($sorted);
            shuffle($sortedKeys);
            $backup = $sorted;
            $sorted = [];
            foreach ($sortedKeys as $sortedKey) {
                $sorted[$sortedKey] = $backup[$sortedKey];
            }
        } elseif ($this->arguments['order'] === 'SHUFFLE') {
            shuffle($sorted);
        } else {
            krsort($sorted, constant($this->arguments['sortFlags']));
        }
        /** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $storage */
        $storage = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
        foreach ($sorted as $item) {
            $storage->attach($item);
        }

        return $storage;
    }

    /**
     * Gets the value to use as sorting value from $object.
     *
     * @param mixed $object
     *
     * @return mixed
     */
    protected function getSortValue($object)
    {
        $field = $this->arguments['sortBy'];
        $value = ObjectAccess::getPropertyPath($object, $field);
        if ($value instanceof \DateTime) {
            $value = intval($value->format('U'));
        } elseif ($value instanceof ObjectStorage || $value instanceof LazyObjectStorage) {
            $value = $value->count();
        } elseif (is_array($value)) {
            $value = count($value);
        }

        return $value;
    }
}
