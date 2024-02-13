<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use TYPO3\CMS\Core\Mail\MailMessage;

/**
 * Trait to inject MailMessage. Mostly used in controllers.
 */
trait InjectMailMessageTrait
{
    protected MailMessage $mailMessage;

    public function injectMailMessage(MailMessage $mailMessage): void
    {
        $this->mailMessage = $mailMessage;
    }
}
