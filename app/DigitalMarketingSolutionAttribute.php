<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DigitalMarketingSolutionAttribute extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'digital_marketing_solution_id',
        'key',
        'value',
    ];

}
