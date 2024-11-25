<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'tm_device';

    public function sensors()
    {
        return $this->hasMany(Sensor::class, 'dev_id', 'dev_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id', 'site_id');
    }
}
