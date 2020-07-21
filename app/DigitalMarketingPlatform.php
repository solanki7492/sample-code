<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DigitalMarketingPlatform extends Model
{

    const STATUS = [
        0 => "Draft",
        1 => "Active",
        2 => "Inactive",
        3 => "Planned",
        4 => "Do not need",
    ];

    protected $fillable = [
        'platform',
        'sub_platform',
        'description',
        'status',
        'created_at',
        'updated_at',
    ];

    public function solutions()
    {
        return $this->hasMany("\App\DigitalMarketingSolution", "digital_marketing_platform_id", "id");
    }

    public function components()
    {
        return $this->hasMany("App\DigitalMarketingPlatformComponent", "digital_marketing_platform_id", "id");
    }
}
