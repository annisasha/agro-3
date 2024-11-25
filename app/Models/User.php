<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'tm_user'; 
    protected $primaryKey = 'user_id'; 
    public $timestamps = false; 

    protected $fillable = [
        'user_name',
        'user_email',
        'user_pass', 
        'user_phone',
        'role_id',
        'user_sts',
        'user_created',
        'user_updated',
    ];

    protected $hidden = [
        'user_pass', 
    ];

 
    public function setPasswordAttribute($value)
    {
        $this->attributes['user_pass'] = bcrypt($value);
    }
}
