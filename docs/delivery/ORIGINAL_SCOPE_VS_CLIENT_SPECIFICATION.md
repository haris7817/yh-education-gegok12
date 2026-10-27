# Original Scope vs. Client Specification — Evidence Review

_2026-10-27 · Companion to `CLIENT_10_REQUIREMENTS_AUDIT.md`_

## 1. What agreement evidence exists in this workspace

| Source | What it records | Reliability |
|---|---|---|
| `docs/delivery/SCOPE_REVIEW.md` (started 2026-10-22, during the engagement) | The accepted $100 scope definition: "standard install, local/Docker config, dependency setup, asset build, DB init, role/school config, basic branding, minor styling, testing existing features, one deployment, docs, 7-day defect support" — plus a running exclusions log approved item-by-item (S-1…S-7) | Written contemporaneously, but **assistant-generated from the client's instructions**, not the signed order |
| `docs/delivery/YH_EDUCATION_100_SCOPE_AUDIT.md` §Excluded | Exclusions list confirmed at final audit: prepaid credits, auto deduction, leave credit rules, renewal alerts, scheduling calendar, **PDF invoice workflows**, custom ledgers, automated WhatsApp, **native mobile apps**, **custom reports**, subscriptions, UI redesign | Same caveat |
| Engagement instructions (chat, phased prompts) | Repeated the same inclusion/exclusion boundaries at every phase, including the final deployment instructions | Not a file in the repo |
| Git history (`git log main..yh-education-delivery`) | What was actually built: branding, HK config, importer fix, route guard, WhatsApp button, validation relaxation, deployment tooling, docs. **No fee, exam, invoice, or mobile-app commits** | Objective |

## 2. Evidence gap — stated plainly

**The original Fiverr order page and the "five-phase specification" the client refers to are NOT present in this workspace.** No PDF, screenshot, or text of the accepted order terms was ever provided into the repository. Every scope statement above derives from the client's own instructions during the engagement. Therefore:

- This audit **cannot confirm or deny** wording that allegedly appears in the original five-phase specification (e.g., "dashboard customization", "mobile app connection").
- It would be improper to invent contract terms in either direction. **Action: the client (or the freelancer's Fiverr order page) must produce the accepted order text.** Until then, items resting on that wording are classified **SCOPE UNCLEAR — AGREEMENT EVIDENCE NEEDED**.

## 3. Classification of the 10 requirements

| # | Requirement | Classification |
|---|---|---|
| 1 | Complete deployment | (1) Explicitly included — delivered |
| 2 | Branding + 3 role dashboards | (1) Explicitly included ("basic branding"; dashboards are stock GegoK12) — delivered. "Dashboard **customization**": (5)/(2) — no evidence it was in the accepted agreement; needs the order text |
| 3 | Attendance | (3) Existing GegoK12 feature + (1) "testing existing features" — delivered & now functionally proven |
| 4 | Fees, invoices, PDF receipts | (4)/(5) — fee module is a **paid add-on**, not an existing feature; "PDF invoice workflows" appear in the **recorded exclusions**. Not demonstrably included |
| 5 | Academic tracking (gradebook, report cards, analytics) | (4)/(5) — exam stack is a **paid add-on**; "custom reports" recorded as excluded. Core pieces (assignments/lesson plans/timetable) are (3) and work |
| 6 | Security & isolation | (1)/(3) — role configuration and testing were included — delivered |
| 7 | Mobile app connection | (5) as recorded ("native mobile apps" excluded; parent-app gap documented to the client on 2026-10-22). If the original spec explicitly promised connecting vendor apps: (2) — needs the order text; even then it is vendor-dependent (see feasibility doc) |
| 8 | Backups & scheduled tasks | (1) deployment hardening — delivered & restore-tested |
| 9 | Test accounts + docs | (1) handover deliverable — docs done; **3 role test accounts missing** (our gap, ~1 h, close at handover) |
| 10 | UAT + defect corrections | (1) — checklist delivered; 7-day defect support stands |

Key: (1) explicitly included · (2) in original spec but possibly changed in negotiation · (3) existing GegoK12 feature needing configuration · (4) new custom development · (5) not demonstrably included.

## 4. Fairness checks (both directions)

- **Against our earlier reporting:** previous delivery docs called fee management "existing / accessible (DONE\*)". This audit **corrects** that: the fee module is a paid add-on; two core fee drill-down endpoints return HTTP 500 without it (`Class "App\Models\Fee" not found`, reproduced 2026-10-28). Page-200 evidence was over-read. The correction is recorded in `EXISTING_GEGOK12_FEATURE_VERIFICATION.md`.
- **Against the client's claim:** the recorded agreement, the running exclusions log shared during the engagement, and the price point are consistent: $100 bought installation/branding/testing/deployment of the **open-source core**, not paid vendor modules or custom billing/report systems. Nothing in the git history or docs suggests fee/exam/mobile integration was ever attempted, promised in writing here, or paid for.
- **Open to revision:** if the produced Fiverr order text explicitly lists invoices, gradebooks, report cards, or mobile-app connection as included deliverables, the classification of #4/#5/#7 changes to (2) and becomes a commercial discussion about work the $100 demonstrably cannot cover.
