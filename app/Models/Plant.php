<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;

    protected $table = 'tm_plant';
    protected $primaryKey = 'pl_id';

    public function plantType()
    {
        return $this->belongsTo(PlantType::class, 'pt_id', 'pt_id');
    }

    // Untuk menghitung umur tanaman
    public function age()
    {
        $plantingDate = strtotime($this->pl_date_planting);
        $currentDate = time();
        $age = ($currentDate - $plantingDate) / (60 * 60 * 24);
        return max(0, floor($age));
    }

    //  Untuk menentukan fase tanaman
    public function phase()
    {
        // Ambil data hari panen dari relasi plantType
        $harvestDays = $this->plantType->pt_day_harvest;

        $age = $this->age();

        if ($this->pt_id == 'PT01') { // Khusus untuk padi (PT01)
            if ($age <= 35) {
                return 'Vegetatif Awal (V1)';
            } elseif ($age > 35 && $age <= 55) {
                return 'Vegetatif Akhir (V2)';
            } elseif ($age > 55 && $age <= 85) {
                return 'Reproduktif (G1)';
            } elseif ($age > 85 && $age <= $harvestDays) {
                return 'Pematangan (G2)';
            } else {
                return 'Panen';
            }
        } else {
            return 'Fase tidak dikenali';
        }
    }

    // Untuk menghitung waktu menuju panen
    public function timetoHarvest()
    {
        // Ambil data pt_day_harvest dari relasi plantType
        $harvestDays = $this->plantType->pt_day_harvest;
        return max(0, $harvestDays - $this->age());
    }

    public function getCommodityVariety()
    {
        // Memisahkan nama tanaman menjadi komoditas dan varietas
        $parts = explode(' ', $this->pl_name, 2);

        $commodity = $parts[0] ?? null;
        $variety = $parts[1] ?? null;

        return [
            'commodity' => $commodity,
            'variety' => $variety
        ];
    }
}
