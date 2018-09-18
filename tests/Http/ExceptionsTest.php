<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Http\Exception\ClientException;
use Spiral\Http\Exception\ClientException\BadRequestException;
use Spiral\Http\Exception\ClientException\ForbiddenException;
use Spiral\Http\Exception\ClientException\NotFoundException;
use Spiral\Http\Exception\ClientException\ServerErrorException;
use Spiral\Http\Exception\ClientException\UnauthorizedException;

class ExceptionsTest extends TestCase
{
    public function testClientException()
    {
        $e = new ClientException();
        $this->assertSame(400, $e->getCode());
    }

    public function testNotFound()
    {
        $e = new NotFoundException();
        $this->assertSame(404, $e->getCode());
    }

    public function testBadRequest()
    {
        $e = new BadRequestException();
        $this->assertSame(400, $e->getCode());
    }

    public function testForbidden()
    {
        $e = new ForbiddenException();
        $this->assertSame(403, $e->getCode());
    }

    public function testUnauthorized()
    {
        $e = new UnauthorizedException();
        $this->assertSame(401, $e->getCode());
    }

    public function testServerError()
    {
        $e = new ServerErrorException();
        $this->assertSame(500, $e->getCode());
    }
}