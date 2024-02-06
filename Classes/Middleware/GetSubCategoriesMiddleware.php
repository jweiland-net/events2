<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Middleware;

use JWeiland\Events2\Domain\Repository\CategoryRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/*
 * This middleware is needed for management plugin. It is needed to get the sub-categories of a selected
 * main category.
 */
class GetSubCategoriesMiddleware implements MiddlewareInterface
{
    public function __construct(protected readonly CategoryRepository $categoryRepository)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getHeader('ext-events2') !== ['getSubCategories']) {
            return $handler->handle($request);
        }

        $categoryUid = (int)($request->getQueryParams()['events2Category'] ?? 0);
        if ($categoryUid === 0) {
            return new JsonResponse();
        }

        return new JsonResponse($this->reduceCategoryData(
            $this->categoryRepository->getSubCategories($categoryUid)
        ));
    }

    /**
     * @return string[]
     */
    protected function reduceCategoryData(QueryResultInterface $categories): array
    {
        $response = [];
        foreach ($categories as $category) {
            $response[] = [
                'uid' => $category->getUid(),
                'label' => $category->getTitle()
            ];
        }

        return $response;
    }
}
