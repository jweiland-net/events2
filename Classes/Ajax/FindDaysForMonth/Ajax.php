<?php

namespace JWeiland\Events2\Ajax\FindDaysForMonth;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\PreparedStatement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Ajax
{
    /**
     * arguments from GET.
     *
     * @var array
     */
    protected $arguments = array();

    /**
     * database.
     *
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility = null;

    /**
     * @var \TYPO3\CMS\Frontend\Page\CacheHashCalculator
     */
    protected $cacheHashCalculator;

    /**
     * inject DateTime Utility.
     *
     * @param \JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(\JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * inject CacheHash Calculator.
     *
     * @param \TYPO3\CMS\Frontend\Page\CacheHashCalculator $cacheHashCalculator
     */
    public function injectCacheHashCalculator(\TYPO3\CMS\Frontend\Page\CacheHashCalculator $cacheHashCalculator)
    {
        $this->cacheHashCalculator = $cacheHashCalculator;
    }

    /**
     * initializes this class.
     *
     * @param array $arguments
     */
    public function initialize(array $arguments)
    {
        // load cached TCA. Needed for enableFields
        Bootstrap::getInstance()->loadCachedTca();
        $this->setArguments($arguments);
    }

    /**
     * set and check GET Arguments.
     *
     * @param array $arguments
     */
    public function setArguments(array $arguments)
    {
        // sanitize categories
        $sanitizedArguments['categories'] = $this->sanitizeCommaSeparatedIntValues((string) $arguments['categories']);
        $sanitizedArguments['month'] = (int) $arguments['month'];
        $sanitizedArguments['year'] = (int) $arguments['year'];
        $sanitizedArguments['pidOfListPage'] = (int) $arguments['pidOfListPage'];
        $sanitizedArguments['storagePids'] = $this->sanitizeCommaSeparatedIntValues((string) $arguments['storagePids']);

        $this->arguments = $sanitizedArguments;
    }

    /**
     * sanitize comma separated values
     * remove empty values
     * remove values which can't be interpreted as integer
     * cast each valid value to integer.
     *
     * @param string $list
     *
     * @return string The sanitized int list
     */
    protected function sanitizeCommaSeparatedIntValues($list)
    {
        $values = GeneralUtility::trimExplode(',', $list, true);
        foreach ($values as $key => $value) {
            if (MathUtility::canBeInterpretedAsInteger($value)) {
                $values[$key] = (int) $value;
            } else {
                unset($values[$key]);
            }
        }

        return implode(',', array_unique($values));
    }

    /**
     * getter for arguments
     * needed for unitTests.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * process ajax request.
     *
     * @param array $arguments Arguments to process
     *
     * @return string
     */
    public function processAjaxRequest(array $arguments)
    {
        $this->initialize($arguments);
        $month = $this->getArgument('month');
        $year = $this->getArgument('year');

        // save a session for selected month
        $this->saveMonthAndYearInSession($month, $year);

        $dayArray = array();
        $days = $this->findAllDaysInMonth($month, $year);
        foreach ($days as $day) {
            $addDay = array(
                'uid' => $day['eventUid'],
                'title' => $day['eventTitle']
            );
            $addDay['uri'] = $this->getUriForDay($day['uid']);
            $dayOfMonth = $this->dateTimeUtility->convert($day['day'])->format('j');
            $dayArray[$dayOfMonth][] = $addDay;
        }
        $this->addHolidays($dayArray);

        return json_encode($dayArray);
    }

    /**
     * get an argument from GET.
     *
     * @param string $argumentName
     *
     * @return string
     */
    protected function getArgument($argumentName)
    {
        if (isset($this->arguments[$argumentName])) {
            return $this->arguments[$argumentName];
        } else {
            return '';
        }
    }

    /**
     * We can't create the uri within a JavaScript for-loop.
     * This way we also have realurl functionality
     * We need the current day for calendar and day controller.
     *
     * @param int $dayUid
     *
     * @return mixed
     */
    public function getUriForDay($dayUid)
    {
        // uriBuilder is very slow: 223ms for 31 links */
        /*$uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($pid)
            ->uriFor('show', array('day' => $dayUid), 'Day', 'events2', 'events');*/

        // create uri manually instead of uriBuilder
        $siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL').'index.php?';
        $query = array(
            'id' => $this->getArgument('pidOfListPage'),
            'tx_events2_events' => array(
                'controller' => 'Day',
                'action' => 'show',
                'day' => (int)$dayUid,
            ),
        );
        $cacheHashArray = $this->cacheHashCalculator->getRelevantParameters(GeneralUtility::implodeArrayForUrl('', $query));
        $query['cHash'] = $this->cacheHashCalculator->calculateCacheHash($cacheHashArray);
        $uri = $siteUrl . http_build_query($query);

        return $uri;
    }

    /**
     * Add Holidays
     *
     * @param array $days
     */
    protected function addHolidays(array &$days)
    {
        $monthOfYear = $this->getDatabaseConnection()->fullQuoteStr(
            $this->getArgument('month'),
            'tx_events2_domain_model_holiday'
        );
        $holidays = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'day',
            'tx_events2_domain_model_holiday',
            'month=' . $monthOfYear
        );
        if (!empty($holidays)) {
            foreach ($holidays as $holiday) {
                $days[$holiday['day']][] = array(
                    'uid' => $holiday['day'],
                    'class' => 'holiday'
                );
            }
        }
    }

    /**
     * save selected month and year in an user session.
     *
     * @param int $month
     * @param int $year
     */
    protected function saveMonthAndYearInSession($month, $year)
    {
        $userAuthentication = $this->getFrontendUserAuthentication();
        $userAuthentication->start();
        $userAuthentication->setAndSaveSessionData(
            'events2MonthAndYearForCalendar',
            array(
                'month' => $month,
                'year' => $year,
            )
        );
    }

    /**
     * find all days in given month.
     *
     * @param int $month
     * @param int $year
     *
     * @return array
     */
    public function findAllDaysInMonth($month, $year)
    {
        // get start and ending of given month
        // j => day without leading 0, n => month without leading 0
        $monthBegin = $this->dateTimeUtility->standardizeDateTimeObject(\DateTime::createFromFormat('j.n.Y', '1.'.$month.'.'.$year));
        $monthEnd = clone $monthBegin;
        $monthEnd->modify('last day of this month')->modify('tomorrow');

        $categories = $this->getArgument('categories');

        if ($categories !== '') {
            $statement = $this->getDatabaseConnection()->prepare_SELECTquery(
                'tx_events2_domain_model_day.uid, tx_events2_domain_model_day.day, tx_events2_domain_model_event.uid as eventUid, tx_events2_domain_model_event.title as eventTitle',
                'tx_events2_domain_model_day
                LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_day.uid=tx_events2_event_day_mm.uid_foreign
                LEFT JOIN tx_events2_domain_model_event ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
                LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign',
                'sys_category_record_mm.tablenames = :tablename
                AND tx_events2_domain_model_day.day >= :monthBegin
                AND tx_events2_domain_model_day.day < :monthEnd
                AND FIND_IN_SET(tx_events2_domain_model_event.pid, :storagePids)
                AND sys_category_record_mm.uid_local IN ('.$categories.')'.
                $this->addWhereForEnableFields()
            );
            $statement->execute(array(
                ':tablename' => 'tx_events2_domain_model_event',
                ':monthBegin' => $monthBegin->format('U'),
                ':monthEnd' => $monthEnd->format('U'),
                ':storagePids' => $this->getArgument('storagePids'),
            ));
        } else {
            $statement = $this->getDatabaseConnection()->prepare_SELECTquery(
                'tx_events2_domain_model_day.uid, tx_events2_domain_model_day.day, tx_events2_domain_model_event.uid as eventUid, tx_events2_domain_model_event.title as eventTitle',
                'tx_events2_domain_model_day
                LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_day.uid=tx_events2_event_day_mm.uid_foreign
                LEFT JOIN tx_events2_domain_model_event ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local',
                'tx_events2_domain_model_day.day >= :monthBegin
                AND tx_events2_domain_model_day.day < :monthEnd
                AND FIND_IN_SET(tx_events2_domain_model_event.pid, :storagePids)' .
                $this->addWhereForEnableFields()
            );
            $statement->execute(array(
                ':monthBegin' => $monthBegin->format('U'),
                ':monthEnd' => $monthEnd->format('U'),
                ':storagePids' => $this->getArgument('storagePids'),
            ));
        }
        $rows = $statement->fetchAll(PreparedStatement::FETCH_ASSOC);
        $statement->free();

        return $rows;
    }

    /**
     * add where clause for enableFields.
     *
     * @return string
     */
    protected function addWhereForEnableFields()
    {
        $additionalWhere = BackendUtility::BEenableFields('tx_events2_domain_model_event');
        $additionalWhere .= BackendUtility::deleteClause('tx_events2_domain_model_event');

        return $additionalWhere;
    }

    /**
     * Get Frontend User Authentication
     *
     * @return FrontendUserAuthentication
     */
    protected function getFrontendUserAuthentication()
    {
        return GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
    }

    /**
     * Get TYPO3 Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    public function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
