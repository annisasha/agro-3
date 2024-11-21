<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorDevice extends Model
{
    protected $table = 'td_device_sensor';
    protected $primaryKey = 'ds_id';
    protected $keyType = 'string';

    public $timestamps = false; 

    protected $fillable = [
        'ds_id',
        'dev_id',
        'unit_id',
        'dc_normal_value',
        'ds_min_norm_value',
        'ds_max_norm_value',
        'ds_min_value',
        'ds_max_value',
        'ds_min_val_warn',
        'ds_max_val_warn',
        'ds_name',
        'ds_address',
        'ds_seq',
        'ds_sts',
        'ds_update'
    ];

    protected static function booted()
    {
        static::creating(function ($sensor) {
            $sensor->ds_update = now();
        });

        static::updating(function ($sensor) {
            $sensor->ds_update = now();
        });
    }
}
