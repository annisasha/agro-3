<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantHandling extends Model
{
    use HasFactory;

    protected $table = 'tr_plant_handling_copy';
    protected $primaryKey = 'hand_id';

    protected $fillable = [
        'pt_id',
        'hand_day',
        'hand_day_toleran',
        'hand_title',
        'hand_desc',
        'fertilizer_type',
        'dose'
    ];
}
