<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class country_code extends Model
{
    protected $guarded = [];

    public static function get_country_id($country) {
        $data = self::where('country_code', $country)->first();
        if ($data != null) {
            return $data->id;
        }
        return null;
    }

    public static function get_country_name($country_id) {
        $data = self::where('id', $country_id)->first();
        if ($data != null) {
            return ucwords($data->country_code);
        }
        return null;
    }
}
