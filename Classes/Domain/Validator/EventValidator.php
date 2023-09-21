<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Validator;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validates an event while create and update action
 */
class EventValidator extends AbstractValidator
{
    /**
     * Checks, if the given time model is empty or its sub-property timeBegin is filled.
     *
     * @param mixed $value
     */
    public function isValid($value): void
    {
        if (!$value instanceof Event) {
            return;
        }

        $this->removeEventTimeIfEmpty($value);
        $this->removeVideoLinkIfEmpty($value);
        $this->checkVideoLinkForYouTube($value);
    }

    protected function removeEventTimeIfEmpty(Event $event): void
    {
        if (
            ($time = $event->getEventTime())
            && $time instanceof Time
            && $time->getTimeBegin() === ''
        ) {
            $event->setEventTime(null);
            $this->getPersistenceManager()->remove($time);
        }
    }

    protected function removeVideoLinkIfEmpty(Event $event): void
    {
        if (
            ($link = $event->getVideoLink())
            && $link instanceof Link
            && $link->getLink() === ''
        ) {
            $event->setVideoLink(null);
            $this->getPersistenceManager()->remove($link);
        }
    }

    protected function checkVideoLinkForYouTube(Event $event): void
    {
        if (
            ($link = $event->getVideoLink())
            && $link instanceof Link
            && ($uri = $link->getLink())
            && $uri !== ''
        ) {
            if (
                !preg_match(
                    '~^(|http:|https:)//(|www.)youtube(.*?)(v=|embed/)([a-zA-Z0-9_-]+)~i',
                    $uri
                )
            ) {
                $this->addError(
                    $this->translateErrorMessage(
                        'validator.event.videoLink.notYouTube',
                        'events2'
                    ),
                    1647875338
                );
            }
        }
    }

    public function getPersistenceManager(): PersistenceManagerInterface
    {
        return $this->getObjectManager()->get(PersistenceManagerInterface::class);
    }

    public function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
