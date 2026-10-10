<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Verification;
use App\Services\Verification\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(private VerificationService $verifications) {}

    /**
     * The review queue — pending first, since those are what need a human.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:'.implode(',', Verification::STATUSES)],
            'type'   => ['nullable', 'in:'.implode(',', Verification::TYPES)],
        ]);

        $query = Verification::with(['verifiable', 'reviewer:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->orderByRaw("CASE WHEN status IN ('pending', 'processing') THEN 0 ELSE 1 END")
            ->latest();

        return response()->json($query->paginate(20));
    }

    /**
     * Approve or reject by hand, overriding whatever the automated check said.
     */
    public function review(Request $request, Verification $verification): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['status'] === 'rejected' && blank($validated['reason'] ?? null)) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => ['reason' => ['A reason is required when rejecting a verification.']],
            ], 422);
        }

        $verification = $this->verifications->review(
            $verification,
            $validated['status'],
            $request->user(),
            $validated['reason'] ?? null,
        );

        return response()->json(['verification' => $verification->load('verifiable', 'reviewer:id,name')]);
    }
}
