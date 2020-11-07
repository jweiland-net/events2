<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/*
 * Extract various information from an event/day to build a json-ld string
 */
class JsonLdService
{
    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d';

    /**
     * @var string
     */
    protected $dateTimeFormat = 'Y-m-d\TH:i:s';

    /**
     * @var array
     */
    protected $data = [
        '@context' => 'http://schema.org',
        '@type' => 'Event'
    ];

    /**
     * Read values from day and event record to build a json-ld string for page header
     *
     * @param Day $day
     */
    public function addJsonLdToPageHeader(Day $day): void
    {
        $this->collectData($day);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        // as long as all JS methods will render a script-tag with type "text/javascript", we have to
        // add our own script-Tag
        $pageRenderer->addHeaderData(
            sprintf(
                '<script type="application/ld+json">%s</script>',
                json_encode($this->data)
            )
        );
    }

    /**
     * Helper method for PhpUnit
     *
     * @return array
     */
    public function getCollectedJsonLdData(): array
    {
        return $this->data;
    }

    protected function collectData(Day $day): void
    {
        $timeFactory = GeneralUtility::makeInstance(TimeFactory::class);
        $time = $timeFactory->getTimeForDay($day);
        if ($time instanceof Time) {
            $this->addStartDateOfTimeToData($time);
            $this->addStartDateOfEventToData($day->getEvent());
            $this->addDoorTimeOfTimeToData($time);
            $this->addDurationToData($time);
            $this->addEndDateOfTimeToData($time);
            $this->addEndDateOfEventToData($day->getEvent());
        } else {
            $this->addStartDateOfEventToData($day->getEvent());
            $this->addEndDateOfEventToData($day->getEvent());
        }
        $this->addNameToData($day->getEvent());
        $this->addDescriptionToData($day->getEvent());
        $this->addUrlToData();
        $this->addLocationToData($day->getEvent());
        $this->addOrganizerToData($day->getEvent());
        $this->addImageToData($day->getEvent());
        $this->addOfferToData($day->getEvent());
        $this->addIsAccessibleForFreeToData($day->getEvent());
    }

    /**
     * If an event have a Time record, we add startDate by time_begin column
     *
     * @link: https://schema.org/DateTime
     * @param Time $time
     */
    protected function addStartDateOfTimeToData(Time $time): void
    {
        if ($time->getTimeBeginAsDateTime() instanceof \DateTime) {
            $this->data['startDate'] = $time->getTimeBeginAsDateTime()->format($this->dateTimeFormat);
        }
    }

    /**
     * If an event have a Time record, we add doorTime by time_entry column
     *
     * @link: https://schema.org/DateTime
     * @param Time $time
     */
    protected function addDoorTimeOfTimeToData(Time $time): void
    {
        if ($time->getTimeEntryAsDateTime() instanceof \DateTime) {
            $this->data['doorTime'] = $time->getTimeEntryAsDateTime()->format($this->dateTimeFormat);
        }
    }

    /**
     * Add duration to data
     *
     * @link: https://schema.org/Duration
     * @param Time $time
     */
    protected function addDurationToData(Time $time): void
    {
        if ($time->getDuration() && preg_match('#\d\d:\d\d#', $time->getDuration())) {
            list($hours, $minutes) = GeneralUtility::trimExplode(':', $time->getDuration());
            $this->data['duration'] = sprintf(
                'PT%dH%dM',
                (int)$hours,
                (int)$minutes
            );
        }
    }

    /**
     * If an event have a Time record, we add endDate by time_end column
     *
     * @link: https://schema.org/DateTime
     * @param Time $time
     */
    protected function addEndDateOfTimeToData(Time $time): void
    {
        if ($time->getTimeEndAsDateTime() instanceof \DateTime) {
            $this->data['endDate'] = $time->getTimeEndAsDateTime()->format($this->dateTimeFormat);
        }
    }

    /**
     * If an event does not have any Time records, we add startDate by event_begin column
     *
     * @link: https://schema.org/Date
     * @param Event $event
     */
    protected function addStartDateOfEventToData(Event $event): void
    {
        if (empty($this->data['startDate']) && $event->getEventBegin() instanceof \DateTime) {
            $this->data['startDate'] = $event->getEventBegin()->format($this->dateFormat);
        }
    }

    /**
     * If an event does not have any Time records, we add endDate by event_end column
     *
     * @link: https://schema.org/Date
     * @param Event $event
     */
    protected function addEndDateOfEventToData(Event $event): void
    {
        if (
            empty($this->data['endDate'])
            && $event->getEventType() === 'duration'
            && $event->getEventEnd() instanceof \DateTime
        ) {
            $this->data['endDate'] = $event->getEventEnd()->format($this->dateFormat);
        }
    }

    /**
     * Add name to data
     *
     * @link: https://schema.org/name
     * @param Event $event
     */
    protected function addNameToData(Event $event): void
    {
        $this->data['name'] = strip_tags($event->getTitle());
    }

    /**
     * Add description to data
     *
     * @link: https://schema.org/description
     * @param Event $event
     */
    protected function addDescriptionToData(Event $event): void
    {
        $this->data['description'] = strip_tags($event->getDetailInformation());
    }

    /**
     * Add event URL to data
     *
     * @link: https://schema.org/URL
     */
    protected function addUrlToData(): void
    {
        $this->data['url'] = rawurldecode(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
    }

    /**
     * Add information to data, if event is for free
     *
     * @link: https://schema.org/isAccessibleForFree
     * @param Event $event
     */
    protected function addIsAccessibleForFreeToData(Event $event): void
    {
        $this->data['isAccessibleForFree'] = $event->getFreeEntry() ? 'True' : 'False';
    }

    /**
     * Add URL for offer to data
     *
     * @link: https://schema.org/Offer
     * @param Event $event
     */
    protected function addOfferToData(Event $event): void
    {
        if ($event->getTicketLink() instanceof Link) {
            $this->data['offers'] = [
                0 => [
                    '@type' => 'Offer',
                    'name' => $event->getTicketLink()->getTitle(),
                    'url' => $this->getUrlFromParameter($event->getTicketLink()->getLink())
                ]
            ];
        }
    }

    /**
     * Add Location to data
     *
     * @link: https://schema.org/Place
     * @link: https://schema.org/PostalAddress
     * @param Event $event
     */
    protected function addLocationToData(Event $event): void
    {
        if ($event->getLocation() instanceof Location) {
            $this->data['location'] = [
                '@type' => 'Place',
                'name' => $event->getLocation()->getLocation(),
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $event->getLocation()->getStreet() . ' ' . $event->getLocation()->getHouseNumber(),
                    'postalCode' => $event->getLocation()->getZip(),
                    'addressLocality' => $event->getLocation()->getCity()
                ]
            ];
            if ($event->getLocation()->getLink() instanceof Link) {
                $this->data['location']['url'] = $this->getUrlFromParameter($event->getLocation()->getLink()->getLink());
            }
        }
    }

    /**
     * Add Organizer to data
     *
     * @link: https://schema.org/Organization
     * @param Event $event
     */
    protected function addOrganizerToData(Event $event): void
    {
        if ($event->getOrganizer() instanceof Organizer) {
            $this->data['organizer'] = [
                '@type' => 'Organization',
                'name' => $event->getOrganizer()->getOrganizer(),
            ];
            if ($event->getOrganizer()->getLink() instanceof Link) {
                $this->data['organizer']['url'] = $this->getUrlFromParameter($event->getOrganizer()->getLink()->getLink());
            }
        }
    }

    /**
     * Add image to data
     *
     * @link: https://schema.org/ImageObject
     * @param Event $event
     */
    protected function addImageToData(Event $event): void
    {
        if (!empty($event->getImages())) {
            $image = $event->getImages()[0];
            if (!$image instanceof FileReference) {
                return;
            }

            $resource = $image->getOriginalResource();
            if (!$resource instanceof \TYPO3\CMS\Core\Resource\FileReference) {
                return;
            }

            $url = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath()));
            $url .= $resource->getPublicUrl(false);

            $this->data['image'] = [
                '@type' => 'ImageObject',
                'caption' => $resource->getTitle(),
                'contentSize' => $resource->getSize(),
                'contentUrl' => $url,
                'url' => rawurldecode(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')),
                'description' => $resource->getDescription()
            ];
        }
    }

    /**
     * Create URL from parameter
     *
     * @param string $parameter
     * @return string
     */
    protected function getUrlFromParameter(string $parameter): string
    {
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $contentObject->typoLink_URL(
            [
                'parameter' => $parameter,
                'forceAbsoluteUrl' => true
            ]
        );
    }
}
