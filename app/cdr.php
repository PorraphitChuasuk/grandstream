<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class cdr extends Model
{
    protected $guarded = [];
    protected $connection = 'sqlsrv';
}
