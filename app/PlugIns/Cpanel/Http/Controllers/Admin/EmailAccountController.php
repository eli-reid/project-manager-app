<?php

namespace App\PlugIns\Cpanel\Http\Controllers\Admin;

use App\PlugIns\Cpanel\Http\Requests\Admin\CreatesMailboxAccountRequest;
use App\PlugIns\Cpanel\Http\Requests\Admin\DeletesMailboxForwarderRequest;
use App\PlugIns\Cpanel\Http\Requests\Admin\ListsMailboxForwardersRequest;
use App\PlugIns\Cpanel\Http\Requests\Admin\ResetsMailboxPasswordRequest;
use App\PlugIns\Cpanel\Http\Requests\Admin\StoresMailboxForwarderRequest;
use App\PlugIns\Cpanel\Http\Requests\Admin\UpdatesMailboxStatusRequest;
use App\PlugIns\Cpanel\Services\CpanelService;
use Illuminate\Http\JsonResponse;

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

    public function store(CreatesMailboxAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

    public function resetPassword(ResetsMailboxPasswordRequest $request, string $email): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->cpanelService->updateEmailPassword(
            email: $email,
            password: $validated['password'],
        );

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function suspend(UpdatesMailboxStatusRequest $request, string $email): JsonResponse
    {
        $request->validated();

        $result = $this->cpanelService->suspendEmailAccount($email);
        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function unsuspend(UpdatesMailboxStatusRequest $request, string $email): JsonResponse
    {
        $request->validated();

        $result = $this->cpanelService->unsuspendEmailAccount($email);
        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function listForwarders(ListsMailboxForwardersRequest $request, string $email): JsonResponse
    {
        $request->validated();

        $result = $this->cpanelService->listForwarders($email);
        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function addForwarder(StoresMailboxForwarderRequest $request, string $email): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->cpanelService->addForwarder(
            email: $email,
            forwardTo: $validated['forward_to'],
        );

        $status = $result['success'] ? 201 : 422;

        return response()->json($result, $status);
    }

    public function deleteForwarder(DeletesMailboxForwarderRequest $request, string $email): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->cpanelService->deleteForwarder(
            email: $email,
            forwardTo: $validated['forward_to'],
        );

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }
}
