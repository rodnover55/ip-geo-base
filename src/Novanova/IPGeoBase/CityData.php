<?php
namespace Novanova\IPGeoBase;

/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class CityData
{
    public $city;
    public $country;

    public function __construct($country, $city) {
        $this->country = $country;
        $this->city = $city;
    }
}