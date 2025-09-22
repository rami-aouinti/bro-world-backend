<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\General\Domain\Enum\Language;
use App\Tests\Utils\PhpUnitUtil;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @package App\Tests\Unit\Utils
 */
class PhpUnitUtilTest extends TestCase
{
    public function testGetValidValueReturnsConsistentEnumCase(): void
    {
        $method = new ReflectionMethod(PhpUnitUtil::class, 'getValidValue');
        $method->setAccessible(true);

        $firstValue = $method->invoke(null, null, Language::class);
        $secondValue = $method->invoke(null, null, Language::class);

        self::assertSame($firstValue, $secondValue);
    }
}
