<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Exporter;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Service\EventService;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\Site\SiteFinder;

class EventsExporter
{
    public function __construct(
        protected readonly EventService $eventService,
        protected readonly SiteFinder $siteFinder,
        protected readonly TimeFactory $timeFactory,
        protected readonly RequestFactory $requestFactory,
        protected readonly LinkService $linkService,
        protected readonly DateTimeUtility $dateTimeUtility,
        protected readonly TypoLinkCodecService $typoLinkCodecService,
        protected readonly LoggerInterface $logger,
    ) {}

    public function export(ExporterConfiguration $configuration): ResponseInterface
    {
        try {
            $preparedEvents = $this->getPreparedEvents(
                $this->eventService->getEventsForExport(
                    $configuration->getStoragePages(),
                    $this->getMaxDateForEventsExport(),
                ),
                $configuration->getStoragePages(),
            );

            return $this->requestFactory->request(
                $configuration->getUrl(),
                'POST',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'x-api-key' => $configuration->getSecret(),
                    ],
                    'body' => json_encode($preparedEvents, JSON_THROW_ON_ERROR),
                ],
            );
        } catch (\Exception $e) {
        }

        $this->logger->error($e->getMessage());

        return new JsonResponse([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }

    protected function getMaxDateForEventsExport(): ?\DateTimeImmutable
    {
        $endRange = $this->dateTimeUtility->convert('today');

        return $endRange?->modify('+1 year');
    }

    /**
     * @param Event[] $events
     */
    protected function getPreparedEvents(array $events, array $storagePages): array
    {
        $preparedEvents = [];
        foreach ($events as $event) {
            $preparedEvent = $this->getEventPreparedForExport($event, $storagePages);
            if ($preparedEvent === []) {
                continue;
            }

            $preparedEvents[] = $preparedEvent;
        }

        return $preparedEvents;
    }

    /**
     * We don't export everything. There is no need to export any tstamp, crdate or user column.
     * Just collect an array for export with useful/needed values.
     */
    protected function getEventPreparedForExport(Event $event, array $storagePages): array
    {
        $eventBegin = $event->getEventBegin();

        // Early return, if there is no event begin
        if ($eventBegin === null) {
            $this->logger->warning('Event begin is empty. Skipping event record: ' . $event->getUid());
            return [];
        }

        $eventRecord = [
            'uid' => $event->getUid(),
            'crdate' => $this->formatDateToISO($event->getCrdate()),
            'tstamp' => $this->formatDateToISO($event->getTstamp()),
            'starttime' => $this->formatDateToISO($event->getStarttime()),
            'endtime' => $this->formatDateToISO($event->getEndtime()),
            'hidden' => $event->getHidden(),
            'deleted' => $event->getDeleted(),
            'event_type' => $event->getEventType(),
            'top_of_list' => $event->getTopOfList(),
            'title' => $event->getTitle(),
            'path_segment' => $event->getPathSegment(),
            'event_begin' => $this->formatDateToISO($eventBegin),
            'event_time' => $this->createTimeRecord($event->getEventTime()),
            'event_end' => $this->formatDateToISO($event->getEventEnd()),
            'same_day' => $event->getSameDay(),
            'multiple_times' => [],
            'xth' => $event->getXth(),
            'weekday' => $event->getWeekday(),
            'different_times' => [],
            'each_weeks' => $event->getEachWeeks(),
            'each_months' => $event->getEachMonths(),
            'recurring_end' => $this->formatDateToISO($event->getRecurringEnd()),
            'exceptions' => [],
            'teaser' => $event->getTeaser(),
            'detail_information' => $event->getDetailInformation(),
            'free_entry' => $event->getFreeEntry(),
            'ticket_link' => [],
            'categories' => [],
            'location' => [],
            'organizers' => [],
            'images' => [],
            'video_link' => [],
            'download_links' => [],
        ];

        $this->addMultipleTimes($eventRecord, $event);
        $this->addDifferentTimes($eventRecord, $event);
        $this->addExceptions($eventRecord, $event);
        $this->addTicketLink($eventRecord, $event);
        $this->addCategories($eventRecord, $event);
        $this->addLocation($eventRecord, $event);
        $this->addOrganizers($eventRecord, $event);
        $this->addImages($eventRecord, $event, $storagePages);
        $this->addVideoLink($eventRecord, $event);
        $this->addDownloadLinks($eventRecord, $event, $storagePages);

        return $eventRecord;
    }

    protected function addMultipleTimes(array &$eventRecord, Event $event): void
    {
        foreach ($event->getMultipleTimes() as $time) {
            $eventRecord['multiple_times'][] = $this->createTimeRecord($time);
        }
    }

    protected function addDifferentTimes(array &$eventRecord, Event $event): void
    {
        foreach ($event->getDifferentTimes() as $time) {
            $eventRecord['different_times'][] = $this->createTimeRecord($time);
        }
    }

    protected function addExceptions(array &$eventRecord, Event $event): void
    {
        foreach ($event->getExceptions() as $exception) {
            $eventRecord['exceptions'][] = [
                'uid' => $exception->getUid(),
                'crdate' => $this->formatDateToISO($exception->getCrdate()),
                'tstamp' => $this->formatDateToISO($exception->getTstamp()),
                'starttime' => $exception->getStarttime(),
                'endtime' => $exception->getEndtime(),
                'hidden' => $exception->getHidden(),
                'deleted' => $exception->getDeleted(),
                'exception_type' => $exception->getExceptionType(),
                'exception_date' => $this->formatDateToISO($exception->getExceptionDate()),
                'exception_time' => $this->createTimeRecord($exception->getExceptionTime()),
                'exception_details' => $exception->getExceptionDetails(),
                'show_anyway' => $exception->getShowAnyway(),
                'mark_as' => $exception->getMarkAs(),
            ];
        }
    }

    protected function addTicketLink(array &$eventRecord, Event $event): void
    {
        if ($event->getTicketLink()) {
            $eventRecord['ticket_link'] = $this->buildLink($event->getTicketLink());
        }
    }

    protected function addCategories(array &$eventRecord, Event $event): void
    {
        $categories = [];
        foreach ($event->getCategories() as $category) {
            $categories[] = [
                'uid' => $category->getUid(),
                'title' => $category->getTitle(),
                'description' => $category->getDescription(),
            ];
        }

        $eventRecord['categories'] = $categories;
    }

    protected function addLocation(array &$eventRecord, Event $event): void
    {
        if ($event->getLocation() instanceof Location) {
            $eventRecord['location'] = [
                'uid' => $event->getLocation()->getUid(),
                'crdate' => $this->formatDateToISO($event->getLocation()->getCrdate()),
                'tstamp' => $this->formatDateToISO($event->getLocation()->getTstamp()),
                'starttime' => $event->getLocation()->getStarttime(),
                'endtime' => $event->getLocation()->getEndtime(),
                'hidden' => $event->getLocation()->getHidden(),
                'deleted' => $event->getLocation()->getDeleted(),
                'location' => $event->getLocation()->getLocation(),
                'street' => $event->getLocation()->getStreet(),
                'houseNumber' => $event->getLocation()->getHouseNumber(),
                'zip' => $event->getLocation()->getZip(),
                'city' => $event->getLocation()->getCity(),
            ];
        }
    }

    protected function addOrganizers(array &$eventRecord, Event $event): void
    {
        foreach ($event->getOrganizers() as $organizer) {
            $eventRecord['organizers'][] = [
                'uid' => $organizer->getUid(),
                'crdate' => $this->formatDateToISO($organizer->getCrdate()),
                'tstamp' => $this->formatDateToISO($organizer->getTstamp()),
                'starttime' => $organizer->getStarttime(),
                'endtime' => $organizer->getEndtime(),
                'hidden' => $organizer->getHidden(),
                'deleted' => $organizer->getDeleted(),
                'organizer' => $organizer->getOrganizer(),
                'link' => $this->buildLink($organizer->getLink()),
            ];
        }
    }

    protected function addImages(array &$eventRecord, Event $event, array $storagePages): void
    {
        $baseUrl = $this->getBaseUrlForImages($storagePages);

        $images = [];
        foreach ($event->getImages() as $fileReference) {
            try {
                $publicLink = sprintf(
                    '%s/%s',
                    $baseUrl,
                    $fileReference->getOriginalResource()->getOriginalFile()->getPublicUrl(),
                );
                $images[] = [
                    'url' => $publicLink,
                ];
            } catch (\Exception $e) {
                $this->logger->error(
                    $e->getMessage(),
                    [
                        'event' => $event->getUid(),
                        'file_ref' => $fileReference->getUid(),
                    ],
                );
                continue;
            }
        }

        $eventRecord['images'] = $images;
    }

    protected function addVideoLink(array &$eventRecord, Event $event): void
    {
        if ($event->getVideoLink()) {
            $eventRecord['video_link'] = $this->buildLink($event->getVideoLink());
        }
    }

    protected function addDownloadLinks(array &$eventRecord, Event $event, array $storagePages): void
    {
        $downloadLinks = [];
        foreach ($event->getDownloadLinks() as $linkForDownload) {
            $downloadLinks[] = $this->buildLink($linkForDownload, $storagePages);
        }

        $eventRecord['download_links'] = $downloadLinks;
    }

    /**
     * Since using typolink for URIs we can not use $this->contentObjectRenderer->typoLink_URL anymore, as
     * it needs a domain name which we do not have in CLI mode. Further we have installed jumpurl which hooks
     * into typolink process and breaks at invalid HTTP_HOST.
     * That's why we have to build our own URI.
     */
    protected function buildLink(?Link $link, array $storagePages = []): array
    {
        if ($link === null) {
            return [];
        }

        $linkRecord = [
            'uid' => $link->getUid(),
            'crdate' => $this->formatDateToISO($link->getCrdate()),
            'tstamp' => $this->formatDateToISO($link->getTstamp()),
            'starttime' => $link->getStarttime(),
            'endtime' => $link->getEndtime(),
            'hidden' => $link->getHidden(),
            'deleted' => $link->getDeleted(),
            'title' => $link->getTitle(),
        ];

        try {
            $typoLinkParts = $this->typoLinkCodecService->decode($link->getLink());
            $linkInformation = $this->linkService->resolve($typoLinkParts['url']);
            switch ($linkInformation['type']) {
                case 'url':
                    $linkRecord['link'] = $linkInformation['url'];
                    break;
                case 'file':
                    $baseUrl = $this->getBaseUrlForImages($storagePages);
                    $linkRecord['link'] = $baseUrl . '/' . $linkInformation['file']->getPublicUrl();
                    break;
                default:
                    $linkRecord['link'] = '';
                    break;
            }
        } catch (UnknownLinkHandlerException $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'link' => $link->getUid(),
                ],
            );
            $linkRecord = [];
        }

        return $linkRecord;
    }

    protected function getBaseUrlForImages(array $storagePages): string
    {
        // Use first storage page to retrieve the public base URL
        $firstStoragePage = (int)current($storagePages);
        try {
            $site = $this->siteFinder->getSiteByPageId($firstStoragePage);

            // getBase may add query (?) and fragment (#). Remove them from URL
            $baseUrl = (string)$site->getBase();
            $pos = strpos($baseUrl, '?');
            if ($pos !== false) {
                $baseUrl = substr($baseUrl, 0, $pos);
            }

            return $baseUrl;
        } catch (SiteNotFoundException $e) {
            $this->logger->error($e->getMessage(), [
                'firstStoragePage' => $firstStoragePage,
            ]);
        }

        return '';
    }

    protected function formatDateToISO(?\DateTimeImmutable $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->format('c');
    }

    protected function createTimeRecord(?Time $time): array
    {
        if ($time === null) {
            return [];
        }

        return [
            'uid' => $time->getUid(),
            'crdate' => $this->formatDateToISO($time->getCrdate()),
            'tstamp' => $this->formatDateToISO($time->getTstamp()),
            'starttime' => $time->getStarttime(),
            'endtime' => $time->getEndtime(),
            'hidden' => $time->getHidden(),
            'deleted' => $time->getDeleted(),
            'weekday' => $time->getWeekday(),
            'time_begin' => $time->getTimeBegin(),
            'time_entry' => $time->getTimeEntry(),
            'duration' => $time->getDuration(),
            'time_end' => $time->getTimeEnd(),
        ];
    }
}
