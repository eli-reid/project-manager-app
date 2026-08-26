<?php

namespace App\Domains\PaymentReceipts\Services;

use App\Core\Identity\Models\User;
use App\Domains\PaymentReceipts\Models\PaymentReceipt;
use App\Domains\Projects\Models\Project;

class PaymentReceiptLedgerService
{
    /**
     * @param  array{received_on:string,amount:string,received_from:string|null,reference:string|null,notes:string|null}  $attributes
     */
    public function record(Project $project, User $user, array $attributes): PaymentReceipt
    {
        return $project->paymentReceipts()->create([
            'received_on' => $attributes['received_on'],
            'amount' => $attributes['amount'],
            'received_from' => $attributes['received_from'] ?: null,
            'reference' => $attributes['reference'] ?: null,
            'notes' => $attributes['notes'] ?: null,
            'created_by' => $user->id,
        ]);
    }

    public function delete(Project $project, PaymentReceipt $paymentReceipt): void
    {
        if ((string) $paymentReceipt->project_id !== (string) $project->id) {
            abort(404);
        }

        $paymentReceipt->delete();
    }
}
