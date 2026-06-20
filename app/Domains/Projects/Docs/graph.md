graph LR

  subgraph Entry["Entry / Composition"]
    PSP[ProjectsServiceProvider]
    ROUTES[Routes admin web mobile api]
  end

  subgraph UI["Route-Facing UI"]
    IDX[Admin Projects Index]
    FORM[Admin Projects Form]
    SHOW[Admin Projects Show]
    U_IDX[User Projects Index]
    U_SHOW[User Projects Show]
    M_IDX[Mobile Projects Index]
    WIDGET[Dashboard Widget]
  end

  subgraph Policy["Authorization"]
    POLICY[ProjectPolicy]
  end

  subgraph Services["Projects Services"]
    REG[ProjectTabRegistry]
    CAT[ProjectTabCatalog]
    PREFSTORE[ProjectTabPreferenceStore]
    LINK[ProjectTabLinkBuilder]
    ACCESS[ProjectAccessService]
    FIN[ProjectFinancialsService]
    REPORT[ProjectReportingService]
  end

  subgraph TabFramework["Tab Framework"]
    TAB_IF[ProjectTabInterface]
    TAB[ProjectTab abstract]
    PANEL_IF[ProjectTabPanel]
    PANEL[LivewireComponentTabPanel multi-view]
    RESOLVED[ResolvedProjectTab]
    VIEWITEM[ProjectTabViewItem]
  end

  subgraph ProjectsTabs["Projects-Owned Tabs"]
    T_OV[OverviewProjectTab]
    T_AC[AccessProjectTab]
    T_FN[FinancialsProjectTab]
  end

  subgraph ExternalDomainTabs["External Domain Tabs"]
    T_D[ DailiesProjectTab ]
    T_T[ TasksProjectTab ]
    T_I[ InvoicesProjectTab ]
    T_S[ StockProjectTab ]
    T_SM[ SubmittalsProjectTab ]
    T_CO[ ChangeOrderTab ]
    T_R[ RFIsProjectTab ]
    T_DOC[ DocumentsProjectTab ]
    T_TC[ TimecardsProjectTab ]
  end

  subgraph Models["Projects Models"]
    P[Project]
    PUA[ProjectUserAccess]
    PRA[ProjectRoleAccess]
    PTD[ProjectTabDefinition]
    PTUP[ProjectTabUserPreference]
    CC[CostCode]
  end

  subgraph CoreAndExternal["Core + External Domains"]
    USER[User]
    ROLE[Role]
    AUDIT[AuditLoggerContract]
    ACCT[AccountingCode]
    ADDR[Address]
    CLIENT[Client]
    PAYRATE[PayRateType]
    DAILY[DailyReport]
    CHG[ChangeOrder]
    INV[Invoice]
  end

  PSP --> ROUTES
  PSP --> POLICY
  PSP --> REG
  PSP --> T_OV
  PSP --> T_AC
  PSP --> T_FN

  ROUTES --> IDX
  ROUTES --> FORM
  ROUTES --> SHOW
  ROUTES --> U_IDX
  ROUTES --> U_SHOW
  ROUTES --> M_IDX

  IDX --> P
  FORM --> P
  FORM --> ACCT
  FORM --> CLIENT
  FORM --> ADDR
  FORM --> PAYRATE
  SHOW --> REG
  SHOW --> LINK
  WIDGET --> REPORT

  POLICY --> ACCESS
  POLICY --> USER
  POLICY --> P

  ACCESS --> PUA
  ACCESS --> PRA
  ACCESS --> USER
  ACCESS --> ROLE
  ACCESS --> AUDIT

  FIN --> INV
  REPORT --> P

  REG --> CAT
  REG --> PREFSTORE
  REG --> PTD
  REG --> PTUP
  REG --> RESOLVED
  REG --> VIEWITEM
  REG --> PANEL_IF

  LINK --> REG

  TAB --> TAB_IF
  TAB --> PANEL_IF
  PANEL --> PANEL_IF
  RESOLVED --> TAB_IF
  VIEWITEM --> RESOLVED

  T_OV --> TAB
  T_AC --> TAB
  T_FN --> TAB
  T_AC --> PANEL
  T_FN --> PANEL

  T_D --> TAB
  T_T --> TAB
  T_I --> TAB
  T_S --> TAB
  T_SM --> TAB
  T_CO --> TAB
  T_R --> TAB
  T_DOC --> TAB
  T_TC --> TAB

  T_D --> PANEL
  T_T --> PANEL
  T_I --> PANEL
  T_S --> PANEL
  T_SM --> PANEL
  T_CO --> PANEL
  T_R --> PANEL
  T_DOC --> PANEL
  T_TC --> PANEL

  P --> USER
  P --> ACCT
  P --> ADDR
  P --> CLIENT
  P --> DAILY
  P --> CHG
  P --> PAYRATE
  P --> PUA
  P --> PRA
  P --> CC