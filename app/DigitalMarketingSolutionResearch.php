<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DigitalMarketingSolutionResearch extends Model
{

    public $timestamps = false;

    const PRIORITY = [
        0 => "Low",
        1 => "Normal",
        2 => "High",
    ];

    protected $fillable = [
        'subject',
        'description',
        'remarks',
        'priority',
        'digital_marketing_solution_id',
    ];

}
