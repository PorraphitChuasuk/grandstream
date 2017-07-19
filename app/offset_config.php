<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class offset_config extends Model
{
    protected $guarded = [];

    public static function get_offset($name, $country) {
        $data = self::_get_data($name, $country);
        if ($data != null) {
            return $data->offset;
        }
        return null;
    }

    public static function set_offset($name, $country, $offset) {
        $data = self::_get_data($name, $country);
        if ($data != null) {
            $data->offset = $offset;
            $data->save();
        }
    }

    private static function _get_data($name, $country) {
        return $data = self::where([
            ['name', '=', $name],
            ['country_code_id', '=', \App\country_code::get_country_id($country)]
        ])->first();
    }
}
