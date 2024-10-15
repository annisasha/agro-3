<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Plant extends Model
{
    use HasFactory;

    protected $table = 'tm_plant'; 
    protected $primaryKey = 'pl_id'; 


    // Method untuk menghitung umur tanaman
    public function age()
    {
        // return now()->diffInDays(Carbon::parse($this->pl_date_planting));

        $plantingDate = strtotime($this->pl_date_planting);
        $currentDate = time(); 
        $age = ($currentDate - $plantingDate) / (60 * 60 * 24); 
        return max(0, floor($age));
    }

    // Method untuk menentukan fase tanaman
    public function phase()
    {
        $age = $this->age();
    
        if ($age <= 35) {
            return 'Vegetatif Awal (V1)';
        } elseif ($age > 35 && $age <= 55) {
            return 'Vegetatif Akhir (V2)';
        } elseif ($age > 55 && $age <= 85) {
            return 'Reproduktif (G1)';
        } elseif ($age > 85 && $age <= 120) {
            return 'Pematangan (G2)';
        } else {
            return 'Panen';
        }
    }

    // Method untuk menghitung waktu menuju panen
    public function timetoHarvest()
    {
        return 120 - $this->age();
    }
}