<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DigitalMarketingPlatformRemark extends Model
{
    protected $fillable = [
        'digital_marketing_platform_id',
        'remarks',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
