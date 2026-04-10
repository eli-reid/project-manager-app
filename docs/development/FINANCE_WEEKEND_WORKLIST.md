# Finance Weekend Worklist

Date: 2026-04-10
Context: Sprint 2 Financial Backbone execution plan

## Current Status Snapshot

- Fully completed finance items: 6 of 18
- In progress / partial: 5 of 18
- Not started: 7 of 18
- Payroll scaffold test status: 28 passing tests

## Priority Worklist (Do Next)

1. Implement payroll-to-project financial sync
2. Implement monthly financial performance report
3. Implement labor cost analysis report
4. Implement material cost analysis report
5. Add drill-downs (project, month/week, cost type, vendor/supplier)
6. Add PDF export support
7. Complete on-screen payroll output + full export workflow
8. Add reconciliation and snapshot stability gate tests

## Suggested Weekend Execution Order

### Block 1: Core Data Flow

- [ ] Build payroll-to-project sync service
- [ ] Add tests proving payroll updates project-level financial summaries
- [ ] Verify reconciliation totals remain stable after sync

### Block 2: Reporting Coverage

- [ ] Build monthly financial performance report
- [ ] Build labor cost analysis report
- [ ] Build material cost analysis report
- [ ] Add shared report filters and drill-down dimensions

### Block 3: Export Parity

- [ ] Ensure on-screen and CSV outputs match for each report
- [ ] Implement PDF export for financial reports
- [ ] Add parity tests for on-screen vs CSV vs PDF totals

### Block 4: Final Gate

- [ ] Add reconciliation test suite for payroll periods and project totals
- [ ] Add snapshot stability tests for financial reports
- [ ] Mark Sprint 2 finance checklist items complete in the sprint checklist

## Definition of Done for Weekend

- Payroll totals flow into project financial summaries correctly
- Monthly/labor/material reports are available and permission-protected
- Drill-down filtering works across required dimensions
- CSV and PDF exports match on-screen totals
- Reconciliation and snapshot stability tests pass

## Notes

- Keep all finance artifacts inside their domain structure.
- Register domain resources via domain service providers.
- Update the sprint checklist after each completed item.
