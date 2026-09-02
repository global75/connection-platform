<?php

namespace App\Services\LeadQualification\Contracts;

use App\Services\LeadQualification\LeadProfile;
use App\Services\LeadQualification\QualificationResult;

interface LeadQualifier
{
    /**
     * Score a lead against the job it targets.
     *
     * @throws \RuntimeException when the verdict could not be produced.
     */
    public function qualify(LeadProfile $lead): QualificationResult;

    /**
     * Short identifier persisted alongside the verdict (e.g. "claude").
     */
    public function name(): string;
}
