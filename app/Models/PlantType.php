<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantType extends Model
{
    use HasFactory;

    protected $table = 'tr_plant_type';
    protected $primaryKey = 'pt_id';

    public function plants()
    {
        return $this->hasMany(Plant::class, 'pt_id', 'pt_id');
    }
}
