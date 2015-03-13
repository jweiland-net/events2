<?php
namespace JWeiland\Events2\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Exception extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * ExceptionType
	 *
	 * @var string
	 */
	protected $exceptionType = '';

	/**
	 * ExceptionDate
	 *
	 * @var \DateTime
	 */
	protected $exceptionDate = NULL;

	/**
	 * ExceptionTime
	 *
	 * @var \JWeiland\Events2\Domain\Model\Time
	 */
	protected $exceptionTime = NULL;

	/**
	 * ExceptionDetails
	 *
	 * @var string
	 */
	protected $exceptionDetails = '';





	/**
	 * Setter for exceptionType
	 *
	 * @param string $exceptionType
	 */
	public function setExceptionType($exceptionType) {
		$this->exceptionType = (string)$exceptionType;
	}

	/**
	 * Getter for exceptionType
	 *
	 * @return string
	 */
	public function getExceptionType() {
		return $this->exceptionType;
	}

	/**
	 * Setter for exceptionDate
	 *
	 * @param \DateTime $exceptionDate
	 */
	public function setExceptionDate(\DateTime $exceptionDate) {
		$this->exceptionDate = $exceptionDate;
	}

	/**
	 * Getter for exceptionDate
	 *
	 * @return \DateTime
	 */
	public function getExceptionDate() {
		return $this->exceptionDate;
	}

	/**
	 * Getter for exceptionVisibleDate
	 *
	 * @return string
	 */
	public function getExceptionVisibleDate() {
		return $this->exceptionDate->format('d.m.Y');
	}

	/**
	 * Returns the exceptionTime
	 *
	 * @return \JWeiland\Events2\Domain\Model\Time $time
	 */
	public function getExceptionTime() {
		return $this->exceptionTime;
	}

	/**
	 * Sets the exceptionTime
	 *
	 * @param \JWeiland\Events2\Domain\Model\Time $exceptionTime
	 * @return void
	 */
	public function setExceptionTime(\JWeiland\Events2\Domain\Model\Time $exceptionTime) {
		$this->exceptionTime = $exceptionTime;
	}

	/**
	 * Setter for exceptionDetails
	 *
	 * @param string $exceptionDetails
	 */
	public function setExceptionDetails($exceptionDetails) {
		$this->exceptionDetails = (string)$exceptionDetails;
	}

	/**
	 * Getter for exceptionDetails
	 *
	 * @return string
	 */
	public function getExceptionDetails() {
		return $this->exceptionDetails;
	}

}