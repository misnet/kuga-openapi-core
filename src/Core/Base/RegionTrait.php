<?php
namespace Kuga\Core\Base;
trait  RegionTrait{

    /**
     * 国家 id
     * @var string
     */
    public $countryId = 0;
    /**
     * 省份ID
     * @var integer
     */
    public $provinceId;
    /**
     * 城市ID
     * @var
     */
    public $cityId;
    /**
     * 镇或街道
     * @var
     */
    public $townId;
    /**
     * 县或区
     * @var
     */
    public $countyId;

    public function regionColumnMapping(){
        return [
            'country_id'=>'countryId',
            'province_id'=>'provinceId',
            'city_id'=>'cityId',
            'town_id'=>'townId',
            'county_id'=>'countyId'
        ];
    }
}
