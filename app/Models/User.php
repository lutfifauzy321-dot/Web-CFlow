<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'avatar', 'savings_goal', 'daily_limit', 'password_hash'];

    protected $hidden = ['password_hash', 'remember_token'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'savings_goal' => 'integer',
            'daily_limit' => 'integer',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }
}
