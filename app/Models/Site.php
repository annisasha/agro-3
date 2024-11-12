<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'tm_site';
    protected $primaryKey = 'site_id';

    public function areas()
    {
        return $this->hasMany(Area::class, 'site_id', 'site_id');
    }
}
