<?php

declare(strict_types=1);

namespace App\Tests\Unit\General\Transport\Rest;

use App\General\Transport\Rest\RequestHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @package App\Tests\Unit
 */
class RequestHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        RequestHandler::setAllowedTenants([]);
    }

    public function testGetTenantReturnsValidTenant(): void
    {
        RequestHandler::setAllowedTenants(['default', 'secondary']);
        $request = new Request(['tenant' => 'secondary']);

        self::assertSame('secondary', RequestHandler::getTenant($request));
    }

    public function testGetTenantThrowsOnUnknownTenant(): void
    {
        RequestHandler::setAllowedTenants(['default']);
        $request = new Request(['tenant' => 'unknown']);

        try {
            RequestHandler::getTenant($request);
            self::fail('Expected HttpException was not thrown.');
        } catch (HttpException $exception) {
            self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
            self::assertSame("Unknown tenant 'unknown'.", $exception->getMessage());
        }
    }

    public function testGetSearchTermsNormalizesDuplicateAndEmptyValues(): void
    {
        $request = new Request([
            'search' => json_encode([
                'and' => ['foo', '', 'bar', 'foo'],
                'or' => ['baz', 'baz', '', 'qux'],
            ]),
        ]);

        $expected = [
            'and' => ['foo', 'bar'],
            'or' => ['baz', 'qux'],
        ];

        self::assertSame($expected, RequestHandler::getSearchTerms($request));
    }
}
