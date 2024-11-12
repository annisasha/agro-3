<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorDevice extends Model
{
  
    protected $table = 'tr_unit';
    protected $primaryKey = 'unit_id';
    public $timestamps = false; 

    protected $fillable = [
        'unit_id',
        'unit_name',
        'unit_name_idn',
        'unit_symbol',
        'unit_sts',
        'unit_update',
        'area',
        'active',
        'min_norm_value',
        'max_norm_value',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class, 'dev_id', 'dev_id');
    }
}
