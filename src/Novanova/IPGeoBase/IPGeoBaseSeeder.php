<?php

namespace Novanova\IPGeoBase;

use Illuminate\Database\Seeder;

/**
 * Class IPGeoBaseSeeder
 * @package Novanova\IPGeoBase
 */
class IPGeoBaseSeeder extends Seeder
{

    public function run()
    {
        $citiesFile = config('ipgeobase.files.cities', __DIR__ . '/cities.txt');
        $cidrFile = config('ipgeobase.files.cidr', __DIR__ . '/cidr_optim.txt');

        $service = new IpGeoBaseService($citiesFile, $cidrFile);

        $service->import();
    }

}