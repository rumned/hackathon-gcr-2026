# CRM backend (PHP + MySQL)

JSON API for the SuiteCRM 7-style wireframe (`crm-wireframe.html`). Plain PHP with PDO, no framework and no Composer packages.

Table and column names follow SuiteCRM 7: UUID ids, a `deleted` flag for soft deletes, `date_entered` / `date_modified` / `created_by` / `modified_user_id` / `assigned_user_id` on every module table, `parent_type` + `parent_id` on calls, meetings, tasks and notes, and the same relationship, email address, audit, favorites and tracker tables. Dropdown values are copied from SuiteCRM 7's language file.

## Requirements

- PHP 8.1 or newer with the `pdo_mysql` and `mbstring` extensions
- MySQL 8 (tested on 8.0.46). MariaDB, which XAMPP ships, has not been tested.

## Setup

1. Create a database and a user, then load the schema:
   ```
   mysql -u root -p -e "CREATE DATABASE crm CHARACTER SET utf8mb4"
   mysql -u root -p crm < sql/schema.sql
   ```
   A database created from an earlier `schema.sql` only needs the migrations it is missing, in order:
   ```
   mysql -u root -p crm < sql/migrations/001_customer_fields_quotes_outbox.sql
   mysql -u root -p crm < sql/migrations/002_customer_kind_and_stage.sql
   mysql -u root -p crm < sql/migrations/003_sales_status_pipeline.sql
   ```
2. Copy `config.example.php` to `config.php` and fill in the database details.
3. Add the users and the sample data shown in the wireframe:
   ```
   php scripts/seed.php            # users admin, sally, max with password "changeme"
   php scripts/seed.php mypassword # or choose the password
   ```
4. Run it:
   - PHP's built-in server: `php -S localhost:8000 -t public public/index.php`
   - Apache (e.g. XAMPP): point the site, or a subfolder, at `public/`. `public/.htaccess` sends requests to `index.php` and needs `mod_rewrite`. The subfolder setup has not been tested.

### Running with the frontend

The frontend (`../frontend`) calls this API from PHP, so run both:

```
cd crm-backend && php -S 127.0.0.1:8000 -t public public/index.php
cd frontend    && php -S 127.0.0.1:8080
```

Then open http://127.0.0.1:8080/login.php and log in as admin / changeme. The API address is set in `frontend/config.php`. With XAMPP, set it to the URL of `crm-backend/public` instead.

## How requests work

- Log in with `POST /api/auth/login`. The response sets a session cookie, and every other route needs it.
- From a browser frontend, send requests with `credentials: 'include'`. If the frontend runs on another port (e.g. Vite on 5173), set `cors_origin` in `config.php` to that origin.
- POST and PUT bodies must be JSON with `Content-Type: application/json`, including logout (send `{}`). Other content types get a 415 response.
- Errors come back as `{"error": "..."}`. Validation errors are status 422 with `{"error": "Validation failed", "errors": {"field": "message"}}`.
- Dates and times are UTC, in the format `YYYY-MM-DD HH:MM:SS`. Input also accepts `YYYY-MM-DD HH:MM` and `YYYY-MM-DDTHH:MM:SSZ`.
- `PUT` changes only the fields you send. Keys that aren't editable fields are ignored, so you can send back a whole record from `GET`.
- Deleting a record sets `deleted = 1`; the row stays in the table.

`{module}` is one of: `accounts`, `contacts`, `leads`, `opportunities`, `cases`, `calls`, `meetings`, `tasks`, `notes`, `campaigns`, `prospect_lists` (target lists).

## Endpoints by wireframe screen

### Login screen and user menu

| Method | Path | Notes |
|---|---|---|
| POST | `/api/auth/login` | `{"user_name", "password"}` |
| POST | `/api/auth/logout` | |
| GET | `/api/auth/me` | Profile |
| GET | `/api/users` | Employees, and the "Assigned to" Select button. Query: `q`, `status=Active` |

### Top bar

| Method | Path | Notes |
|---|---|---|
| GET | `/api/search?q=acme` | Global search over accounts, contacts, leads, opportunities, cases (names starting with `q`) |
| GET | `/api/alerts` | `unread_count` for "Alerts (3)" plus the latest alerts. An alert is created when someone assigns a record to another user |
| POST | `/api/alerts/{id}/read` | |
| POST | `/api/{module}` | Quick create |

### Sidebar

| Method | Path | Notes |
|---|---|---|
| GET | `/api/recently-viewed` | Last 10 records whose detail view you opened. `?module=accounts` for the module menu's list |
| GET | `/api/favorites` | `?module=accounts` optional |
| POST | `/api/favorites` | `{"module": "accounts", "record_id": "..."}` (also the star on the detail view) |
| DELETE | `/api/favorites/{module}/{id}` | |

### Home dashboard

| Method | Path | Notes |
|---|---|---|
| GET | `/api/dashlets/my-calls` | Planned calls assigned to you, soonest first |
| GET | `/api/dashlets/my-meetings` | Planned meetings assigned to you |
| GET | `/api/dashlets/my-open-opportunities` | Not Closed Won / Closed Lost |
| GET | `/api/dashlets/pipeline-by-sales-stage` | Count and amount per open sales stage, for the bar chart |
| GET | `/api/dashlets/my-accounts` | |
| GET | `/api/dashlets/my-leads` | |
| GET / PUT | `/api/preferences/Home` | Dashboard tabs and dashlet layout ("Add dashlets", "Add tab", "Edit tabs"). PUT body `{"contents": <any JSON>}` |

Dashlets take `page` and `per_page` (default 5).

### List view

| Method | Path | Notes |
|---|---|---|
| GET | `/api/{module}` | See query parameters below |
| GET / PUT | `/api/preferences/Accounts_ListView` | Columns chosen with the "Columns" button |
| POST | `/api/{module}/mass-delete` | `{"ids": [...]}` |
| POST | `/api/{module}/mass-update` | `{"ids": [...], "fields": {"industry": "Retail"}}`. All records are updated or, on a validation error, none |
| GET | `/api/{module}/export` | CSV. `?ids=id1,id2` for selected rows, otherwise the same filters as the list |
| POST | `/api/{module}/merge` | `{"primary_id", "duplicate_ids": [...], "fields": {...optional}}`. Moves contacts, opportunities, leads, cases, activities, emails and target list memberships to the kept record, then deletes the duplicates |
| POST | `/api/prospect_lists/{listId}/records` | Add to target list: `{"module": "accounts", "ids": [...]}` |
| GET | `/api/prospect_lists/{listId}/records` | Members of a target list |
| POST | `/api/{module}/import` | Import (module menu): `{"csv": "name,billing_address_city,email1\n..."}`. Header row holds field names; `email1` is the primary email. Valid rows are kept when other rows fail; errors are reported per row |

List query parameters:

- `page`, `per_page` (default 10, max 100). The response has `records`, `total`, `page`, `per_page`, which covers "(1 - 10 of 54)".
- `sort` (a field, `name`, `date_entered` or `date_modified`) and `dir` (`asc` / `desc`). Default: newest first.
- `q`: basic search, names starting with `q`.
- `filter[field]=value`: text fields match "starts with", other fields match exactly. `filter[field][]=a&filter[field][]=b` matches any of the values. Date fields take `filter[field][from]=2026-10-01&filter[field][to]=2026-10-31`. Accounts, contacts and leads also take `filter[email]=...`.

Each list row includes the related names the table needs: `assigned_user_name`, `email1` (primary email), `account_name` and so on.

### Detail view

| Method | Path | Notes |
|---|---|---|
| GET | `/api/{module}/{id}` | All fields, `email_addresses`, related names, `created_by_name`, `modified_by_name`, `is_favorite`. Also adds the record to "Recently viewed" |
| DELETE | `/api/{module}/{id}` | Actions > Delete |
| GET | `/api/{module}/{id}/duplicates` | Actions > Find duplicates: same name (or same office phone for accounts), or a shared email address |
| GET | `/api/{module}/{id}/changelog` | Actions > View change log (accounts, contacts, leads, opportunities, cases) |
| GET | `/api/{module}/{id}/subpanels/{subpanel}` | Accounts subpanels: `activities`, `history`, `contacts`, `opportunities`, `leads`, `cases` |
| POST | `/api/{module}/{id}/subpanels/{subpanel}` | Subpanel "Select": `{"record_id": "..."}` |
| DELETE | `/api/{module}/{id}/subpanels/{subpanel}/{recordId}` | Remove the link; the record itself is kept |

- Actions > Duplicate: `GET` the record, then `POST` it as a new record.
- Subpanel "Create": a normal `POST /api/{module}` with `account_id` (contacts, opportunities, leads, cases) or `parent_type: "Accounts"` + `parent_id` (calls, meetings, tasks, notes).
- Activities lists planned calls and meetings and tasks that aren't Completed or Deferred. History lists held / not held calls and meetings, completed / deferred tasks, and notes.

### Edit view

| Method | Path | Notes |
|---|---|---|
| GET | `/api/meta/{module}` | Field types, required flags, max lengths and dropdown options (Type, Industry, ...) for building the form |
| PUT | `/api/{module}/{id}` | Save |
| POST | `/api/{module}` | Create |

Email addresses are sent as a list, and the primary one is listed first in responses:

```json
"email_addresses": [
  {"email_address": "info@acme.example", "primary_address": true},
  {"email_address": "billing@acme.example"}
]
```

"Copy address from billing" is handled in the frontend: copy the `billing_address_*` values into `shipping_address_*` before saving.

Accounts also have these customer fields, used by the CRMPro frontend: `contact_name`, `contact_title`, `phone_mobile`, `status` (sales status, in pipeline order: Lead, Follow-up, Test Drive, Quotation, Follow-up 2, Won), `lead_source` and `tags` (comma-separated). Account records also include a read-only `last_contacted`: the date of the latest held call or meeting, or email, linked to the account (`null` if there is none).

### Quotations and outbox

A quotation is created and emailed automatically when an opportunity enters the **Proposal/Price Quote** sales stage: when it is created in that stage, or moved into it by `PUT`, mass update or merge. Saving it again while it stays in that stage sends nothing. The quotation has one line (the opportunity's name and amount), is valid for 30 days and goes to the primary email address of the opportunity's account.

If the opportunity has no account or no amount, no quotation is created. If the account has no email address, the quotation is created with status `Not sent`. In each case the assigned user gets an alert explaining why.

| Method | Path | Notes |
|---|---|---|
| GET | `/api/accounts/{id}/quotes` | The account's quotations, newest first, with `items` and the `emails` sent for each |
| POST | `/api/accounts/{id}/quotes` | Create a quotation for the account and email it. Either `{"opportunity_id": "..."}` (one line: the deal's name and amount; the deal must belong to the account and have an amount) or `{"items": [{"name": "...", "quantity": 1, "unit_price": 100}]}`. Optional `"discount": {"type": "percent" \| "amount", "value": 10, "label": "..."}` and `"send": false` to save it without emailing. Returns 201 with the quotation |
| GET | `/api/quotes/{id}` | One quotation. `status` is `Draft`, `Sent` or `Not sent` (see `send_error`) |
| POST | `/api/quotes/{id}/discount` | `{"type": "percent" \| "amount", "value": 10, "label": "Autumn promo", "send": true}`. A value of 0 removes the discount. `send` (default true) emails the updated quotation |
| POST | `/api/quotes/{id}/send` | Email the quotation again (`{}`) |
| GET | `/api/outbox` | Emails sent by the backend, newest first. Query: `page`, `per_page`, `quote_id`, `parent_id` |
| GET | `/api/outbox/{id}` | One email with its plain-text body (`description`) |

All email goes through `Mailer::send()` in `src/Mailer.php`. The default transport, `outbox`, only saves the email (status `outbox`); nothing leaves the server. To deliver real email, add a transport in `Mailer::deliver()` and set `mail.transport` in `config.php`. Sent emails also appear in the account's History subpanel.

## Files

```
public/index.php      routes, grouped by screen
public/.htaccess      Apache rewrite
src/Modules.php       field definitions per module (like SuiteCRM vardefs)
src/Records.php       list / get / create / update / delete for every module
src/Subpanels.php     subpanels, Select and unlink
src/Bulk.php          mass delete, mass update, export, import
src/Merge.php         merge
src/Dashlets.php      home dashlets
src/Quotes.php        quotations: auto-send, discount, resend
src/Mailer.php        outgoing email and the outbox
src/...               auth, users, emails, favorites, recently viewed, alerts, search, preferences, target lists
sql/schema.sql        tables
sql/migrations/       changes for databases created from an earlier schema.sql
scripts/seed.php      users and wireframe sample data
```

To add a field: add a column in `sql/schema.sql` and an entry in the module's `fields` in `src/Modules.php`. Validation, list filters, sorting, export and the meta endpoint all read from there.

## Not included

- Compose email, archive email, note attachments, Documents and "Forgot password?" (these need email sending or file uploads)
- Real email delivery: quotation emails are saved to the outbox until a transport is added in `src/Mailer.php`
- Roles and permissions: every logged-in user can view and edit every record
