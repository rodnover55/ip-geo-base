<?php
namespace Novanova\IPGeoBase;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class IpGeoBaseService
{
    private $citiesFile;
    private $cidrFile;
    private $count;
    private $limit;

    public function get($ip) {
        $longIp = ip2long($ip);

        $result = DB::table('ip_geo_base__base')->where($longIp, '>', DB::raw('ip_geo_base__base.long_ip1'))
            ->where($longIp, '<', DB::raw('ip_geo_base__base.long_ip2'))
            ->leftJoin('ip_geo_base__cities', 'ip_geo_base__cities.id', '=', 'ip_geo_base__base.city_id')
            ->first();

        return new CityData($result['country'], $result['city']);
    }

    public function import($citiesFile, $cidrFile, $config = []) {
        $this->setFiles($citiesFile, $cidrFile);
        $this->setConfig($config);

        DB::transaction(function () {
            $this->importCities();
            $this->importCidr();
        });
    }

    protected function setConfig($config) {
        $this->count = array_get($config, 'count', 1000);
        $this->limit = array_get($config, 'limit', 10000);
    }

    protected function setFiles($citiesFile, $cidrFile) {
        $this->citiesFile = $citiesFile;
        $this->cidrFile = $cidrFile;

        if (!file_exists($this->citiesFile)) {
            throw new FileNotFoundException("file '{$this->citiesFile}' not found.");
        }

        if (!file_exists($this->cidrFile)) {
            throw new FileNotFoundException("file '{$this->cidrFile}' not found.");
        }

    }


    protected function importCities() {
        $csv = new CSVIterator($this->citiesFile, "\t");

        $differ = new DatabaseDiffer($csv->getGenerator(), [
            'id', 'city', 'region', 'district', 'lat', 'lng'
        ], ['id'], [
            'count' => $this->count,
            'limit' =>  $this->limit
        ]);

        $differ->getDiff(DB::table('ip_geo_base__cities')
                ->select('id', 'city', 'region', 'district', 'lat', 'lng')->orderBy('id'),
            function ($operation, $items) {
                switch ($operation) {
                    case DatabaseDiffer::INSERTED:
                        DB::table('ip_geo_base__cities')->insert($items[0]);
                        break;
                    case DatabaseDiffer::UPDATED:
                        foreach ($items as $item) {
                            DB::table('ip_geo_base__cities')->where('id', $items['id'])->update($item);
                        }

                        break;
                    case DatabaseDiffer::DELETED:
                        $ids = array_pluck($items, 'id');
                        DB::table('ip_geo_base__cities')->delete($ids);
                        break;
                }
            }
        );
    }

    protected function importCidr() {
        $csv = new CidrIterator($this->cidrFile, "\t");

        $differ = new DatabaseDiffer($csv->getGenerator(), [
            'long_ip1', 'long_ip2', 'ip1', 'ip2', 'country', 'city_id'
        ], ['long_ip1', 'long_ip2', 'ip1', 'ip2', 'country', 'city_id'], [
            'count' => $this->count,
            'limit' =>  $this->limit
        ]);

        $differ->getDiff(DB::table('ip_geo_base__base')
                ->select('long_ip1', 'long_ip2', 'ip1', 'ip2', 'country', 'city_id')
                ->orderBy('long_ip1')
                ->orderBy('long_ip2')
                ->orderBy('ip1')
                ->orderBy('ip2')
                ->orderBy('country')
                ->orderBy('city_id'),
            function ($operation, $items) {
                switch ($operation) {
                    case DatabaseDiffer::INSERTED:
                        DB::table('ip_geo_base__base')->insert($items[0]);
                        break;
                    case DatabaseDiffer::UPDATED:
                        foreach ($items as $item) {
                            DB::table('ip_geo_base__base')->where('id', $items['id'])->update($item);
                        }

                        break;
                    case DatabaseDiffer::DELETED:
                        $ids = array_pluck($items, 'id');
                        DB::table('ip_geo_base__base')->delete($ids);
                        break;
                }
            }
        );
    }
}