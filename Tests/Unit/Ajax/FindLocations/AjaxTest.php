<?php
namespace JWeiland\Events2\Tests\Unit\Ajax\FindLocations;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use JWeiland\Events2\Ajax\FindLocations;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @subpackage events2
 * @author Stefan Froemken <projects@jweiland.net>
 */
class AjaxTest extends UnitTestCase {

	/**
	 * @var \JWeiland\Events2\Ajax\FindLocations\Ajax
	 */
	protected $subject;

	public function setUp() {
		$this->subject = new FindLocations\Ajax();
	}

	public function tearDown() {
		unset($this->subject);
	}

	/**
	 * I don't see a possibility to test, if TCA was loaded correct
	 *
	 * @test
	 */
	public function initializeObjectInitializesFindLocationClass() {
		$this->subject->initializeObject();
		$this->assertInstanceOf(
			'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
			$this->subject->getDatabaseConnection()
		);
	}

	/**
	 * @test
	 */
	public function processAjaxRequestWithNoLocationsReturnsEmptyJson() {
		$arguments = array(
			'locationPart' => 'Hello german umlauts: öäü. <b>How are you?</b>'
		);
		$expectedArgument = 'Hello german umlauts: öäü. How are you?';
		/** @var \JWeiland\Events2\Ajax\FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('JWeiland\\Events2\\Ajax\\FindLocations\\Ajax', array('findLocations'));
		$subject->expects($this->once())->method('findLocations')->with($expectedArgument)->will($this->returnValue(array()));
		$this->assertSame(
			'[]',
			$subject->processAjaxRequest($arguments)
		);
	}

	/**
	 * @test
	 */
	public function processAjaxRequestWithLocationsReturnsJson() {
		$locationMap = array(
			array(
				'at h',
				array(
					array(
						'uid' => 123,
						'label' => 'at home'
					)
				)
			),
			array(
				'mar',
				array(
					array(
						'uid' => 234,
						'label' => 'Marienheide'
					),
					array(
						'uid' => 345,
						'label' => 'Marienhagen'
					)
				)
			)
		);
		/** @var \JWeiland\Events2\Ajax\FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('JWeiland\\Events2\\Ajax\\FindLocations\\Ajax', array('findLocations'));
		$subject->expects($this->exactly(2))->method('findLocations')->will($this->returnValueMap($locationMap));
		$this->assertSame(
			'[{"uid":123,"label":"at home"}]',
			$subject->processAjaxRequest(array('locationPart' => 'at h'))
		);
		$this->assertSame(
			'[{"uid":234,"label":"Marienheide"},{"uid":345,"label":"Marienhagen"}]',
			$subject->processAjaxRequest(array('locationPart' => 'mar'))
		);
	}

	/**
	 * @test
	 */
	public function findLocationsWithSqlErrorResultsInEmptyArray() {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
		$databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetRows'));
		$databaseConnection->expects($this->once())->method('exec_SELECTgetRows')->with(
			$this->equalTo('uid, location as label'),
			$this->equalTo('tx_events2_domain_model_location'),
			$this->logicalAnd(
				$this->stringContains('LIKE "%mar%"'),
				$this->stringContains('tx_events2_domain_model_location.hidden=0'),
				$this->stringContains('tx_events2_domain_model_location.deleted=0')
			)
		)->will($this->returnValue(NULL));
		/** @var \JWeiland\Events2\Ajax\FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('JWeiland\\Events2\\Ajax\\FindLocations\\Ajax', array('dummy'));
		$subject->_set('databaseConnection', $databaseConnection);
		$this->assertSame(
			array(),
			$subject->findLocations('mar')
		);
	}

	/**
	 * @test
	 */
	public function findLocationsWithLocationReturnsLocations() {
		$record = array(
			array(
				'uid' => 123,
				'label' => 'Marienheide'
			),
			array(
				'uid' => 456,
				'label' => 'Marienhagen'
			)
		);
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
		$databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetRows'));
		$databaseConnection->expects($this->once())->method('exec_SELECTgetRows')->with(
			$this->equalTo('uid, location as label'),
			$this->equalTo('tx_events2_domain_model_location'),
			$this->stringContains('LIKE "%mar%"')
		)->will($this->returnValue($record));
		/** @var \JWeiland\Events2\Ajax\FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('JWeiland\\Events2\\Ajax\\FindLocations\\Ajax', array('dummy'));
		$subject->_set('databaseConnection', $databaseConnection);
		$this->assertSame(
			$record,
			$subject->findLocations('mar')
		);
	}

	/**
	 * @test
	 */
	public function findLocationsWithHtmlInLocationResultsInLikeQueryWithoutHtml() {
		$record = array(
			array(
				'uid' => 123,
				'label' => 'Marienheide'
			),
			array(
				'uid' => 456,
				'label' => 'Marienhagen'
			)
		);
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
		$databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetRows'));
		$databaseConnection->expects($this->once())->method('exec_SELECTgetRows')->with(
			$this->equalTo('uid, location as label'),
			$this->equalTo('tx_events2_domain_model_location'),
			$this->stringContains('LIKE "%mar%"')
		)->will($this->returnValue($record));
		/** @var \JWeiland\Events2\Ajax\FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('JWeiland\\Events2\\Ajax\\FindLocations\\Ajax', array('dummy'));
		$subject->_set('databaseConnection', $databaseConnection);
		$this->assertSame(
			$record,
			$subject->findLocations('<b>mar</b>')
		);
	}

	/**
	 * @test
	 */
	public function findLocationsWithHtmlInLocationResultsInLikeQueryWithHtmlSpecialCharedValue() {
		$record = array(
			array(
				'uid' => 123,
				'label' => 'Marienheide'
			),
			array(
				'uid' => 456,
				'label' => 'Marienhagen'
			)
		);
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
		$databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetRows'));
		$databaseConnection->expects($this->once())->method('exec_SELECTgetRows')->with(
			$this->equalTo('uid, location as label'),
			$this->equalTo('tx_events2_domain_model_location'),
			$this->stringContains('LIKE "%foo &lt; bar%"')
		)->will($this->returnValue($record));
		/** @var \JWeiland\Events2\Ajax\FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('JWeiland\\Events2\\Ajax\\FindLocations\\Ajax', array('dummy'));
		$subject->_set('databaseConnection', $databaseConnection);
		$this->assertSame(
			$record,
			$subject->findLocations('foo < bar')
		);
	}

	/**
	 * @test
	 */
	public function findLocationsWithProcentInLocationResultsInLikeQueryWithEscapedProcent() {
		$record = array(
			array(
				'uid' => 123,
				'label' => 'Marienheide'
			),
			array(
				'uid' => 456,
				'label' => 'Marienhagen'
			)
		);
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
		$databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetRows'));
		$databaseConnection->expects($this->once())->method('exec_SELECTgetRows')->with(
			$this->equalTo('uid, location as label'),
			$this->equalTo('tx_events2_domain_model_location'),
			$this->stringContains('LIKE "%foo\%bar%"')
		)->will($this->returnValue($record));
		/** @var \JWeiland\Events2\Ajax\FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('JWeiland\\Events2\\Ajax\\FindLocations\\Ajax', array('dummy'));
		$subject->_set('databaseConnection', $databaseConnection);
		$this->assertSame(
			$record,
			$subject->findLocations('foo%bar')
		);
	}

}