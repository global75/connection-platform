<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'category'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($s) => $s->slug = $s->slug ?? Str::slug($s->name));
    }

    public function jobSeekers()
    {
        return $this->belongsToMany(JobSeekerProfile::class, 'job_seeker_skills')
                    ->withPivot('proficiency');
    }

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'job_skills')
                    ->withPivot('is_required');
    }
}
