<?php
namespace Novanova\Tests;

use Novanova\IPGeoBase\CSVIterator;
use Novanova\IPGeoBase\CidrIterator;

/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class CidIteratorTest extends TestCase
{
    public function testGenerator() {
        $cidr = base_path('src/Novanova/IPGeoBase/cidr_optim.txt');

        $iterator = (new CidrIterator($cidr, "\t"))->getGenerator();
        $csv = (new CSVIterator($cidr, "\t"))->getGenerator();

        $csv->rewind();

        foreach ($iterator as $item) {
            $this->assertTrue($csv->valid());
            $expected = $csv->current();

            $this->assertCount(6, $item);
            $this->assertNotEquals('-', $item[5]);

            $actual = [
                $item[0], $item[1], "{$item[2]} - {$item[3]}", $item[4], empty($item[5]) ? ('-') : ($item[5])
            ];

            $this->assertSame($expected, $actual);
            break;
        }
    }
}