<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An inbound audit request from the SaaS localization service page.
 */
class LocalizationLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'app_url', 'target_languages', 'message', 'status',
    ];

    protected $casts = [
        'target_languages' => 'array',
    ];

    public const STATUSES = ['new', 'contacted', 'completed'];
}
