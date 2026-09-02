<?php

namespace App\Http\Controllers\Api\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\StoreLocalizationLeadRequest;
use App\Models\LocalizationLead;
use App\Notifications\LocalizationLeadNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class LocalizationLeadController extends Controller
{
    public function store(StoreLocalizationLeadRequest $request): JsonResponse
    {
        $lead = LocalizationLead::create($request->validated());

        Notification::route('mail', config('leads.notify_email'))
            ->notify(new LocalizationLeadNotification($lead));

        return response()->json([
            'message' => 'Your audit request has been received!',
        ], 201);
    }
}
