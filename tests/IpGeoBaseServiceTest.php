<?php
namespace Novanova\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Novanova\IPGeoBase\CSVIterator;
use Novanova\IPGeoBase\IpGeoBaseService;

/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class IpGeoBaseServiceTest extends TestCase
{
    /**
     * @before
     */
    public function runDatabaseMigrations()
    {
        file_put_contents('/tmp/test.sqlite', '');
        $this->artisan('migrate', ['--path' => 'src/migrations']);

//        $this->beforeApplicationDestroyed(function () {
//            $this->artisan('migrate:rollback');
//        });
    }

    public function testImport() {
        $cities = base_path('src/Novanova/IPGeoBase/cities.txt');
        $cidr = base_path('src/Novanova/IPGeoBase/cidr_optim.txt');

        $service = new IpGeoBaseService($cities, $cidr);

        $service->import();

        $csv = new CSVIterator($cities, "\t");

        foreach ($csv->getGenerator() as $source) {
            $row = array_combine([
                'id', 'city', 'region', 'district', 'lat', 'lng'
            ], $source);

            $this->seeInDatabase('ip_geo_base__cities', $row);
        }
    }
}