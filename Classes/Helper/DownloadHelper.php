<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Helper class to provide a string or file as download
 */
class DownloadHelper
{
    public function downloadFile(
        FileInterface $file = null,
        string $body = '',
        bool $asDownload = false,
        string $alternativeFilename = null,
        string $overrideMimeType = null,
    ): ResponseInterface {
        if ($file === null && $body === '') {
            throw new \InvalidArgumentException('Please provide either a file object or a string to download', 1639401496);
        }

        if ($file instanceof FileInterface) {
            return $file->getStorage()->streamFile($file, $asDownload, $alternativeFilename, $overrideMimeType);
        }

        if (empty($alternativeFilename)) {
            throw new \InvalidArgumentException('You want start a string download. Please provide alternative filename argument, as we can not extract a filename from file content', 1639401906);
        }

        $stream = new Stream('php://temp', 'rw');
        $stream->write($body);
        $headers = [
            'Content-Disposition' => 'attachment; filename="' . $alternativeFilename . '"',
            'Content-Type' => $overrideMimeType ?? 'text/plain',
            'Content-Length' => (string)strlen($body),
            'Last-Modified' => gmdate('D, d M Y H:i:s', time()) . ' GMT',
            // Cache-Control header is needed here to solve an issue with browser IE8 and lower
            // See for more information: http://support.microsoft.com/kb/323308
            'Cache-Control' => '',
        ];

        return new Response($stream, 200, $headers);
    }

    /**
     * Use this method to force a download. It throws a special exception which will break current TYPO3
     * process and jumps to a position, where the Response will be output directly.
     * Please use downloadFile, if you don't want to skip anything.
     *
     * @throws ImmediateResponseException
     */
    public function forceDownloadFile(
        FileInterface $file = null,
        string $body = '',
        bool $asDownload = false,
        string $alternativeFilename = null,
        string $overrideMimeType = null,
    ): Response {
        throw new ImmediateResponseException(
            $this->downloadFile($file, $body, $asDownload, $alternativeFilename, $overrideMimeType),
            1639402254,
        );
    }
}
