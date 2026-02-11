<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paste extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'content',
        'encrypted',
        'password_protected',
        'init_vector',
        'salt',
        'language',
        'discord_id',
        'expires_at',
        'burn_after_read',
        'read_count',
        'content_hash',
    ];

    protected $casts = [
        'encrypted' => 'boolean',
        'password_protected' => 'boolean',
        'burn_after_read' => 'boolean',
        'read_count' => 'integer',
        'expires_at' => 'datetime',
    ];
}
