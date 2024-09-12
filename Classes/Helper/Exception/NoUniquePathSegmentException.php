<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper\Exception;

/**
 * This exception will be thrown, if no unique or empty path_segment could be generated
 */
class NoUniquePathSegmentException extends \InvalidArgumentException {}
