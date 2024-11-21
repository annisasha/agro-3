<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string'; 
    protected $table = 'tm_user'; 
    protected $primaryKey = 'user_id'; 
    public $timestamps = false; 
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->user_id)) {
                $model->user_id = Str::uuid()->toString(); // untuk mengenerate UUID
                $model->user_id = str_replace('-', '', $model->user_id); 
            }
        });
    }

    protected $fillable = [
        'user_name', 
        'user_email', 
        'user_phone', 
        'user_pass', 
        'role_id', 
        'user_sts',
        'user_created',
        'user_updated',
    ];

    protected $hidden = [
        'user_pass', 
    ];

    /**
     * Override metode untuk autentikasi.
     */
    public function getAuthPassword()
    {
        return $this->user_pass; 
    }
}
