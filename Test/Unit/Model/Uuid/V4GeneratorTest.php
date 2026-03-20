<?php
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Test\Unit\Model\Uuid;

use PHPUnit\Framework\TestCase;
use QuarryTeam\CustomerUuid\Model\Uuid\V4Generator;

class V4GeneratorTest extends TestCase
{
    public function testGenerateReturnsUuidV4Format(): void
    {
        $generator = new V4Generator();
        $uuid = $generator->generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testGenerateReturnsDifferentValues(): void
    {
        $generator = new V4Generator();
        $uuid1 = $generator->generate();
        $uuid2 = $generator->generate();

        $this->assertNotSame($uuid1, $uuid2);
    }
}

