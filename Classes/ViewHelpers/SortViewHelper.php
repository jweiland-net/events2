<?php
namespace JWeiland\Events2\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Claus Due <claus@wildside.dk>, Wildside A/S
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * @package Vhs
 * @subpackage ViewHelpers\Iterator
 */
class SortViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Initialize arguments
	 *
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerArgument('sortBy', 'string', 'Which property/field to sort by - leave out for numeric sorting based on indexes(keys)');
		$this->registerArgument('order', 'string', 'ASC, DESC, RAND or SHUFFLE. RAND preserves keys, SHUFFLE does not - but SHUFFLE is faster', FALSE, 'ASC');
		$this->registerArgument('sortFlags', 'string', 'Constant name from PHP for SORT_FLAGS: SORT_REGULAR, SORT_STRING, SORT_NUMERIC, SORT_NATURAL, SORT_LOCALE_STRING or SORT_FLAG_CASE', FALSE, 'SORT_REGULAR');
	}

	/**
	 * "Render" method - sorts a target list-type target. Either $array or
	 * $objectStorage must be specified. If both are, ObjectStorage takes precedence.
	 *
	 * Returns the same type as $subject. Ignores NULL values which would be
	 * OK to use in an f:for (empty loop as result)
	 *
	 * @param array|\Iterator $subject An array or Iterator implementation to sort
	 * @throws \Exception
	 * @return mixed
	 */
	public function render($subject = NULL) {
		if (NULL === $subject) {
			// this case enables inline usage if the "as" argument
			// is not provided. If "as" is provided, the tag content
			// (which is where inline arguments are taken from) is
			// expected to contain the rendering rather than the variable.
			$subject = $this->renderChildren();
		}
		$sorted = NULL;
		if (is_array($subject) === TRUE) {
			$sorted = $this->sortArray($subject);
		} else {
			if ($subject instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage || $subject instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage) {
				$sorted = $this->sortObjectStorage($subject);
			} elseif ($subject instanceof Iterator) {
				/** @var \Iterator $subject */
				$array = iterator_to_array($subject, TRUE);
				$sorted = $this->sortArray($array);
			} elseif ($subject instanceof \TYPO3\CMS\Extbase\Persistence\QueryResultInterface) {
				/** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $subject */
				$sorted = $this->sortArray($subject->toArray());
			} elseif ($subject !== NULL) {
				// a NULL value is respected and ignored, but any
				// unrecognized value other than this is considered a
				// fatal error.
				throw new \Exception('Unsortable variable type passed to Iterator/SortViewHelper. Expected any of Array, QueryResult, ' .
					' ObjectStorage or Iterator implementation but got ' . gettype($subject), 1351958941);
			}
		}
		return $sorted;
	}

	/**
	 * Sort an array
	 *
	 * @param array $array
	 * @return array
	 */
	protected function sortArray($array) {
		$sorted = array();
		foreach ($array as $index => $object) {
			if ($this->arguments['sortBy']) {
				$index = $this->getSortValue($object);
			}
			while (isset($sorted[$index])) {
				$index .= TRUE === is_int($index) ? '.1' : '1';
			}
			$sorted[$index] = $object;
		}
		if ($this->arguments['order'] === 'ASC') {
			ksort($sorted, constant($this->arguments['sortFlags']));
		} elseif ($this->arguments['order'] === 'RAND') {
			$sortedKeys = array_keys($sorted);
			shuffle($sortedKeys);
			$backup = $sorted;
			$sorted = array();
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
	 * Sort a \TYPO3\CMS\Extbase\Persistence\ObjectStorage instance
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $storage
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected function sortObjectStorage($storage) {
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $temp */
		$temp = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		foreach ($storage as $item) {
			$temp->attach($item);
		}
		$sorted = array();
		foreach ($storage as $index => $item) {
			if ($this->arguments['sortBy']) {
				$index = $this->getSortValue($item);
			}
			while (isset($sorted[$index])) {
				$index .= TRUE === is_int($index) ? '.1' : '1';
			}
			$sorted[$index] = $item;
		}
		if ($this->arguments['order'] === 'ASC') {
			ksort($sorted, constant($this->arguments['sortFlags']));
		} elseif ($this->arguments['order'] === 'RAND') {
			$sortedKeys = array_keys($sorted);
			shuffle($sortedKeys);
			$backup = $sorted;
			$sorted = array();
			foreach ($sortedKeys as $sortedKey) {
				$sorted[$sortedKey] = $backup[$sortedKey];
			}
		} elseif ($this->arguments['order'] === 'SHUFFLE') {
			shuffle($sorted);
		} else {
			krsort($sorted, constant($this->arguments['sortFlags']));
		}
		$storage = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		foreach ($sorted as $item) {
			$storage->attach($item);
		}
		return $storage;
	}

	/**
	 * Gets the value to use as sorting value from $object
	 *
	 * @param mixed $object
	 * @return mixed
	 */
	protected function getSortValue($object) {
		$field = $this->arguments['sortBy'];
		$value = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($object, $field);
		if ($value instanceof \DateTime) {
			$value = intval($value->format('U'));
		} elseif ($value instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage || $value instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage) {
			$value = $value->count();
		} elseif (is_array($value)) {
			$value = count($value);
		}
		return $value;
	}

}