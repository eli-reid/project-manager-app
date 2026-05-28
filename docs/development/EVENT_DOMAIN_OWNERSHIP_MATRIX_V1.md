# Event Domain Ownership Matrix v1

## Status
- Companion artifact to [docs/development/EVENT_CONTRACT_SHEET_V1.md](docs/development/EVENT_CONTRACT_SHEET_V1.md).
- Purpose: map each event to exact producer/consumer ownership classes.
- Scope: class contracts for implementation planning (existing + to-add).

## Ownership Rules
- Producer domain owns event emission and payload truth.
- Consumer domain owns side effects and retries.
- No consumer may mutate producer domain state directly.
- Reports consumes projections only and does not own business logic.

## Class Legend
- Existing class: already present in repository.
- To add class: naming contract for this initiative.

## Producer Ownership Matrix

| Event | Producer Domain | Producer Owner Class | Status | Notes |
|---|---|---|---|---|
| TimecardSubmitted | Timecards | App\Domains\Timecards\Services\TimecardLifecycleService | Existing | Emit when status becomes submitted. |
| TimecardApproved | Timecards | App\Domains\Timecards\Services\TimecardLifecycleService | Existing | Primary payroll trigger. |
| TimecardEntryChangedAfterApproval | Timecards | App\Domains\Timecards\Services\TimecardEntrySyncService | Existing | Emit for create/update/delete on approved entries. |
| TimecardRejected | Timecards | App\Domains\Timecards\Services\TimecardLifecycleService | Existing | Remove from payroll candidate pool. |
| PayrollRunDrafted | Payroll | App\Domains\Payroll\Services\PayRunService | Existing | Draft run projection event. |
| PayrollRunFinalized | Payroll | App\Domains\Payroll\Services\PayRunService | Existing | Triggers accounting posting. |
| PayrollRunVoided | Payroll | App\Domains\Payroll\Services\PayRunService | Existing | Triggers accounting reversal. |
| StockOrderApproved | Stock | App\Domains\Stock\Services\StockOrderLifecycleService | To add | Domain lifecycle emitter for stock order transitions. |
| StockOrderReceived | Stock | App\Domains\Stock\Services\StockOrderLifecycleService | To add | Drives payables match and accounting candidate posting. |
| StockOrderCancelled | Stock | App\Domains\Stock\Services\StockOrderLifecycleService | To add | Releases commitments in consumers. |
| VendorBillCreated | Payables (rehome from Invoices) | App\Domains\Payables\Services\VendorBillLifecycleService | To add | Replace invoice-as-vendor-bill semantics. |
| VendorBillApproved | Payables | App\Domains\Payables\Services\VendorBillLifecycleService | To add | Approval transition event. |
| VendorBillPaid | Payables | App\Domains\Payables\Services\VendorBillPaymentService | To add | Payment application event. |
| VendorBillVoided | Payables | App\Domains\Payables\Services\VendorBillLifecycleService | To add | Void transition event. |
| CustomerInvoiceIssued | Receivables | App\Domains\Receivables\Services\CustomerInvoiceLifecycleService | To add | New AR lifecycle domain. |
| CustomerPaymentApplied | Receivables | App\Domains\Receivables\Services\CustomerPaymentApplicationService | To add | Payment allocation event. |
| CustomerCreditIssued | Receivables | App\Domains\Receivables\Services\CustomerCreditMemoService | To add | Credit memo lifecycle event. |
| CustomerInvoiceWrittenOff | Receivables | App\Domains\Receivables\Services\CustomerWriteOffService | To add | Bad debt lifecycle event. |
| LedgerEntriesPosted | Accounting | App\Domains\Accounting\Services\LedgerPostingService | To add | Emitted after committed posting batch. |
| LedgerEntriesReversed | Accounting | App\Domains\Accounting\Services\LedgerReversalService | To add | Emitted after committed reversal batch. |
| PostingFailed | Accounting | App\Domains\Accounting\Services\LedgerPostingService | To add | Emitted after retry threshold failure. |

## Consumer Ownership Matrix

| Event | Consumer Domain | Consumer Owner Class | Status | Responsibility |
|---|---|---|---|---|
| TimecardSubmitted | Payroll | App\Domains\Payroll\Services\PayrollEventConsumerService | To add | Mark period preview candidate readiness. |
| TimecardApproved | Payroll | App\Domains\Payroll\Services\PayrollRecomputeService | To add | Recompute affected pay period. |
| TimecardEntryChangedAfterApproval | Payroll | App\Domains\Payroll\Services\PayrollRecomputeService | To add | Recompute affected pay period. |
| TimecardRejected | Payroll | App\Domains\Payroll\Services\PayrollRecomputeService | To add | Remove/recompute affected period. |
| TimecardApproved | Accounting | App\Domains\Accounting\Services\LaborAccrualConsumerService | To add | Optional labor accrual candidate handling. |
| TimecardEntryChangedAfterApproval | Accounting | App\Domains\Accounting\Services\LaborAccrualConsumerService | To add | Reverse/repost labor allocation candidates. |
| PayrollRunFinalized | Accounting | App\Domains\Accounting\Services\PayrollPostingConsumerService | To add | Post wages/taxes/liabilities. |
| PayrollRunVoided | Accounting | App\Domains\Accounting\Services\PayrollPostingConsumerService | To add | Reverse payroll postings. |
| StockOrderReceived | Payables | App\Domains\Payables\Services\StockReceiptMatchConsumerService | To add | Create bill matching candidates. |
| StockOrderReceived | Accounting | App\Domains\Accounting\Services\InventoryCostConsumerService | To add | Inventory/expense candidate posting. |
| VendorBillCreated | Accounting | App\Domains\Accounting\Services\PayablesPostingConsumerService | To add | AP recognition posting. |
| VendorBillPaid | Accounting | App\Domains\Accounting\Services\PayablesPostingConsumerService | To add | Cash/AP settlement posting. |
| VendorBillVoided | Accounting | App\Domains\Accounting\Services\PayablesPostingConsumerService | To add | Reversal posting. |
| CustomerInvoiceIssued | Accounting | App\Domains\Accounting\Services\ReceivablesPostingConsumerService | To add | AR/revenue posting. |
| CustomerPaymentApplied | Accounting | App\Domains\Accounting\Services\ReceivablesPostingConsumerService | To add | Cash/AR settlement posting. |
| CustomerCreditIssued | Accounting | App\Domains\Accounting\Services\ReceivablesPostingConsumerService | To add | AR/revenue adjustment posting. |
| CustomerInvoiceWrittenOff | Accounting | App\Domains\Accounting\Services\ReceivablesPostingConsumerService | To add | Bad debt posting. |
| LedgerEntriesPosted | Reports | App\Domains\Reports\Services\ReportingProjectionConsumerService | To add | Update reporting projections only. |
| LedgerEntriesReversed | Reports | App\Domains\Reports\Services\ReportingProjectionConsumerService | To add | Update reversal projections only. |
| PostingFailed | Reports/Ops | App\Domains\Reports\Services\ReportingProjectionConsumerService | To add | Expose operational health indicators. |

## Cross-Cutting Infrastructure Ownership

| Concern | Owner Class | Status | Notes |
|---|---|---|---|
| Event envelope creation | App\Core\Events\EventEnvelopeFactory | To add | Centralized envelope contract enforcement. |
| Outbox persistence | App\Core\Events\Outbox\OutboxWriter | To add | Written in same transaction as state changes. |
| Outbox dispatch | App\Core\Events\Outbox\OutboxDispatcher | To add | Queued publisher with retry/backoff. |
| Consumer idempotency | App\Core\Events\Consumers\IdempotentConsumerMiddleware | To add | Dedupe by event_id + aggregate_id + aggregate_version. |
| Replay tooling | App\Core\Events\Outbox\OutboxReplayService | To add | Controlled replay with audit trail. |

## Domain Anchors (Current Code)
- Timecards provider: [app/Domains/Timecards/Providers/TimecardsServiceProvider.php](app/Domains/Timecards/Providers/TimecardsServiceProvider.php)
- Payroll provider: [app/Domains/Payroll/Providers/PayrollServiceProvider.php](app/Domains/Payroll/Providers/PayrollServiceProvider.php)
- Stock provider: [app/Domains/Stock/Providers/StockServiceProvider.php](app/Domains/Stock/Providers/StockServiceProvider.php)
- Invoices provider (current vendor billing location): [app/Domains/Invoices/Providers/InvoicesServiceProvider.php](app/Domains/Invoices/Providers/InvoicesServiceProvider.php)
- Accounting provider: [app/Domains/Accounting/Providers/AccountingServiceProvider.php](app/Domains/Accounting/Providers/AccountingServiceProvider.php)
- Reports registry: [app/Domains/Reports/Services/ReportRegistry.php](app/Domains/Reports/Services/ReportRegistry.php)

## Implementation Order (Class-Level)
1. Add core event infrastructure classes under App\Core\Events.
2. Add PayrollRecomputeService and wire Timecards producer events first.
3. Add Accounting posting consumers for payroll finalized/voided.
4. Introduce StockOrderLifecycleService and emit stock order events.
5. Rehome vendor billing to Payables and add VendorBill lifecycle + payment services.
6. Add Receivables lifecycle services and accounting consumers.
7. Add ReportingProjectionConsumerService and move reports to projection consumption.

## Acceptance Criteria
- Every event in v1 has one producer owner class.
- Every cross-domain side effect maps to one consumer owner class.
- No event has ambiguous ownership between domains.
- Existing workflows remain non-regressed while new classes are introduced.
