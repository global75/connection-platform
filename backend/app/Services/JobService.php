<?php

namespace App\Services;

use App\Models\EmployerProfile;
use App\Models\Job;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobService
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Job::active()
            ->with(['employer:id,company_name,company_slug,logo,headquarters_city,headquarters_state,is_verified', 'skills:id,name,slug'])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at');

        if (!empty($filters['q'])) {
            $query->search($filters['q']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (!empty($filters['location_type'])) {
            $query->where('location_type', $filters['location_type']);
        }
        if (!empty($filters['experience_level'])) {
            $query->where('experience_level', $filters['experience_level']);
        }
        if (!empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }
        if (isset($filters['salary_min'])) {
            $query->where('salary_max', '>=', $filters['salary_min']);
        }
        if (isset($filters['salary_max'])) {
            $query->where('salary_min', '<=', $filters['salary_max']);
        }
        if (isset($filters['visa_sponsorship'])) {
            $query->where('visa_sponsorship', (bool) $filters['visa_sponsorship']);
        }
        if (!empty($filters['skills'])) {
            $skillIds = is_array($filters['skills']) ? $filters['skills'] : explode(',', $filters['skills']);
            $query->whereHas('skills', fn ($q) => $q->whereIn('skills.id', $skillIds));
        }

        return $query->paginate($perPage);
    }

    public function createJob(EmployerProfile $employer, array $data): Job
    {
        return DB::transaction(function () use ($employer, $data) {
            $job = $employer->jobs()->create($data);

            if (!empty($data['skills'])) {
                $skillData = collect($data['skills'])->mapWithKeys(fn ($s) => [
                    $s['id'] => ['is_required' => $s['is_required'] ?? true],
                ]);
                $job->skills()->sync($skillData);
            }

            $employer->decrementCredits();

            return $job->load('skills');
        });
    }

    public function updateJob(Job $job, array $data): Job
    {
        return DB::transaction(function () use ($job, $data) {
            $job->update($data);

            if (array_key_exists('skills', $data)) {
                $skillData = collect($data['skills'])->mapWithKeys(fn ($s) => [
                    $s['id'] => ['is_required' => $s['is_required'] ?? true],
                ]);
                $job->skills()->sync($skillData);
            }

            return $job->fresh('skills');
        });
    }

    public function getJobWithDetails(Job $job): Job
    {
        $job->incrementViews();

        return $job->load([
            'employer:id,company_name,company_slug,logo,description,website,headquarters_city,headquarters_state,headquarters_country,founded_year,company_size,is_verified,verified_at',
            'skills:id,name,slug,category',
        ]);
    }
}
