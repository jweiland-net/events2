<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers\Widget;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\ViewHelpers\Widget\Controller\ICalendarController;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper;

/*
 * A Fluid widget to create a link for downloading an iCal file
 */
class ICalendarViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @var ICalendarController
     */
    protected $controller;

    public function __construct(ICalendarController $controller)
    {
        $this->controller = $controller;
    }

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('day', Day::class, 'The day object to create the download iCal file for', true);
    }

    /**
     * Renders a link to download an iCal file
     */
    public function render(): string
    {
        return (string)$this->initiateSubRequest();
    }
}
