<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Pavel Z
 */

declare(strict_types=1);

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Http\Header\AcceptHeader;
use Spiral\Http\Header\AcceptHeaderItem;

class AcceptHeaderTest extends TestCase
{
    public function testHeaderItemCompare(): void
    {
        $this->assertEquals(0, AcceptHeaderItem::compare('', ''));
        $this->assertEquals(0, AcceptHeaderItem::compare('test', 'test2'));
        $this->assertEquals(1, AcceptHeaderItem::compare('test', 'test2;q=0.7'));
        $this->assertEquals(-1, AcceptHeaderItem::compare('test; q=0.7', 'test2'));

        $this->assertEquals(0, AcceptHeaderItem::compare('test; q=0.7', 'test2; q=0.7'));
        $this->assertEquals(0, AcceptHeaderItem::compare('test; q=0.7', 'test2; q=0.7;p'));
        $this->assertEquals(0, AcceptHeaderItem::compare('test; q=0.7; test=23', 'test2; q=0.7;p=1'));
        $this->assertEquals(1, AcceptHeaderItem::compare('test; q=0.7; p=2; st=3', 'test2; q=0.7;p=1'));
        $this->assertEquals(-1, AcceptHeaderItem::compare('test; q=0.7', 'test2; q=0.7;p=1'));

        $this->assertEquals(1, AcceptHeaderItem::compare(
            AcceptHeaderItem::fromString('test'),
            AcceptHeaderItem::fromString('*')
        ));
    }

    public function testCompareHeaderItemValue(): void
    {
        $class = (new \ReflectionClass(AcceptHeaderItem::class));
        $compareValue = $class->getMethod('compareValue');
        $compareValue->setAccessible(true);

        $this->assertEquals(0, $compareValue->invokeArgs(null, ['', '']));
        $this->assertEquals(0, $compareValue->invokeArgs(null, ['*', '*']));
        $this->assertEquals(0, $compareValue->invokeArgs(null, ['UTF-8', 'UTF-8']));
        $this->assertEquals(1, $compareValue->invokeArgs(null, ['UTF-8', '*']));
        $this->assertEquals(-1, $compareValue->invokeArgs(null, ['*', 'UTF-8']));

        $this->assertEquals(0, $compareValue->invokeArgs(null, ['*/*', '*/*']));
        $this->assertEquals(1, $compareValue->invokeArgs(null, ['type/*', '*/*']));
        $this->assertEquals(1, $compareValue->invokeArgs(null, ['type/subtype', '*/*']));
        $this->assertEquals(-1, $compareValue->invokeArgs(null, ['*/*', 'type/*']));
        $this->assertEquals(-1, $compareValue->invokeArgs(null, ['*/*', 'type/subtype']));

        $this->assertEquals(0, $compareValue->invokeArgs(null, ['*/*', '*/*']));
        $this->assertEquals(0, $compareValue->invokeArgs(null, ['type/*', 'type/*']));
        $this->assertEquals(1, $compareValue->invokeArgs(null, ['type/subtype', 'type/*']));
        $this->assertEquals(-1, $compareValue->invokeArgs(null, ['type/*', 'type/subtype']));
    }

    public function testHeaderItem(): void
    {
        $item = new AcceptHeaderItem('text/html', 0.9, ['t' => 'test']);

        $this->assertEquals('text/html; q=0.9; t=test', $item);
        $this->assertEquals(0.9, $item->getQuality());
        $this->assertEquals(['t' => 'test'], $item->getParams());

        $item = $item->withValue('application/json');

        $this->assertEquals('application/json', $item->getValue());

        $item = $item->withQuality(0);

        $this->assertEquals(0, $item->getQuality());

        $item = $item->withParams(['n' => 'new']);

        $this->assertEquals(['n' => 'new'], $item->getParams());

        $wrongParamsItem = AcceptHeaderItem::fromString('text/html; q');

        $this->assertEquals([], $wrongParamsItem->getParams());
        $this->assertEquals(1.0, $wrongParamsItem->getQuality());

        AcceptHeaderItem::fromString('');
    }

    public function testHeaderConstructing(): void
    {
        $acceptCharset = new AcceptHeader(['*', 'UTF-8']);

        $this->assertFalse($acceptCharset->has('unicode'));
        $this->assertNull($acceptCharset->get('unicode'));

        $sorted = $acceptCharset->sorted();
        $this->assertEquals('UTF-8', $sorted[0]);
        $this->assertEquals('*', $sorted[1]);
        $this->assertEquals('UTF-8, *', $acceptCharset);

        $acceptCharset = $acceptCharset->add(AcceptHeaderItem::fromString('unicode'));

        $this->assertTrue($acceptCharset->has('unicode'));
        $this->assertNotNull($acceptCharset->get('unicode'));

        $sorted = $acceptCharset->sorted();
        $this->assertEquals('UTF-8', $sorted[0]);
        $this->assertEquals('unicode', $sorted[1]);
        $this->assertEquals('*', $sorted[2]);
        $this->assertEquals('UTF-8, unicode, *', $acceptCharset);

        $acceptHeader = AcceptHeader::fromString('text/html; q=0.8, */*; q=0.7');
        $acceptHeader = $acceptHeader->add('application/json');

        $sorted = $acceptHeader->sorted();
        $this->assertEquals('application/json', $sorted[0]);
        $this->assertEquals('text/html; q=0.8', $sorted[1]);
        $this->assertEquals('*/*; q=0.7', $sorted[2]);
    }

    public function testHeader1(): void
    {
        $acceptHeader = AcceptHeader::fromString('audio/*; q=0.2, audio/basic')->sorted();
        $this->assertEquals('audio/basic', (string) $acceptHeader[0]);
        $this->assertEquals('audio/*; q=0.2', (string) $acceptHeader[1]);
    }

    public function testHeader2(): void
    {
        $acceptHeader = AcceptHeader::fromString(
            'text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5'
        )->sorted();

        $this->assertEquals('text/html; level=1', $acceptHeader[0]);
        $this->assertEquals('text/html; q=0.7', $acceptHeader[1]);
        $this->assertEquals('*/*; q=0.5', $acceptHeader[2]);
        $this->assertEquals('text/html; q=0.4; level=2', $acceptHeader[3]);
        $this->assertEquals('text/*; q=0.3', $acceptHeader[4]);
    }

    public function testHeader3(): void
    {
        $acceptHeader = AcceptHeader::fromString('text/*, text/html, text/html;level=1, */*')->sorted();

        $this->assertEquals('text/html; level=1', $acceptHeader[0]);
        $this->assertEquals('text/html', $acceptHeader[1]);
        $this->assertEquals('text/*', $acceptHeader[2]);
        $this->assertEquals('*/*', $acceptHeader[3]);
    }

    public function testHeader4(): void
    {
        $acceptHeader = AcceptHeader::fromString('text, text/html');

        $this->assertEquals('text', (string) $acceptHeader->all()[0]);
        $this->assertEquals('text/html', (string) $acceptHeader->all()[1]);

        $this->assertEquals('text', (string) $acceptHeader->sorted()[0]);
        $this->assertEquals('text/html', (string) $acceptHeader->sorted()[1]);
    }
}
