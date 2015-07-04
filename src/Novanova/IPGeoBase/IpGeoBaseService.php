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

    public function __construct($citiesFile, $cidrFile) {
        $this->citiesFile = $citiesFile;
        $this->cidrFile = $cidrFile;

        if (!file_exists($this->citiesFile)) {
            throw new FileNotFoundException("file '{$this->citiesFile}' not found.");
        }

        if (!file_exists($this->cidrFile)) {
            throw new FileNotFoundException("file '{$this->cidrFile}' not found.");
        }
    }

    public function import() {
        DB::transaction(function () {
            $csv = new CSVIterator($this->citiesFile, "\t");

            $differ = new DatabaseDiffer($csv->getGenerator(), [
                'id', 'city', 'region', 'district', 'lat', 'lng'
            ], ['id'], [
                'count' => 1
            ]);

            $differ->getDiff(DB::table('ip_geo_base__cities')
                ->select('id', 'city', 'region', 'district', 'lat', 'lng'), function($operation, $items) {

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
            });
        });
//
//
//        DB::table('ip_geo_base__cities')->delete();
//
//        foreach ($file as $row) {
//            if (preg_match($pattern, $row, $out)) {
//                DB::table('ip_geo_base__cities')->insert(
//                    array(
//                        'id' => $out[1],
//                        'city' => $out[2],
//                        'region' => $out[3],
//                        'district' => $out[4],
//                        'lat' => $out[5],
//                        'lng' => $out[6],
//                        'country' => ''
//                    )
//                );
//            }
//        }
//
//        $file = file($cidrFile);
//        $pattern = '#(\d+)\s+(\d+)\s+(\d+\.\d+\.\d+\.\d+)\s+-\s+(\d+\.\d+\.\d+\.\d+)\s+(\w+)\s+(\d+|-)#';
//
//        DB::table('ip_geo_base__base')->delete();
//
//        foreach ($file as $row) {
//            if (preg_match($pattern, $row, $out)) {
//                DB::table('ip_geo_base__base')->insert(
//                    array(
//                        'long_ip1' => $out[1],
//                        'long_ip2' => $out[2],
//                        'ip1' => $out[3],
//                        'ip2' => $out[4],
//                        'country' => $out[5],
//                        'city_id' => is_numeric($out[6]) && 0 < (int)$out[6] ? (int)$out[6] : null
//                    )
//                );
//            }
//        }
//
//        $cities = DB::table('ip_geo_base__cities')
//            ->join('ip_geo_base__base', 'ip_geo_base__cities.id', '=', 'ip_geo_base__base.city_id')
//            ->select('ip_geo_base__cities.id', 'ip_geo_base__base.country')->get();
//
//        foreach ($cities as $city) {
//            DB::table('ip_geo_base__cities')
//                ->where('id', $city->id)
//                ->update(array('country' => $city->country));
//        }

        DB::commit();

    }
}