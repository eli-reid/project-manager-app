<?php

namespace App\Core\Cpanel\Http\Controllers\Admin;

use App\Core\Cpanel\Services\CpanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailAccountController
{
    public function __construct(
        protected CpanelService $cpanelService
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->cpanelService->listEmailAccounts();
        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'min:12'],
            'quota' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $this->cpanelService->createEmailAccount(
            emailUsername: $validated['username'],
            password: $validated['password'],
            quota: $validated['quota'] ?? null,
        );

        $status = $result['success'] ? 201 : 422;

        return response()->json($result, $status);
    }

    public function destroy(string $email): JsonResponse
    {
        $result = $this->cpanelService->deleteEmailAccount($email);
        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }
}
