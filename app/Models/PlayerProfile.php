<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerProfile extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $guarded = ['user_id'];
    protected $hidden = ['avatar_path', 'avatar_disk'];
}
