<?php
namespace Novanova\Tests;

use Illuminate\Support\Facades\DB;
use Novanova\IPGeoBase\CSVIterator;
use Novanova\IPGeoBase\IpGeoBaseService;
use Novanova\IPGeoBase\CityData;

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
        $this->artisan('migrate', ['--path' => 'src/migrations']);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }

    public function testImport() {
        $cities = base_path('src/Novanova/IPGeoBase/cities.txt');
        $cidr = base_path('src/Novanova/IPGeoBase/cidr_optim.txt');

        $service = new IpGeoBaseService();
        $service->import($cities, $cidr, [
            'count' => 1
        ]);

        $csv = new CSVIterator($cities, "\t");

        foreach ($csv->getGenerator() as $source) {
            $row = array_combine([
                'id', 'city', 'region', 'district', 'lat', 'lng'
            ], $source);

            $this->seeInDatabase('ip_geo_base__cities', $row);
        }
    }

    public function testGet() {
        DB::table('ip_geo_base__cities')->insert([
            'id' => 1959,
            'city' => 'Нижневартовск',
            'region' => 'Ханты-Мансийский автономный округ',
            'district' => 'Уральский федеральный округ',
            'lat' => 60.948009,
            'lng' => 76.555847
        ]);

        DB::table('ip_geo_base__base')->insert([
            'long_ip1' => 94265344,
            'long_ip2' => 94273535,
            'ip1' => '5.158.96.0',
            'ip2' => '5.158.127.255',
            'country' => 'RU',
            'city_id' => 1959
        ]);

        $service = new IpGeoBaseService();

        /**
         * @var CityData $data
         */
        $data = $service->get('5.158.96.10');

        $this->assertEquals('RU', $data->country);
        $this->assertEquals('Нижневартовск', $data->city);
    }
}