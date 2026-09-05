# Domain Connections

Below is a high-level UML-style domain connection diagram (Mermaid) showing which domains use or depend on others.

```mermaid
graph LR
  Projects[Projects]
  Documents[Documents]
  RFIs[RFIs]
  ChangeOrders[ChangeOrders]
  Dailies[Dailies]
  Invoices[Invoices]
  Stock[Stock]
  Submittals[Submittals]
  Timecards[Timecards]
  Tasks[Tasks]
  Payroll[Payroll]
  Accounting[Accounting]
  Reports[Reports]

  Projects --> Documents
  Projects --> RFIs
  Projects --> ChangeOrders
  Projects --> Dailies
  Projects --> Invoices
  Projects --> Stock
  Projects --> Submittals
  Projects --> Timecards
  Projects --> Tasks

  Timecards --> Payroll
  Payroll --> Accounting
  Reports --> Accounting
  Reports --> Projects

  Documents --> Projects
  Tasks --> Projects
  Stock --> Projects

  %% bidirectional / close relationships
  Timecards -- uses --> Projects
  Payroll -- consumes events from --> Timecards
  Accounting -- consumes events from --> Payroll

  classDef domain fill:#f8f9fb,stroke:#333,stroke-width:1px;
  class Projects,Documents,RFIs,ChangeOrders,Dailies,Invoices,Stock,Submittals,Timecards,Tasks,Payroll,Accounting,Reports domain;
```

Summary:
- Projects is a central domain used by Documents, RFIs, ChangeOrders, Dailies, Invoices, Stock, Submittals, Timecards and Tasks.
- Timecards ↔ Payroll ↔ Accounting form the payroll/accounting chain.
- Reports consumes both project and accounting data.

Notes:
- This diagram is inferred from `app/Domains/*` usages (models, services, providers). It shows "uses/depends on" relationships, not every class-level coupling.
- If you want a more detailed class-level or event-flow UML, tell me which domains to expand.
