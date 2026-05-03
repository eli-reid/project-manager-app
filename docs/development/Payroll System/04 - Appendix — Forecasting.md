# Appendix — Forecasting

Parent Document: 01 — Payroll System Spec  
Version: 1.0  
Date: 2026-04-11

---

## 1  Overview

The forecasting module projects future labor costs using historical timecard data, current headcount, active project schedules, and rate tables. Forecasts enable project managers and controllers to anticipate cash flow needs, identify budget variances early, and make staffing decisions based on data rather than gut feel.

## 2  Forecasting Models

### 2.1  Trailing Average Model

**Formula:**

> Forecast Weekly Cost = (SUM of actual_labor_cost for trailing N weeks) / N

**Parameters:**

| Parameter | Default | Configurable |
|---|---:|---|
| Trailing window (N) | 4 weeks | Yes (2–12) |
| Include OT | Yes | Yes |
| Exclude terminated employees | Yes | No |

**Use Case:** General cash-flow planning when project mix is stable.

### 2.2  Project-Based Model

**Formulas:**

> Remaining Hours = budget_labor_hours - SUM(actual_hours_to_date)  
> Weeks Remaining = remaining_hours / avg_weekly_hours_per_project  
> Weekly Cost     = avg_weekly_hours × blended_rate  
> Total Remaining = remaining_hours × blended_rate

**Parameter Sources:**

| Parameter | Source |
|---|---|
| budget_labor_hours | Project master |
| actual_hours_to_date | Processed timecards |
| avg_weekly_hours | Trailing 4-week average |
| blended_rate | Weighted average of all employee rates on project |

**Use Case:** Project-level budget tracking and completion forecasting.

### 2.3  Headcount-Based Model

**Formulas:**

> Weekly Forecast  = SUM(employee_rate × expected_weekly_hours) for all active employees  
> Monthly Forecast = Weekly × (52 / 12)

**Parameters:**

| Parameter | Source / Default |
|---|---|
| employee_rate | Current active PayRate |
| expected_weekly_hours | 40 (default) or project-specific |
| Headcount adjustments | Manual input |

**Use Case:** Budget planning, bid preparation, overhead allocation.

### 2.4  Seasonal Adjustment Model

**Formulas:**

> Adjusted Forecast = Base Forecast × Seasonal Factor  
> Seasonal Factor   = (avg hours for target month across prior years) / (avg hours across all months)

**Note:** Requires minimum 2 years of historical data.

**Use Case:** Companies with seasonal workload patterns.

## 3  Forecast Outputs

### 3.1  Dashboard Widgets

| Widget | Refresh Frequency |
|---|---|
| Weekly Burn Rate | Daily |
| Project Budget Gauge | Daily |
| Cash Flow Projection | Weekly |
| Headcount Trend | Weekly |
| Variance Alert | Daily |

### 3.2  Reports

| Report | Frequency | Audience |
|---|---|---|
| Weekly Labor Forecast | Monday AM | Controller / PM |
| Monthly Budget vs Actual | 1st business day | Controller / Executive |
| Project Completion Forecast | On demand | PM |
| Annual Labor Budget | Annually | Executive / Controller |
| Seasonal Trend Analysis | Quarterly | Controller |

### 3.3  Export Formats

| Format | Description |
|---|---|
| PDF | Executive summaries with charts |
| CSV | Raw projection data |
| Excel (.xlsx) | Formatted workbooks with pivot-ready data |

## 4  Variance Analysis

### 4.1  Budget vs Actual

| Level | Calculation | Threshold |
|---|---|---|
| Company | Actual − Forecast | +/− 15% |
| Project | Actual − Budget | +/− 10% |
| Cost Code | Actual − Budget | +/− 20% |

### 4.2  Variance Categories

| Category | Condition | Action |
|---|---|---|
| Favorable | Actual < Forecast | Investigate under-staffing |
| Unfavorable | Actual > Forecast | Investigate scope creep / OT |
| Neutral | Within threshold | No action |

### 4.3  Root Cause Drill-Down

**Hierarchy:** Company → Project → Cost Code → Employee-Level → Timecard Entries

## 5  Scenario Planning

### 5.1  What-If Analysis

| Scenario Type | Inputs | Output |
|---|---|---|
| Rate Change | New rate, effective date, affected employees | Projected cost impact |
| Headcount Change | Add/remove employees | Adjusted forecast |
| OT Reduction | Target % reduction | Projected savings |
| Project Ramp | New project, crew size, duration, rates | Incremental cost |

### 5.2  Scenario Comparison

- Up to 3 scenarios side-by-side
- Shows delta from baseline
- Scenarios can be saved, shared, and annotated

## 6  Forecasting Accuracy

### 6.1  Accuracy Tracking

**Formula:**

> Accuracy % = 100 − |((Actual − Forecast) / Forecast) × 100|

**Targets:**

| Forecast Horizon | Target Accuracy |
|---|---:|
| Weekly | ≥ 90% |
| Monthly | ≥ 95% |
| Project completion | ≥ 85% |

### 6.2  Model Tuning

- Accuracy reviewed monthly
- Trailing window and seasonal factors recalculated quarterly
- Payroll Admin can manually adjust when conditions change significantly

## 7  Data Requirements

### 7.1  Minimum Data

| Model | Minimum Data Required |
|---|---|
| Trailing Average | 2 weeks processed payroll |
| Project-Based | Active project with budget + 1 week timecards |
| Headcount-Based | Current roster with active rates |
| Seasonal | 2 years historical payroll |

### 7.2  Data Sources

| Source | Provides | Update Frequency |
|---|---|---|
| Timecard Subsystem | Hours | Real-time (as approved) |
| PayRun History | Actual costs | Per pay run |
| Employee Master | Headcount / rates | Real-time |
| Project Master | Budgets / schedules | As updated by PM |

*End of Appendix — Forecasting*
```
