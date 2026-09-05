# Event Contract Sheet v1

## Status
- Draft baseline for event-driven domain integration.
- Scope aligned to finance expansion and domain isolation goals.
- This contract is implementation-facing and should be versioned as behavior evolves.

## Objectives
- Isolate domain responsibilities using event-driven integration.
- Trigger payroll recalculation from approved timecard data changes.
- Standardize event envelopes, delivery guarantees, and consumer idempotency.
- Keep Reports as a read-side surface, not a business-rule owner.

## Non-Goals
- Full ERP replacement in one phase.
- Global total ordering across all events.
- Synchronous cross-domain orchestration in request paths.

## Canonical Decisions
- Delivery mode: queued asynchronous consumers.
- Reliability model: outbox pattern with replay support.
- Payroll recalculation strategy: recompute affected pay period (full recompute, not delta patching).
- Ordering guarantee: per aggregate stream only.
- Consumer safety: idempotent handlers are required for all cross-domain consumers.

## Event Envelope Contract (Required)
All published domain events must conform to the following envelope.

| Field | Type | Required | Notes |
|---|---|---|---|
| event_id | string (ULID/UUID) | Yes | Globally unique immutable id for dedupe and traceability. |
| event_name | string | Yes | Canonical name from this sheet. |
| version | integer | Yes | Starts at 1 for all events in this document. |
| occurred_at | datetime (ISO-8601 UTC) | Yes | Time event was produced. |
| effective_at | datetime (ISO-8601 UTC) | Yes | Business-effective time for accounting period correctness. |
| producer_domain | string | Yes | Domain owning event emission. |
| aggregate_type | string | Yes | Aggregate class/category name. |
| aggregate_id | string | Yes | Aggregate identifier. |
| aggregate_version | integer | Yes | Monotonic version for stream ordering and idempotency. |
| correlation_id | string | Yes | Correlates a business transaction across events. |
| causation_id | string | Yes | The prior event/command that caused this event. |
| actor_user_id | string or null | Yes | Null for system-generated events. |
| company_scope | string | Recommended | Required if multi-tenant/company scoping exists. |
| payload | object | Yes | Domain-specific immutable facts. |

## Outbox Contract
- Producers write domain state and outbox record in one DB transaction.
- Outbox records are published by background dispatcher.
- Dispatcher retries with exponential backoff.
- Failed records remain queryable and replayable.
- Replays must preserve original event_id and envelope metadata.

## Consumer Contract
- Idempotency key: event_id + aggregate_id + aggregate_version.
- Duplicate deliveries must be acknowledged without duplicate side effects.
- Consumers must not mutate producer domain state directly.
- Consumers must emit failure telemetry with correlation_id.

## Domain Event Catalog

### 1) Timecards Events

#### TimecardSubmitted
- Producer: Timecards
- Trigger: timecard status transitions to submitted
- Payload fields:
  - timecard_id
  - user_id
  - period_start
  - period_end
  - submitted_at
  - project_ids[]
  - total_hours
- Consumers:
  - Payroll: mark period ready for preview candidates
  - Reports: update operational submission metrics
- Acceptance criteria:
  - Payroll preview queue entry exists for referenced period within SLA.
  - Reports submission count reflects event exactly once.

#### TimecardApproved
- Producer: Timecards
- Trigger: timecard status transitions to approved
- Payload fields:
  - timecard_id
  - approved_by
  - approved_at
  - period_start
  - period_end
  - project_hours[]: {project_id, hours}
  - cost_code_hours[]: {cost_code_id, hours}
- Consumers:
  - Payroll: recompute affected pay period
  - Accounting: optional labor accrual candidate processing
  - Reports: refresh labor-related read models
- Acceptance criteria:
  - One payroll recompute job enqueued for affected period.
  - Reprocessing same event does not create additional payroll side effects.

#### TimecardEntryChangedAfterApproval
- Producer: Timecards
- Trigger: approved entry create/update/delete or approved-entry project/cost/date/hours changes
- Payload fields:
  - timecard_id
  - entry_id
  - change_type (created|updated|deleted)
  - before (nullable)
  - after (nullable)
  - period_start
  - period_end
- Consumers:
  - Payroll: recompute affected pay period
  - Accounting: reverse/repost labor allocation candidates
  - Reports: refresh deltas
- Acceptance criteria:
  - Payroll recompute is scheduled for period once per aggregate_version.
  - Accounting candidate postings are net-correct after replay.

#### TimecardRejected
- Producer: Timecards
- Trigger: timecard status transitions to rejected
- Payload fields:
  - timecard_id
  - rejected_by
  - rejected_at
  - reason
  - period_start
  - period_end
- Consumers:
  - Payroll: remove from payable candidate pool
  - Reports: update rejection metrics
- Acceptance criteria:
  - Payroll preview excludes rejected timecard data.
  - Reports rejection count increments once per event_id.

### 2) Payroll Events

#### PayrollRunDrafted
- Producer: Payroll
- Trigger: payroll run draft created
- Payload fields:
  - payroll_run_id
  - period_start
  - period_end
  - employee_count
  - gross_total
- Consumers:
  - Reports
- Acceptance criteria:
  - Payroll draft appears in operational payroll reporting projection.

#### PayrollRunFinalized
- Producer: Payroll
- Trigger: payroll run finalized and locked
- Payload fields:
  - payroll_run_id
  - period_start
  - period_end
  - gross_total
  - tax_total
  - deduction_total
  - net_total
  - allocation_lines[]: {project_id, cost_code_id, amount}
- Consumers:
  - Accounting: post wage/tax/liability entries
  - Reports: publish finalized payroll analytics
- Acceptance criteria:
  - Accounting posting batch references payroll_run_id and balances debits/credits.
  - Replay does not duplicate ledger entries.

#### PayrollRunVoided
- Producer: Payroll
- Trigger: finalized payroll run voided
- Payload fields:
  - payroll_run_id
  - voided_at
  - reason
  - reversed_posting_batch_id (nullable)
- Consumers:
  - Accounting: post reversal entries
  - Reports: mark run voided
- Acceptance criteria:
  - Accounting reversal links to original posting batch.
  - Reports show run status voided.

### 3) Stock Events

#### StockOrderApproved
- Producer: Stock
- Trigger: stock order status approved
- Payload fields:
  - stock_order_id
  - approved_at
  - project_id
  - accounting_code_id
  - estimated_total
- Consumers:
  - Reports
- Acceptance criteria:
  - Approved order appears in procurement projection once.

#### StockOrderReceived
- Producer: Stock
- Trigger: stock order status received
- Payload fields:
  - stock_order_id
  - received_at
  - project_id
  - accounting_code_id
  - received_total
  - item_lines[]: {item_id, quantity, unit_cost, total}
- Consumers:
  - Payables: bill matching candidate creation
  - Accounting: inventory/expense posting candidate
  - Reports
- Acceptance criteria:
  - Payables can resolve received order by stock_order_id for match workflow.
  - Accounting candidate amount equals received_total.

#### StockOrderCancelled
- Producer: Stock
- Trigger: stock order cancelled
- Payload fields:
  - stock_order_id
  - cancelled_at
  - reason
- Consumers:
  - Accounting: release commitments where applicable
  - Reports
- Acceptance criteria:
  - Commitment projection no longer includes cancelled order.

### 4) Payables Events (Vendor Billing)

#### VendorBillCreated
- Producer: Payables
- Trigger: vendor bill created
- Payload fields:
  - vendor_bill_id
  - vendor_id_or_name
  - bill_number
  - bill_date
  - due_date
  - project_id (nullable)
  - accounting_code_id
  - subtotal
  - tax_amount
  - total_amount
  - stock_order_id (nullable)
- Consumers:
  - Accounting: AP recognition posting
  - Reports
- Acceptance criteria:
  - AP recognition posting candidate exists once per bill version.

#### VendorBillApproved
- Producer: Payables
- Trigger: bill approved for payment
- Payload fields:
  - vendor_bill_id
  - approved_by
  - approved_at
  - payment_terms_snapshot
- Consumers:
  - Accounting (optional reclassification)
  - Reports
- Acceptance criteria:
  - Approval state reflected in payable aging projection.

#### VendorBillPaid
- Producer: Payables
- Trigger: payment applied to vendor bill
- Payload fields:
  - vendor_bill_id
  - payment_id
  - paid_at
  - paid_amount
  - payment_method
- Consumers:
  - Accounting: cash/AP settlement posting
  - Reports
- Acceptance criteria:
  - Outstanding balance reduced by paid_amount exactly once.

#### VendorBillVoided
- Producer: Payables
- Trigger: bill voided
- Payload fields:
  - vendor_bill_id
  - voided_at
  - reason
- Consumers:
  - Accounting: reversal posting
  - Reports
- Acceptance criteria:
  - Bill status and accounting projection both reflect voided state.

### 5) Receivables Events

#### CustomerInvoiceIssued
- Producer: Receivables
- Trigger: customer invoice issued
- Payload fields:
  - customer_invoice_id
  - customer_id
  - invoice_number
  - invoice_date
  - due_date
  - project_id (nullable)
  - revenue_lines[]: {accounting_code_id, amount}
  - tax_amount
  - total_amount
- Consumers:
  - Accounting: AR/revenue recognition posting
  - Reports
- Acceptance criteria:
  - AR and revenue candidate postings balance against total_amount.

#### CustomerPaymentApplied
- Producer: Receivables
- Trigger: payment applied to one or more invoices
- Payload fields:
  - payment_id
  - applied_at
  - customer_id
  - allocations[]: {customer_invoice_id, amount}
  - total_applied
- Consumers:
  - Accounting: cash/AR settlement posting
  - Reports
- Acceptance criteria:
  - Sum(allocations.amount) equals total_applied.
  - Replay does not duplicate settlement effects.

#### CustomerCreditIssued
- Producer: Receivables
- Trigger: credit memo issued
- Payload fields:
  - credit_memo_id
  - customer_id
  - issued_at
  - amount
  - reason
  - linked_invoice_id (nullable)
- Consumers:
  - Accounting: AR/revenue adjustment posting
  - Reports
- Acceptance criteria:
  - Outstanding AR reduces by credit amount in projections.

#### CustomerInvoiceWrittenOff
- Producer: Receivables
- Trigger: invoice or balance written off
- Payload fields:
  - customer_invoice_id
  - written_off_at
  - amount
  - reason
- Consumers:
  - Accounting: bad debt posting
  - Reports
- Acceptance criteria:
  - Written-off amount removed from collectible aging buckets.

### 6) Accounting Events

#### LedgerEntriesPosted
- Producer: Accounting
- Trigger: posting batch committed successfully
- Payload fields:
  - posting_batch_id
  - source_event_id
  - ledger_date
  - debit_total
  - credit_total
  - line_count
- Consumers:
  - Reports
  - Reconciliation monitors
- Acceptance criteria:
  - debit_total equals credit_total.
  - Projection links posting_batch_id to source_event_id.

#### LedgerEntriesReversed
- Producer: Accounting
- Trigger: reversal batch committed
- Payload fields:
  - reversal_batch_id
  - original_posting_batch_id
  - reversed_at
  - debit_total
  - credit_total
- Consumers:
  - Reports
  - Reconciliation monitors
- Acceptance criteria:
  - Reversal references valid original_posting_batch_id.

#### PostingFailed
- Producer: Accounting
- Trigger: posting fails after retry threshold
- Payload fields:
  - source_event_id
  - failure_code
  - failure_reason
  - failed_at
  - retry_count
- Consumers:
  - Operations alerts
  - Reconciliation dashboard
- Acceptance criteria:
  - Alert includes source_event_id and correlation_id.

## Payroll Recompute Contract (Locked)
- Trigger sources:
  - TimecardApproved
  - TimecardEntryChangedAfterApproval
  - TimecardRejected
- Scope:
  - Recompute affected pay period only.
- Processing rules:
  - Finalized payroll runs are immutable unless explicitly voided.
  - Recompute failures must not mutate finalized state.
  - Recompute job must be idempotent by period + aggregate version.

## SLA and Operational Expectations
- Event publish lag (outbox record to broker/queue): target <= 30s.
- Consumer processing lag (queued): target <= 2m p95.
- Dead-letter handling: required for non-recoverable failures.
- Replay tooling: required per domain with audit logging.

## Contract Test Matrix
Each producer/consumer pair must implement contract tests for:
- Envelope schema validation.
- Idempotent duplicate delivery.
- Out-of-order guard by aggregate_version.
- Replay safety.
- Failure telemetry propagation (correlation_id, causation_id).

## Initial Rollout Sequence
1. Build shared envelope library + outbox table + dispatcher + idempotent consumer middleware.
2. Ship Timecards -> Payroll contracts (TimecardApproved, TimecardEntryChangedAfterApproval).
3. Ship Payroll -> Accounting (PayrollRunFinalized, PayrollRunVoided).
4. Ship Stock/Payables -> Accounting integration events.
5. Introduce Receivables contracts.
6. Migrate Reports to projection-only consumption.

## Change Control
- Backward-compatible changes:
  - Additive payload fields.
  - New optional events.
- Breaking changes:
  - Field removal/rename.
  - Semantic trigger changes.
  - Envelope contract changes.
- Breaking changes require version bump and dual-read support window.

## Open Questions
- Confirm broker/transport abstraction strategy for queue backend portability.
- Confirm tenant/company scoping field final name if multi-tenant support expands.
- Confirm retention policy for outbox and replay logs.
