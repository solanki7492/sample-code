<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DigitalMarketingUsp extends Model
{

	public $timestamps = false;
    protected $fillable = [
        'digital_marketing_platform_id',
        'name'
    ];
}
