<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DigitalMarketingSolution extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'provider',
        'website',
        'contact',
        'digital_marketing_platform_id',
    ];

    public function attributes()
    {
        return $this->hasMany("App\DigitalMarketingSolutionAttribute", "digital_marketing_solution_id", "id");
    }

}
