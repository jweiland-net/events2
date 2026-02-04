<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model\Enums;

enum AttendanceModeEnum: int
{
    case EMPTY = 0;
    case IN_PERSON = 1;
    case ONLINE = 2;
    case HYBRID = 3;
}
