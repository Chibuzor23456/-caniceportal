# Product Requirements Document
## Canice Technologies Client Portal

**Status:** Final — ready for build
**Owner:** Canice Technologies (Canice Okwudili)
**Build target:** Laravel + MySQL, green-field

---

## 1. Note to Claude Code (read first)

This is a **green-field build**. Nothing from any earlier prototype exists in this repo — do not look for or reference old static files. Everything described here is built from scratch.

Build in the phase order given in Section 4. Each phase must be a working, deployable increment on its own — do not attempt to build all modules simultaneously. Confirm one phase works before starting the next.

Every infrastructure decision in Section 3 is final. Do not propose swapping the mail method, hosting, or storage provider — build with what's specified.

---

## 2. Why This Exists

Canice Technologies currently runs client work through WhatsApp, email, spreadsheets, and one hand-edited quotation HTML file per client. That works at a handful of clients but breaks down as the client list grows: nothing is centralized, nothing is trackable, and every new quotation means manually duplicating and re-editing a static file.

This portal replaces that with one system where:
- Every client has their own account and only ever sees their own information.
- Every quotation, contract, invoice, and file lives in one place, permanently accessible to the client — not buried in an email thread.
- Every step of a project (from quotation to final delivery) is visible and timestamped, so nothing depends on someone remembering to send an update.
- The whole experience — for both Canice and the client — feels like a real product, not a folder of PDFs held together by email.

The single measure of success for this build: a client should never have to ask "where are we on this?" — the answer should already be sitting on their dashboard.

---

## 3. Tech Stack & Infrastructure (final — with reasoning)

| Layer | Choice | Why |
|---|---|---|
| Backend framework | Laravel (PHP 8.2+) | Already the team's working stack. Ships with auth scaffolding, queues, file storage abstraction, and mature PDF tooling — most of this PRD is solved by Laravel's defaults, not custom infrastructure. |
| Database | MySQL | Standard Laravel pairing, well supported on the chosen host. |
| Email sending | PHPMailer via SMTP | Already in production use and reliable in practice. Wrap it behind Laravel's `Mail` facade with a custom SMTP mailer config, so sending stays swappable later without touching application code. Do **not** replace this with a third-party transactional API (Resend, Postmark, etc.) — see Section 12 for how this PRD closes the tracking gap without doing that. |
| Hosting | Hostinger Business (shared) | Includes SSH access and unlimited cron jobs at this tier — sufficient for this app's actual scale. See queue handling note below for the one real limitation this introduces. |
| Queue handling | Cron-simulated queue, not a persistent worker | Shared hosting kills long-running background processes, so a normal `php artisan queue:work` daemon isn't viable here. Instead: a 1-minute cron entry runs `php artisan schedule:run`, and the scheduled task itself runs `queue:work --stop-when-empty --max-time=50`, processing whatever's queued and exiting cleanly. This means background jobs (PDF generation, emails, reminders) run with up to ~1 minute of delay instead of instantly — a non-issue at this app's scale. Document this setup explicitly in the deployment README so it isn't accidentally "fixed" into a persistent worker that then gets killed by the host. |
| File storage | Cloudflare R2 (S3-compatible) | Client files (signatures, payment proofs, project deliverables, contracts) must survive server migrations and not be capped by server disk size. Laravel's `s3` filesystem driver points at R2 with no code changes needed elsewhere. R2 has no egress fees, which matters because clients will regularly download their own files. All client-facing file links are signed and time-limited — never public. |

---

## 4. Build Order

Build and ship in this sequence. Each phase is usable on its own — Canice should be able to start replacing manual work after Phase 2, without waiting for the whole system.

1. **Auth + Client Management + Onboarding** — the foundation everything else sits on.
2. **Quotation Module** — section builder, pricing, PDF generation, e-signature. This is the highest-leverage phase: it directly replaces the single most painful manual process today.
3. **Projects + Milestone Review Flow** — turns an accepted quotation into a trackable, collaborative project.
4. **Invoices + Payment Proof Flow** — the money side.
5. **Messaging + Notifications + Activity Log + Search + Email Delivery Tracking** — the polish and trust layer that ties everything together.

---

## 5. Users & Access

**Administrator** — one role in v1, full access to everything. This is Canice Technologies. The data model should anticipate additional admin/staff roles later (see Section 15) without requiring a rebuild, but only one role needs to actually exist now.

**Client** — one account per client company. A client can only ever see data tied to their own company: their own quotations, projects, invoices, files, and messages. This boundary must be enforced at the database query / policy layer, not just hidden in the UI — a client should never be able to reach another client's record even by guessing a URL.

---

## 6. Design System & Layout Spec

This section translates the reference dashboard style into an explicit build spec, so both the admin and client sides feel like one coherent, professional product rather than two different tools bolted together.

### 6.1 Overall visual language
- Page background: soft off-white / light gray, not pure white — this is what makes the white cards read as "floating" rather than flat.
- Cards: white, rounded corners, subtle drop shadow, generous internal padding. No hard borders — separation comes from shadow and background contrast, not lines.
- Typography: one clean sans-serif throughout, clear size hierarchy (large bold numbers on stat cards, medium-weight section headers, regular body text for descriptions).
- Color use is functional, not decorative: green for positive/increase, red for negative/decrease, and one brand accent color used sparingly for primary actions and active nav states.

### 6.2 Sidebar (both Admin and Client — shared structure)
- Top of sidebar: a small dark rounded-square logo badge next to the workspace/company name, with a secondary line underneath — on the admin side this can show something like the active client count; on the client side, just their company name is enough since there's no equivalent "member count."
- Below that: a search bar styled as a real input, with a `⌘K` shortcut hint on the right — this should actually trigger a global search overlay (see Section 6.7).
- Nav items are grouped under small uppercase gray section labels (not just one long undifferentiated list). Example grouping:
  - **Admin sidebar:** "Workspace" (Overview, Clients, Quotations, Projects, Invoices) / "Manage" (Messages, Activity, Settings)
  - **Client sidebar:** "Overview" (Dashboard) / "My Work" (Projects, Documents, Invoices, Messages)
- Where a nav item has a single obvious headline number, show it inline on the right side of that row — e.g. Invoices can show the outstanding total, Quotations can show the pending count — the same pattern as a metric appearing directly in a sidebar row rather than only inside the page itself.
- The active nav item is visually distinct — filled background, not just bold text — matching the reference image's "Overview" item styling.
- Sidebar is collapsible on smaller screens, collapsing to icon-only.

### 6.3 Top of main content area
- Large page title (e.g. "Overview," "Clients," "Project: First Care Website") in bold, with a small freshness line directly underneath — e.g. "Updated 2 mins ago" — on any page showing data that changes (dashboards, activity feeds). This should reflect a real last-refreshed timestamp, not a static label.
- A row of pill-style filter/segment tabs directly under the title where the page supports filtering (e.g. date range or status filters on list views), styled as rounded pill buttons with one active state.

### 6.4 Stat cards
Used on both dashboards. Each stat card contains:
- A colored icon badge (rounded square, color varies per metric — blue, orange, green, etc.) on the left
- A label (e.g. "Weekly Active Clients," "Pending Quotations")
- A large bold number as the primary value
- A small delta indicator underneath — green up-arrow with percentage for an increase, red down-arrow for a decrease, compared to the prior period

Admin dashboard stat cards: Total Clients, Active Projects, Pending Quotations, Expiring Quotations, Pending Invoices, Phases Awaiting Review.

Client dashboard stat cards: Active Projects, Current Progress, Current Phase Status, Outstanding Invoices, Recent Files.

### 6.5 Secondary insight widget
Below the row of stat cards, both dashboards include one larger widget that goes beyond a single number — pairing a big headline figure and delta with an actual small bar/trend chart underneath, the same pattern as a "Visitor Insights" panel on an analytics dashboard. This is where a trend actually becomes visible over time, not just a snapshot.

- **Admin dashboard:** a "Quotation Activity" widget — quotations sent vs. accepted over the last several weeks, as a simple bar chart, with the current period's total and its delta from the prior period shown above the chart.
- **Client dashboard:** a "Project Progress" widget — their progress percentage over time (rising as phases get approved), shown the same way.

This is a genuine data visualization, not decorative — it should read real values from the database, not placeholder numbers.

### 6.6 List and detail views
- Client list, quotation list, project list, invoice list all follow the same table/card-hybrid pattern: clean rows, status shown as a colored pill badge (not just text), key metadata visible without opening the record.
- Detail views (a single client, a single project) use a header block (name, status, key metadata) followed by tabbed or sectioned content below — matching the card-based layout, not a dense form.

### 6.7 Global search
Triggered by `⌘K` or clicking the search bar. Searches across Clients, Projects, Quotations, Invoices, Files, and Contracts, returning grouped results by type.

### 6.8 Full Navigation Map

**Admin sidebar**

*Workspace*
- Overview — dashboard per Section 8
- Clients — list, profiles, create/edit/suspend/delete, onboarding trigger (Section 9)
- Quotations — full builder, templates, version history, status tracking (Section 10)
- Projects — milestone/phase review flow (Section 13)
- Invoices — create/send/track/export (Section 14)

*Manage*
- Contracts — create/upload/send/track (Section 14)
- Files — cross-client file view (Section 14)
- Messages — unified inbox across all client threads (Section 14)
- Activity Log — full system-wide audit trail (Section 14)
- Settings — company info, email templates, SMTP config, 2FA, Email Delivery panel (Sections 7, 12, 15)

**Client sidebar**

*Overview*
- Overview — dashboard per Section 8

*My Work*
- My Projects — phase timeline, deliverable review threads, phase approval (Section 13)
- Quotations — pending action + locked signed view once accepted (Section 10)
- Contracts — same pattern as Quotations (Section 14)
- Documents — permanent archive of signed quotations/contracts/paid invoices (Section 14)
- Invoices — outstanding/paid, payment proof upload (Section 14)
- Messages — client-admin thread (Section 14)

Both sidebars share the `⌘K` global search and a notifications bell, both living in the shared header (Section 6.7) rather than as sidebar entries.

---

## 7. Authentication & Security

- Administrator login and Client login (separate login flows, same underlying auth system).
- Forgot password / reset password, with secure token expiry.
- Email verification on account creation.
- Secure, properly expiring sessions.
- **Two-factor authentication on the Administrator account.** Not required for clients in v1, but mandatory for admin — this system holds signed legal agreements and client personal data, and password-only access to that is not good enough.
- CSRF protection and rate limiting on all auth and form endpoints.
- Full audit logging of security-relevant events (see Section 14).
- Client data isolation enforced at the policy/query layer — every query touching client-scoped data must be scoped to the authenticated client's own records.

---

## 8. Dashboards

### Administrator Dashboard
Shows, at a glance: Total Clients, Active Projects, Pending Quotations, Expiring Quotations, Pending Invoices, **Phases Awaiting Review** (a live count of project phases currently Pending Review or In Discussion — see Section 13 — so nothing sits waiting on Canice without him noticing), a feed of Recent Activity, Recent Client Messages, a Calendar Overview of upcoming deadlines/meetings, and a compact **Email Delivery Health** indicator (failed/bounced send count over the last 7 days, linking through to the full panel in Section 12). This page exists so Canice never has to go looking for what needs attention — it should surface it.

### Client Dashboard
Shows: their Active Projects and Current Progress (derived automatically — see Section 13), the **current phase's status** (Pending Review / In Discussion / Approved) so it's immediately clear whose turn it is to act, Pending Quotations awaiting their decision, Outstanding Invoices, Recent Files, Notifications, Upcoming Meetings, a quick link into the **Document Archive** (Section 14) for signed quotations/contracts/invoices, and **any domain/hosting renewal dates tied to their project**. That last one matters specifically because renewal dates have historically lived only in Canice's head — surfacing them here prevents a client's site from lapsing silently. On project completion, the dashboard also surfaces the automatic testimonial prompt (Section 15) here rather than only by email.

### Extended Admin Analytics (Overview page, below the primary stat row)
A second and third row of stat cards: Total Revenue, Paid Invoices, Overdue Invoices, Files Uploaded, Unread Messages / New Clients, Completed Projects, Average Project Duration, Client Satisfaction (averaged from Section 15 testimonial ratings), Conversion Rate (quotations accepted ÷ sent). Below that: a **Revenue Overview** line chart (paid invoice totals over time, with period-over-period delta) alongside the existing Quotation Activity strip (Section 6.5); a **Top Clients by Revenue** ranked list; a **Projects by Status** donut mapped to the real project status enum (Section 13), not a generic category set; and an **Invoice Overview** breakdown (Total/Paid/Outstanding/Overdue/Due in 7 days). Further down: a **Client Growth** chart (monthly client acquisition), a **Revenue by Service** bar chart (requires adding a `service_category` field to quotation pricing line items — Section 10 — so this reflects real categorization rather than guessed data), a **Payment Collection Rate** circular indicator (paid ÷ total invoiced), an **Upcoming Deadlines** timeline aggregating existing dates (quotation expiry, contract renewal, project delivery, invoice due) rather than a new data source, and **Recent Client Logins** as a filtered view of the existing Activity Log (Section 14) — not a new table. A **Smart Insights** panel closes this out, using the rule-based logic from Section 16 (e.g. "3 quotations expire this week," "2 invoices overdue," "Client hasn't logged in for 14 days") — deliberately named Smart Insights rather than AI Insights, since it's templated logic against real thresholds, not a model.

**Explicitly cut from v1, and why:** Team Productivity and Support Tickets widgets assume staff beyond Canice, which Section 5 and Section 16 already rule out for v1 — there's no team to measure yet. A Monthly Pipeline Kanban (leads/discovery/proposal/won/lost) is a genuine CRM module requiring a new "Lead" entity this PRD doesn't have — real feature, but a future phase, not a dashboard widget to bolt on silently.

### Extended Client Dashboard Widgets
A personalized greeting header ("Good morning, [name]"). A **Project Progress** donut mapped to real phase status categories (Section 13). A **Start a New Project** CTA banner — relevant for repeat clients, consistent with the multi-quotation handling already speced in Section 11. **Payment History** and **Quotation History** tables, a **Contract Status** list (signed/unsigned, expiry, download, link to the signature verification page from Section 10), and **Shared Files** grouped by category (Design/Documents/Invoices/Contracts/Deliverables) as a refinement of the file management spec in Section 14. **Average Response Time** — a real, visible number reflecting the response-time expectation already committed to in Section 14, not just a static line. A client-facing **Smart Insights** panel using the same rule-based pattern as the admin side.

**Explicitly cut or scaled down, and why:** "Team Working on Your Project" (photos/roles/availability) assumes staff beyond Canice — same conflict as the admin side, cut for v1. Upcoming Meetings keeps a plain list without Join/Reschedule actions, since calendar integration is already excluded from v1 (Section 16). The project timeline stays the simpler visual timeline already speced in Section 13 rather than an interactive Gantt chart — disproportionate complexity for a linear 4-phase sequence.

---

## 9. Client Management

### What's stored per client
Company Name, Contact Person, Email, Phone, Address, Industry, Notes, Date Joined, Status (Active/Suspended/Past), **Tags** (freeform labels like "healthcare," "retail," "active," "past" — filterable on the client list, which becomes genuinely useful once the client list grows past a handful), and **Referral Source** (freeform — how this client found Canice Technologies, useful for his own growth tracking later, not client-facing).

### Admin actions
Create, Edit, Suspend, Delete, View full Client Profile (a detail page pulling together everything tied to that client — quotations, projects, invoices, files, messages, in one place).

### Onboarding automation
The moment a client is created: generate login credentials and a temporary password, create the account, send a welcome email containing login details, and log the onboarding event to the activity log. No manual step should be required beyond filling in the client's initial details.

---

## 10. Quotation Module

This is the module directly replacing the current single-largest pain point (hand-editing one HTML file per client), so it deserves the most care.

### Section-based builder
Quotations are composed from independent, reorderable sections — never a single large text editor, because that's what makes every quotation today require manual full-document editing instead of quick composition. Each section supports Title, rich text content, images, tables, and lists.

Admin can: Add a section, Delete a section, Reorder sections via drag-and-drop, Duplicate a section, and Save.

**Built-in section types**, ready to drop into any quotation: Project Overview, Objectives, Scope of Work, Deliverables, Timeline, Investment, Payment Terms, Payment Schedule (the phased breakdown — deposit now, balance before go-live — kept distinct from Payment Terms since clients need it visually broken out, not buried in prose), Payment/Bank Details (the account details block, so a client sees where to send a deposit without waiting for a separate invoice), Client Responsibilities (what the client must provide — content, images, logins — to set expectations and reduce delay disputes), Agreement Parties (developer/client info block), Domain & Hosting Renewal (conditional — only relevant when hosting is part of the scope), Terms & Conditions, Acceptance, Additional Notes.

This list is a starting library, not a requirement — since every quotation is fully independent (see below), the admin can add, skip, or write custom sections beyond this list for any project.

### Pricing table
A structured line-item table: Service name, Quantity, Unit Price, Discount, Tax, with line totals and a Grand Total calculated automatically — never manually typed in, to avoid arithmetic errors on a legal document. Currency is configurable.

### Independence between quotations
Every quotation is its own independent record with its own sections and pricing table — nothing forces two quotations to look alike. When a template is loaded, its content is **copied** into the new quotation, never linked to it: editing a template afterward must never retroactively change a quotation already built from it, and editing one quotation must never affect another, even if both started from the same template. This is what allows a completely different scope, deliverable set, and pricing structure per client/project while still sharing the same document shell (letterhead, PDF layout, signature block, QR code) for brand consistency.

### Templates
Any quotation can be Saved as a Template, and templates can be Loaded, Duplicated, or Deleted. This is what turns "build a new quotation" into a five-minute task instead of a from-scratch document.

### Version history
Every revision to a sent quotation creates a new numbered version (V1, V2, V3…). The admin can compare versions side-by-side to see what changed. The client only ever sees the newest version — they're never confused by an outdated draft.

### Status lifecycle
Draft → Sent → Viewed → Accepted / Rejected → Expired → Archived.

### Validity
Every quotation is valid for 14 days from creation, with the expiry date calculated automatically. Once expired: the Accept button disappears, status flips to Expired, and the client is offered the option to request a revision instead.

### Rejection handling
If a client rejects a quotation, they're asked to state a reason. That reason is captured and **automatically carried forward as context into the next draft revision**, so the admin isn't retyping what the client already explained.

### PDF generation
Every quotation PDF includes: Company Logo, Client Information, Quotation Number, Issue Date, Expiry Date, a Table of Contents for longer quotations, the Pricing Table, Terms, Footer, Page Numbers, a QR verification code (see below), and an optional watermark.

### Signatures
Admin uploads a company signature once, in Settings. It's automatically applied — along with name and position — to every generated quotation. No manual insertion per document.

### Client acceptance
The client can type their full name or draw a signature on a signature pad, then confirm. On acceptance, the PDF regenerates to include both signatures and the exact acceptance timestamp. The system stores the Acceptance Date, Time, IP Address, Browser, and Device — this is what makes the signed document actually defensible later, not just a nice PDF.

### Signature verification page
The QR code embedded in every signed document links to a public verification page showing the document reference, who signed, when, and the IP-verified confirmation. This exists so a signed agreement can never be credibly disputed — anyone can scan the code and see the recorded proof independently of the PDF itself.

### Post-signature lock (critical — build exactly as follows)
Once a quotation reaches Accepted status, its secure link must never render the signing form again. This is decided **server-side, before the page is built** — not hidden with CSS or JavaScript — so there is no path to reach the signature pad on an already-signed document.

The controller resolves the view purely by current status:
- **Draft / Sent / Viewed (not expired)** → render the normal acceptance form and signature pad.
- **Accepted** → render a distinct, read-only "Signed" view: a banner stating who signed and the exact timestamp, the final signed PDF displayed inline with a download button. The signature-capture markup is not included in this view's response at all.
- **Expired** → render an expired state with a "Request revision" action, no form.
- **Rejected** → render that state, no form.

**Race condition handling:** wrap the accept action in a database transaction with a row lock on the quotation record. If two requests hit "Accept" at nearly the same time (e.g. two open tabs), the first to acquire the lock creates the signature record and updates status; the second is redirected straight into the read-only Signed view instead of creating a duplicate signature or overwriting the first one.

This same lock pattern applies to Contracts (Section 14), which reuse the same signature mechanism.

---

## 11. Workflow Automation

These are the events that should happen without anyone clicking a separate button:

- **Client created** → account + password generated, welcome email sent, event logged.
- **Quotation sent** → PDF generated, secure link created, email dispatched, send attempt logged (see Section 12).

**Secure link format:** the emailed quotation link uses a long random token, not the reference number or a sequential ID — e.g. `https://portal.canicetechnologies.com/q/{random-token}` — so one quotation's link can never be guessed from another's. This link requires no login (that's what makes "Quotation Viewed" tracking work on open). It **expires** — tied to the quotation's normal 14-day validity window pre-signature, and retired roughly 30 days after signature as security hygiene, since a token sitting in an old email shouldn't stay a permanent door into a signed legal document.

**Permanent signed copy:** once accepted, the canonical place to view the unedited signed quotation is inside the client's authenticated portal, at a clean human-readable URL rather than a token — e.g. `https://canicetechnologies.com/quotation/brightpathconsulting`. Access control here comes from the login session, not URL secrecy, so no random token is needed. This page is linked from both the Documents Archive (Section 14) and the project page, and remains available indefinitely — it's what the client falls back to once the emailed link has expired. Slugs default to the client's name; on the rare case a client has more than one quotation (a repeat client, a second project), the system appends a number automatically on collision (`brightpathconsulting`, then `brightpathconsulting-2`) rather than requiring a different URL scheme for that case.

This is distinct from the in-app authenticated listing (`/app/quotations/{reference}`, only reachable while logged in) and the public verification page (`/verify/{reference}`, intentionally public since it only ever shows non-sensitive confirmation data, never contract content).
- **Quotation viewed** → the moment the client opens the secure link, a view timestamp is recorded and the admin is notified. (This is deliberately based on link access, not email-open tracking — see Section 12 for why.)
- **Quotation accepted** → signed PDF generated, a Project is automatically created from it, both parties notified.
- **Quotation rejected** → admin notified, rejection reason captured (Section 10).
- **Quotation expired** → status updated automatically, client and admin both notified.
- **Reminder emails** fire automatically: 3 days before expiry, 1 day before expiry, and on expiry itself.

---

## 12. Email Delivery Tracking (closing the SMTP visibility gap)

Raw SMTP through PHPMailer sends reliably, but by itself gives no data back about what happened after the send — no bounce webhook, no open/click tracking, no reputation dashboard. Rather than switching providers, this module builds the missing visibility directly:

### Email Log
Every outgoing email (welcome, quotation sent, reminder, invoice, etc.) is recorded: recipient, email type, the related record (quotation ID, invoice ID, etc.), timestamp, and the PHPMailer send result — including the raw SMTP response if the send fails immediately. This answers "did we attempt to send this" and "did the server accept it" at minimum.

### Bounce detection via a monitored mailbox
SMTP alone won't hand over bounce webhooks, but bounces still arrive somewhere — as non-delivery report (NDR) emails sent back to the configured Return-Path address. Set up a dedicated mailbox for this on Hostinger. Add a scheduled job (riding the same 1-minute cron already handling queues) that polls this mailbox over IMAP, parses incoming NDRs, matches them back to the original send via Message-ID, and updates that row in the Email Log to "Bounced." This isn't instant like a webhook, but it closes the loop within minutes.

### "Quotation Viewed" stays link-based, not email-based
As specified in Section 11, "viewed" is triggered by the client opening the secure quotation link — not an email open-tracking pixel. This is actually the more reliable signal, since many email clients block tracking pixels by default anyway.

### Admin-facing Email Delivery panel
A tab (under Settings or folded into the Activity Log) listing recent send attempts with a clear status: Sent / Failed / Bounced. This is a small, purpose-built version of what a paid provider's dashboard would show — built in-house instead of bought.

### DNS hardening
Proper SPF, DKIM, and DMARC records configured for the sending domain via Hostinger's DNS manager. This is the actual preventative lever against deliverability problems — reducing the odds of a shared-IP reputation issue happening at all, rather than only detecting it after the fact.

### Google Postmaster Tools
Register the sending domain — free, and gives real spam-rate and reputation data for anything sent to Gmail addresses specifically, which is a reasonable stand-in for a paid deliverability dashboard given most clients likely use Gmail.

**Known limits, stated plainly:** this setup won't reveal if a message lands in spam on non-Gmail providers, and bounce detection only works if the receiving mail server actually sends a parseable NDR — some silently drop instead. It's a real improvement over flying blind, not full parity with a paid transactional provider — and that trade-off is accepted here in exchange for keeping the SMTP setup that's already working.

---

## 13. Projects & Milestone Review Flow

A Project is automatically created the moment a quotation is accepted (Section 11). Each project has a Title, Description, Timeline, an ordered list of Phases, Files, Notes, overall Status, Expected Delivery Date, and Completion Date.

**Phase statuses:** Planning, Design, Development, Testing, Review, Completed, Paused, Cancelled.

### The review loop — this is the core new workflow, build exactly as follows:

1. Admin uploads a deliverable for the current phase — files, screenshots, a link, or notes.
2. The phase status changes to **Pending Review**, and the client is notified.
3. The client can leave a comment on that phase. This is a **threaded discussion attached to the phase**, not a single one-shot approval field — multiple rounds of back-and-forth (questions, requested tweaks, clarifications) are the expected normal case, not an edge case.
4. The admin replies inline, within the same thread.
5. Every new comment from either side bumps the phase status to **In Discussion**, and the interface should clearly show whose turn it is to respond — waiting on admin, or waiting on client — so a conversation never quietly stalls without either side noticing.
6. When the client is satisfied, they click **Approve Phase**. This:
   - Timestamps the approval.
   - Locks the thread to read-only — comments are never deletable once a phase is approved, because this thread *is* the audit trail for that phase of work.
   - Unlocks the next phase in the sequence.
7. **Overall project progress percentage is calculated automatically** as (approved phases ÷ total phases) — never manually entered by the admin. This removes the single most common failure mode of project trackers: the admin simply forgetting to update a number.

### Visual timeline
Both admin and client views show a visual timeline of phases — clearly distinguishing completed, current, and upcoming — matching the card-based visual language from Section 6.

---

## 14. File Management, Contracts, Invoices, Messaging, Notifications, Activity Log

### File Management
Admin uploads Images, Documents, Contracts, Videos, ZIP files, and Source Files — all stored on Cloudflare R2, never local disk (Section 3). Clients can Preview, Download, and see their own upload history. All client-facing download links are signed and time-limited, never public.

**Document archive:** every signed quotation, signed contract, and paid invoice is accessible from one dedicated tab in the client's account — they should never have to dig through old emails to find a document they already have.

### Contracts
Admin can Create a contract, Generate its PDF, Upload an existing contract, or Send it. The client can View, Download, Accept, and Sign it — using the same signature capture mechanism built for quotations, including the identical post-signature lock behavior specified in Section 10.

### Invoices
Admin can Create, Generate PDF, Track Payment Status, and Send an invoice. Status flow: Draft → Sent → Paid → Overdue → Cancelled.

**Auto-population from the Payment Schedule.** An invoice is never built from a blank form. Once a quotation is accepted and its project created, "Create Invoice" on that project pre-fills a draft invoice from the next unbilled phase of the quotation's Payment Schedule (Section 10) — description, amount, and due condition all pulled from what the client already agreed to and signed, not retyped. In the common two-phase pattern this means: Invoice 1 pre-fills from the upfront/deposit phase (typically sent immediately after signing, matching how Canice actually works), and Invoice 2 later pre-fills from the balance phase (typically sent before go-live). The admin can still edit the pre-filled draft before sending, but starts from real numbers, not a blank slate.

**Invoice document layout** reuses the same letterhead, color system, and bank-details block as the quotation/agreement PDF (Section 10) for visual consistency, but is intentionally shorter: Company Logo, Bill To, Invoice Number, Issue Date, Due Date, a line-items table (pulled from the triggering Payment Schedule phase), Total Due, Payment/Bank Details, and current Status shown as a colored pill (Draft/Sent/Paid/Overdue/Cancelled). No section-builder is needed here the way quotations have one — invoices are structurally simple and don't vary the way project scope does.

Payment confirmation stays manual in v1: the client sees bank transfer details, uploads proof of payment, and the admin verifies and marks the invoice Paid. Online payment gateway integration is explicitly out of scope for v1 (Section 16).

**Export:** admin can export invoices and the client list to CSV/PDF, for use in external accounting — this system is not meant to replace proper bookkeeping, just to feed it clean data.

### Messaging
A simple threaded messaging system per client — **polling/refresh-based, not real-time websockets**, which keeps this phase's scope realistic. Supports text, images, and documents, with read receipts. Typing indicators are explicitly cut from v1 (Section 16).

A stated response-time expectation should be visible on the messaging tab — even a simple line like "We typically respond within 24 hours." This single detail does a lot for how the wait feels on the client's side.

### Notifications
In-app and email notifications fire on: New Client, Quotation Sent/Viewed/Accepted, Project Updated, File Uploaded, Invoice Created/Paid, and Message Received.

### Activity Log
Every meaningful action in the system is recorded — client created, quotation sent/viewed/accepted, project created/updated, invoice generated, file uploaded, login events, password changes.

**A simplified version of this log must be visible to the client**, not just the admin — a plain-language timeline of what's happened on their own project or account, viewable without asking anyone. This is one of the highest-leverage trust features in the whole system: it turns "trust me, it's progressing" into something the client can see for themselves at any time.

---

## 15. Testimonial Capture, Search, Settings, Email Templates

### Testimonial capture
On project completion, the client is automatically prompted for a testimonial or review — this replaces Canice having to remember to ask manually, and captures feedback while the experience is still fresh.

### Global search
Search across Clients, Projects, Quotations, Invoices, Files, and Contracts from one search bar (Section 6.7), with results grouped by type.

### Settings
Company Information (Logo, Signature, Address, Phone, Email), Currency, Timezone, Email Template management, SMTP Configuration, Notification Preferences, and the Email Delivery panel from Section 12.

### Email Templates
Customizable templates for: Welcome Email, Quotation Email, Reminder Email, Invoice Email, Project Update, Password Reset, Contract Email, Completion Email.

---

## 16. Rule-Based Smart Drafting & Auto-Summaries (no external AI API)

This is deliberately **not** generative AI — no external API call and no self-hosted model. It's rule-based logic against data the system already has, and it delivers most of the practical value of "AI-assisted" drafting without the infrastructure or ongoing cost.

**Quotation draft assist:** when the admin selects a client to quote, the system suggests a starting template based on that client's tags (Section 9) — e.g. a client tagged "healthcare" pre-loads the section structure and pricing pattern used on past healthcare projects, pulled from the Template library (Section 10). No text is generated; existing template content is intelligently selected.

**Auto-generated project status lines:** since project progress is already calculated automatically from approved phases (Section 13), a plain-language status sentence can be built entirely from string templates against that structured data — for example: *"Project is {progress}% complete. Phase {n} ({phase name}) is currently {status} — waiting on your response."* This can populate the client dashboard and a weekly digest email without any generative step.

This is distinct from true free-text AI generation (natural, non-templated prose), which genuinely does require either an external API or a self-hosted model with real compute — neither of which fits this build's constraints. That capability stays in Section 17 as future scope.

---

## 17. Explicitly Out of Scope for v1

These are deliberately excluded to keep the build focused — not forgotten, just sequenced for later:

- Multi-tenant SaaS architecture (this is a single-business tool, not a product to resell)
- Employee accounts, team roles, internal task assignment
- Online payment gateway integration
- Real-time messaging (websockets) and typing indicators
- Calendar synchronization with external calendars
- True free-text AI-generated quotations or AI-written project summaries (natural, non-templated prose via an external API or self-hosted model) — the rule-based version of this now ships in v1; see Section 16
- Client portal white-labeling
- Third-party API integrations beyond what's specified here

---

## 18. Non-Functional Requirements
Responsive design across desktop, tablet, and mobile, matching the layout spec in Section 6 on every screen size. Fast page loads. Clean, modular, scalable codebase with reusable components. Optimized database queries — no N+1 query patterns on list views. Comprehensive input validation on every form. Professional, print-quality PDF layouts throughout (quotations, contracts, invoices).
