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
2. Copy `config.example.php` to `config.php` and fill in the database details.
3. Add the users and the sample data shown in the wireframe:
   ```
   php scripts/seed.php            # users admin, sally, max with password "changeme"
   php scripts/seed.php mypassword # or choose the password
   ```
4. Run it:
   - PHP's built-in server: `php -S localhost:8000 -t public public/index.php`
   - Apache (e.g. XAMPP): point the site, or a subfolder, at `public/`. `public/.htaccess` sends requests to `index.php` and needs `mod_rewrite`. The subfolder setup has not been tested.

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
src/...               auth, users, emails, favorites, recently viewed, alerts, search, preferences, target lists
sql/schema.sql        tables
scripts/seed.php      users and wireframe sample data
```

To add a field: add a column in `sql/schema.sql` and an entry in the module's `fields` in `src/Modules.php`. Validation, list filters, sorting, export and the meta endpoint all read from there.

## Not included

- Compose email, archive email, note attachments, Documents and "Forgot password?" (these need email sending or file uploads)
- Roles and permissions: every logged-in user can view and edit every record
