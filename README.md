# Residio

**One place to run a residential community.** Residio is a multi-tenant society and apartment management application with a staff workspace, a private resident portal, and a REST API.

It brings day-to-day community operations together: manage buildings and residents, handle visitor entry, publish notices, collect maintenance, coordinate repairs, manage facilities, and keep residents informed.

---

## At a glance

- **Society workspace** for administrators, committee members, accountants, guards, and maintenance staff.
- **Resident portal** for personal bills and bill documents, notices, delivered broadcasts, and conversations with management.
- **Building group chats** for residents in the same tower or building.
- **Multi-tenant access controls** to keep society records separated and restrict resident information to authorized users.
- **Sanctum API** at `/api/v1` for authenticated API clients.
- **Demo data** for trying the application locally.

## Portals and access

### Society workspace

Society staff sign in at `/login`. What each person can do depends on their role and permissions. The seeded roles include Society Admin, Sub Admin, Accountant, Security Guard, Maintenance Staff, Resident, Tenant, Family Member, and Vendor.

The Society Admin can manage society settings, including the resident invite code, and administer the society's operations and people.

### Resident portal

Residents sign in at `/resident-login` and open their portal at `/resident-portal`. The portal provides access to the signed-in resident's own bills and bill documents, relevant notices, delivered broadcasts, and conversations they participate in. Residents can contact management through portal messages.

The application applies authorization and tenant scoping on the server as well as in the interface. Residents should only be able to access records they are authorized to see; resident data is not intended to be a shared society-wide view.

New residents can create an account at `/resident-register`. Registration requires the society's invite code and a valid building/flat selection. A society administrator can find the invite code in **Settings → General Settings**.

### Platform administration

The Super Admin workspace manages the platform side of the application, including societies, subscription plans, subscriptions, platform analytics, CMS pages, blog content, and contact inquiries.

## Features

### Society and people

- Society profile and settings
- Tower/building, floor, and flat/unit management
- Parking slot management
- Society documents
- Resident, tenant, family-member, and staff records
- Resident directory, emergency contacts, and vehicles
- Role-based permissions for society teams

### Daily operations

- Visitor passes, approvals, check-in/check-out, and gate workflow
- Complaints with categories, assignment, status tracking, and SLA support
- Facility listings and bookings, including image URLs or uploaded images
- Notices, announcements, events, and polls
- Community assets and maintenance schedules
- Tower/building group conversations for residents in that building

### Finance and administration

- Maintenance heads, bill generation, invoices, receipts, collections, and due tracking
- Accounting ledgers, journal entries, and financial reports
- Vendor records, work orders, payments, and ratings
- Staff attendance, shifts, leave, and payroll
- Helpdesk tickets, replies, assignment, and escalation
- Society reports for operational and financial activity

### Communication

- Management-to-resident conversations
- Broadcasts and reusable communication templates
- Resident portal messages
- Building-specific group chats, with membership tied to the relevant building
- Notices and broadcasts surfaced in the resident portal according to their audience

### Platform controls

- Multi-society administration
- Subscription plans and feature availability
- Role and permission management
- Two-factor authentication
- Audit and login history
- Tenant-scoped web and API access

Some modules are gated by the society's subscription plan. Available modules and actions can therefore vary by role and plan.

## Technology stack

| Area | Technologies |
| --- | --- |
| Application | PHP 8.2+, Laravel 12 |
| Database | SQLite for local development; MySQL/MariaDB supported |
| Web interface | Laravel Blade, Bootstrap 5, Bootstrap Icons |
| Front-end behavior | JavaScript, jQuery, DataTables, Chart.js |
| API authentication | Laravel Sanctum |
| Queues and caching | Laravel queues and cache; database drivers for simple local use, Redis supported |
| File storage | Laravel filesystem (local storage or S3-compatible storage) |
| Asset tooling | Vite, Tailwind CSS packages, npm (for the `resources/` assets) |
| Testing | PHPUnit and Laravel feature tests |

The web layout currently loads Bootstrap, icons, DataTables, Chart.js, and jQuery from CDNs. The existing application styles and scripts are also served from `public/css` and `public/js`.

## Get started locally

### Requirements

- PHP **8.2 or newer**
- Composer
- SQLite with the PHP `pdo_sqlite` extension, or MySQL/MariaDB with the matching PHP PDO extension
- Node.js and npm are optional for running the existing Blade UI; they are used when building the Vite-managed `resources/` assets

### 1. Get the code and install PHP dependencies

```bash
git clone https://github.com/codedbydollys10/Residio.git
cd Residio
composer install
```

### 2. Create your local environment file

This repository intentionally does not include an `.env.example`. Create a new `.env` file in the project root and add the following local SQLite configuration:

```dotenv
APP_NAME=Residio
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

Do not commit `.env`; it contains machine-specific configuration and secrets.

### 3. Create the SQLite file, generate the app key, and prepare the database

**PowerShell (Windows):**

```powershell
if (-not (Test-Path database\database.sqlite)) {
    New-Item -ItemType File -Path database\database.sqlite | Out-Null
}
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

**macOS/Linux:**

```bash
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

The seed command creates platform roles and plans, a Green Valley demo society, sample accounts, and module demo data. To initialize an empty database without demo records, use `php artisan migrate` instead.

### 4. Start the application

```bash
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

### 5. Sign in with demo accounts

The demo seeders create these accounts with the same local-only password, **`Password@123`**:

| Role | Email | Sign-in |
| --- | --- | --- |
| Society Admin | `admin@greenvalley.test` | `/login` |
| Accountant | `accountant@greenvalley.test` | `/login` |
| Security Guard | `guard@greenvalley.test` | `/login` |
| Maintenance Staff | `maintenance@greenvalley.test` | `/login` |
| Resident | `resident@greenvalley.test` | `/resident-login` |

These are development/demo credentials only. Change or remove them before deploying an instance with real data. The demo resident account is separate from the self-registration flow.

A platform Super Admin account is seeded as well; its configured email is defined in [`SuperAdminSeeder.php`](./database/seeders/SuperAdminSeeder.php).

### Optional: use MySQL or MariaDB

Create an empty database, then change the database settings in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=residio
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Then run:

```bash
php artisan migrate --seed
```

Keep database credentials in your local or deployment environment; never add real credentials to source control.

### Optional: build Vite-managed assets

The standard Blade interface uses assets in `public/` and CDN-loaded libraries. If you are changing the Vite-managed files under `resources/`, install Node dependencies and build them:

```bash
npm install
npm run build
```

For live asset development, run `npm run dev` in another terminal.

## Background jobs and scheduled tasks

For local development, the seeded notification and queued work can be processed in another terminal:

```bash
php artisan queue:work
```

The configured local `QUEUE_CONNECTION=database` stores queued jobs in the application database. For production, run a supervised queue worker and configure Laravel's scheduler to execute `php artisan schedule:run` every minute. Configure mail, storage, cache, and queue services for the environment you deploy to.

## API

The versioned API base path is `/api/v1`. It uses Laravel Sanctum bearer tokens for API clients. Authenticate at `POST /api/v1/auth/login`, then use the returned token for protected requests. API routes cover core operations such as residents, complaints, visitors, facilities, notices, maintenance, accounting, vendors, assets, helpdesk, communication, and reports; authorization and feature access are enforced by the application.

## Tests

Run the test suite with:

```bash
php artisan test
```

Or use Composer:

```bash
composer test
```

The PHPUnit configuration uses an in-memory SQLite database for tests. SQLite's PDO extension must be enabled in PHP.

## Project structure

```text
app/
  Http/Controllers/      Web, API, authentication, and platform controllers
  Http/Middleware/       Tenant, subscription, feature, and permission checks
  Http/Requests/         Request validation and authorization
  Models/                Eloquent models and tenant-scoping concerns
  Policies/              Record-level authorization
  Services/              Business workflows and application logic
  Repositories/          Persistence contracts and implementations
  Events/ Listeners/     Domain events and their handlers
  Jobs/ Notifications/   Background work and notifications
  Support/Tenancy/       Current-tenant resolution
bootstrap/               Laravel bootstrap and middleware configuration
config/                  Application, roles, tenancy, and service settings
database/
  migrations/            Database schema
  seeders/               Platform and demo data seeders
  factories/              Test and model factories
public/
  css/ js/ images/        Public styles, scripts, and images
resources/
  views/                  Blade pages and shared layouts
  css/ js/                Vite-managed source assets
routes/
  web/                    Society and platform web route modules
  api/                    Versioned API route modules
tests/
  Feature/ Unit/          Application and unit tests
```

Web and API feature routes are organized by module in `routes/web/` and `routes/api/`. The route loaders in `routes/web.php` and `routes/api.php` include these module files automatically.

## Deployment notes

For a production deployment:

1. Set `APP_ENV=production`, `APP_DEBUG=false`, and the correct `APP_URL`.
2. Use a production database and unique, securely managed credentials.
3. Run `composer install --no-dev --optimize-autoloader`.
4. Generate and protect `APP_KEY`; do not regenerate it for an existing installation.
5. Run `php artisan migrate --force`.
6. Configure durable storage, mail delivery, queues, and scheduled tasks as required.
7. Point the web server document root to `public/` and enable HTTPS.
8. Never seed demo accounts into a production database.
