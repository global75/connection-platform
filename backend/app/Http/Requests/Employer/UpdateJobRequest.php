<?php

namespace App\Http\Requests\Employer;

/**
 * Same contract as posting a job, so validation stays context-aware (required
 * city for on-site roles, required country list for country-specific hiring).
 */
class UpdateJobRequest extends StoreJobRequest
{
}
