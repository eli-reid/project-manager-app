# Plugin Dependency Model

This diagram shows:
- Core domain relationships inside `app/Domains`.
- The approved plugin integration surface through `app/Core` contracts/facades.
- Forbidden coupling patterns (direct plugin calls into domain internals).

```mermaid
---
id: af1e45be-0a0b-4647-b8e0-cd92a2cabd70
---
flowchart LR
  %% External plugin boundary
  subgraph PLUGIN[Plugin Layer]
    P1[Plugin Module]
  end

  %% Stable app-owned integration surface
  subgraph ACCESS[Core Plugin Access Surface]
    REG[Plugin Registry\napp/Core/*]
    API[Domain Contracts / Facades\napp/Core/*/Contracts]
    AUTH[AuthZ + Policy Gateway]
    EVENTS[Domain Event Bus]
  end

  %% Domain model boundary
  subgraph DOMAINS[Domain Mesh - app/Domains]
    ADDR[Addresses]
    CLIENTS[Clients]
    PROJECTS[Projects]
    TASKS[Tasks]
    DOCS[Documents]
    RFIS[RFIs]
    CHANGE[ChangeOrders]
    DAILIES[Dailies]
    INVOICES[Invoices]
    STOCK[Stock]
    EQUIP[Equipment]
    SUBMITTALS[Submittals]
    TIMECARDS[Timecards]
    PAYROLL[Payroll]
    ACCOUNTING[Accounting]
    REPORTS[Reports]
    PROVIDERS[Providers]
  end

  %% Plugin access rules
  P1 --> REG
  P1 --> API
  REG --> AUTH
  AUTH --> API
  API --> PROJECTS
  API --> TASKS
  API --> CLIENTS
  API --> DOCS
  API --> REPORTS

  %% Event-driven extension path
  TIMECARDS --> EVENTS
  PAYROLL --> EVENTS
  PROJECTS --> EVENTS
  EVENTS --> P1

  %% Domain interactions
  PROJECTS --> CLIENTS
  CLIENTS --> ADDR
  TASKS --> PROJECTS
  DOCS --> PROJECTS
  RFIS --> PROJECTS
  CHANGE --> PROJECTS
  DAILIES --> PROJECTS
  INVOICES --> PROJECTS
  STOCK --> PROJECTS
  EQUIP --> PROJECTS
  SUBMITTALS --> PROJECTS
  TIMECARDS --> PROJECTS
  TIMECARDS --> PAYROLL
  PAYROLL --> ACCOUNTING
  REPORTS --> PROJECTS
  REPORTS --> ACCOUNTING
  PROVIDERS --> STOCK

  %% Explicit anti-pattern
  P1 -. forbidden: direct domain calls .-> PROJECTS
  P1 -. forbidden: cross-domain writes .-> ACCOUNTING

  %% Edge styling (by declaration order)
  linkStyle 0,1,2,3,4,5,6,7,8,9,10,11,12 stroke:#60A5FA,stroke-width:2px,color:#DBEAFE
  linkStyle 13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29 stroke:#94A3B8,stroke-width:1.5px,color:#E2E8F0
  linkStyle 30,31 stroke:#F43F5E,stroke-width:2.5px,color:#FFE4E6

  classDef plugin fill:#3A2618,stroke:#F59E0B,stroke-width:2px,color:#FFF7ED;
  classDef access fill:#102A43,stroke:#60A5FA,stroke-width:2px,color:#E0F2FE;
  classDef domain fill:#10261A,stroke:#4ADE80,stroke-width:1.5px,color:#DCFCE7;
  classDef denied fill:#3A0F1C,stroke:#FB7185,stroke-width:2px,color:#FFE4E6;

  style PLUGIN fill:#1F1610,stroke:#F59E0B,stroke-width:2px,color:#FFF7ED
  style ACCESS fill:#0B1B2A,stroke:#60A5FA,stroke-width:2px,color:#DBEAFE
  style DOMAINS fill:#0C1C13,stroke:#4ADE80,stroke-width:2px,color:#DCFCE7

  class P1 plugin;
  class REG,API,AUTH,EVENTS access;
  class ADDR,CLIENTS,PROJECTS,TASKS,DOCS,RFIS,CHANGE,DAILIES,INVOICES,STOCK,EQUIP,SUBMITTALS,TIMECARDS,PAYROLL,ACCOUNTING,REPORTS,PROVIDERS domain;
```

## Plugin Access Contract

1. A plugin should call app-owned contracts/facades from `app/Core/*/Contracts`, not domain services or models directly.
2. Authorization should run in the app boundary before contract execution.
3. Cross-domain workflows should be orchestrated in app/core services, not from plugin code.
4. Side-effects should be consumed through domain events where possible.

## Practical Rule Of Thumb

- Allowed: `Plugin -> Core Contract -> Domain`.
- Allowed: `Domain Event -> Plugin Subscriber`.
- Avoid: `Plugin -> app/Domains/*` direct writes or direct Eloquent access across domains.

## Built-In vs Plugin Domain Split

Use this split to decide what ships in the platform by default vs what can be optional plugins.

### Built-In Domains (Project foundation)

- Projects
- Clients
- Addresses
- Tasks
- Documents
- Timecards
- Payroll
- Accounting

Why built-in:
- These form the critical execution and financial chain.
- Most other domains either reference Project context directly or depend on this data for policy/reporting.
- Breaking these out as plugins usually creates circular dependencies.

### Plugin Candidate Domains (optional capability packs)

- RFIs
- ChangeOrders
- Dailies
- Submittals
- Equipment
- Stock
- Invoices
- Reports
- Providers

Why plugin candidates:
- They are feature verticals that can be enabled/disabled per customer profile.
- They mostly consume core Project/Client/Timecard/Accounting data.
- They can expose capabilities through contracts/events without owning core identity of a project.

## Dependency Rule Set

1. Outside domains depend on Projects (for scope/context).
2. Projects may depend on core domains only (Clients and Addresses in this model).
3. Plugin domains must not be required by Projects to execute core workflows.
4. If a plugin is disabled, core Project workflows must still function.

## Quick Classification Test

A domain should be built-in if at least one is true:
- Project lifecycle cannot run without it.
- It is in the request/auth/policy critical path for all tenants.
- Multiple other domains require synchronous reads/writes from it.

A domain should be a plugin if all are true:
- It can be toggled per tenant without breaking project creation/execution.
- Its integration can be modeled via contracts/events.
- It does not introduce required dependencies back into Projects.