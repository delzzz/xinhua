<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'region';
    protected $primaryKey='region_id';
    public $timestamps = false;


    //查询地区名
    public function regionName($regionId)
    {
        $regionArr = explode(',', $regionId);
        $regionStr = '';
        foreach ($regionArr as $key => $value) {
            $region = Region::find($value);
            $regionStr .= $region['name'];
        }
        return $regionStr;
    }

    //根据countyId县查出省市县
    function getFullName($countyId){
        if(!empty($countyId)){
            $county = Region::find($countyId);
            $countyName = $county->name;
            $cityId = $county->parent_id;
            $city = Region::find($cityId);
            $provinceId = $city->parent_id;
            $cityName = $city->name;
            $province = Region::find($provinceId);
            $provinceName = $province->name;
            return $provinceName.','.$cityName.','.$countyName;
        }
        else{
            return null;
        }

    }

}
