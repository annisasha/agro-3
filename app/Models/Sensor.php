<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    protected $table = 'tm_sensor_read';
    protected $primaryKey = 'read_id'; 

}
