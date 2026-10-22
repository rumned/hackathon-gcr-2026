<?php

declare(strict_types=1);

/*
 * Creates three users and the sample records shown in the wireframe.
 * Usage: php scripts/seed.php [password]     (default password: changeme)
 */

use App\Auth;
use App\Db;
use App\Records;
use App\Util;

require dirname(__DIR__) . '/src/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line.');
}
if ((int) Db::value('SELECT COUNT(*) FROM users') > 0) {
    fwrite(STDERR, "The users table already has rows; seed data was not added.\n");
    exit(1);
}

$password = $argv[1] ?? 'changeme';
$now = Util::now();

// ------------------------------------------------------------ users
$users = [];
foreach ([
    ['admin', '', 'Administrator', 1],
    ['sally', 'Sally', 'Bronsen', 0],
    ['max', 'Max', 'Jensen', 0],
] as [$userName, $first, $last, $isAdmin]) {
    $users[$userName] = Util::uuid();
    Db::insert('users', [
        'id' => $users[$userName],
        'user_name' => $userName,
        'user_hash' => password_hash($password, PASSWORD_DEFAULT),
        'first_name' => $first,
        'last_name' => $last,
        'is_admin' => $isAdmin,
        'status' => 'Active',
        'date_entered' => $now,
        'date_modified' => $now,
    ]);
}
Auth::actAs($users['admin']);

/** Sets date_entered / date_modified to the dates shown in the wireframe. */
$setDates = static function (string $table, string $id, string $entered, ?string $modified = null): void {
    Db::run("UPDATE `$table` SET date_entered = ?, date_modified = ? WHERE id = ?", [$entered, $modified ?? $entered, $id]);
};

// ------------------------------------------------------------ accounts (list view rows)
$accounts = [];
$createdDates = [];
foreach ([
    ['Acme Logistics', 'London', 'United Kingdom', '+44 20 0000 0001', 'admin', 'info@acme.example', '2026-10-14'],
    ['Northwind Traders', 'Seattle', 'United States', '+1 206 000 0002', 'sally', 'sales@northwind.example', '2026-10-12'],
    ['Bluefin Marine', 'Singapore', 'Singapore', '+65 0000 0003', 'admin', 'hello@bluefin.example', '2026-10-09'],
    ['Kestrel Foods', 'Berlin', 'Germany', '+49 30 0000 0004', 'max', 'contact@kestrel.example', '2026-10-05'],
    ['Granite Systems', 'Austin', 'United States', '+1 512 000 0005', 'sally', 'info@granite.example', '2026-09-30'],
    ['Orchid Health', 'Sydney', 'Australia', '+61 2 0000 0006', 'admin', 'team@orchid.example', '2026-09-28'],
    ['Pioneer Print', 'Manchester', 'United Kingdom', '+44 161 000 0007', 'max', 'print@pioneer.example', '2026-09-21'],
    ['Summit Outdoor', 'Denver', 'United States', '+1 303 000 0008', 'admin', 'shop@summit.example', '2026-09-17'],
    ['Tidewater Energy', 'Rotterdam', 'Netherlands', '+31 10 000 0009', 'sally', 'info@tidewater.example', '2026-09-11'],
    ['Willow Interiors', 'Dublin', 'Ireland', '+353 1 000 0010', 'max', 'studio@willow.example', '2026-09-02'],
    ['Daniel Reyes', 'Kuala Lumpur', 'Malaysia', '+60 3 0000 0011', 'sally', 'daniel.reyes@mail.example', '2026-08-28'],
] as [$name, $city, $country, $phone, $user, $email, $created]) {
    $record = Records::create('accounts', [
        'name' => $name,
        'billing_address_city' => $city,
        'billing_address_country' => $country,
        'phone_office' => $phone,
        'assigned_user_id' => $users[$user],
        'email_addresses' => [$email],
    ]);
    $accounts[$name] = $record['id'];
    $createdDates[$name] = "$created 09:00:00";
    $setDates('accounts', $record['id'], "$created 09:00:00");
}

// Detail view fields of Acme Logistics
Records::update('accounts', $accounts['Acme Logistics'], [
    'website' => 'https://acme.example',
    'billing_address_street' => '1 Example Street',
    'billing_address_postalcode' => 'EC1A 1AA',
    'shipping_address_street' => '1 Example Street',
    'shipping_address_city' => 'London',
    'shipping_address_postalcode' => 'EC1A 1AA',
    'shipping_address_country' => 'United Kingdom',
    'description' => 'Freight and warehousing client.',
    'account_type' => 'Enterprise',
    'industry' => 'Transportation',
    'annual_revenue' => '$4.2M',
    'employees' => '120',
]);

// Customer kind (account_type), stage, contact person, status and tags (the frontend shows details by kind and stage)
foreach ([
    'Acme Logistics' => ['account_type' => 'Enterprise', 'customer_stage' => 'Existing', 'contact_name' => 'Jane Smith', 'contact_title' => 'Operations manager', 'status' => 'Won', 'lead_source' => 'Web Site', 'tags' => 'Enterprise, High Value'],
    'Northwind Traders' => ['account_type' => 'Business', 'customer_stage' => 'Existing', 'contact_name' => 'Laura Chen', 'contact_title' => 'Purchasing lead', 'status' => 'Won', 'lead_source' => 'Existing Customer'],
    'Bluefin Marine' => ['account_type' => 'Business', 'customer_stage' => 'Existing', 'contact_name' => 'Wei Ling Tan', 'contact_title' => 'Partnerships manager'],
    'Kestrel Foods' => ['account_type' => 'Business', 'customer_stage' => 'Lead', 'contact_name' => 'Jonas Weber', 'contact_title' => 'Head of logistics', 'status' => 'Contacted', 'lead_source' => 'Cold Call'],
    'Granite Systems' => ['account_type' => 'Business', 'customer_stage' => 'Lead', 'contact_name' => 'Maria Lopez', 'contact_title' => 'IT director', 'status' => 'Qualified', 'lead_source' => 'Trade Show'],
    'Orchid Health' => ['account_type' => 'Business', 'customer_stage' => 'Existing', 'status' => 'Won', 'lead_source' => 'Partner'],
    'Pioneer Print' => ['account_type' => 'Business', 'customer_stage' => 'Existing', 'contact_name' => 'Tom Hughes'],
    'Summit Outdoor' => ['account_type' => 'Business', 'customer_stage' => 'Existing', 'status' => 'Won'],
    'Tidewater Energy' => ['account_type' => 'Enterprise', 'customer_stage' => 'Lead'],
    'Daniel Reyes' => ['account_type' => 'Individual', 'customer_stage' => 'Existing', 'phone_mobile' => '+60 12 000 0011', 'status' => 'Won', 'lead_source' => 'Web Site'],
] as $name => $fields) {
    Records::update('accounts', $accounts[$name], $fields);
    $setDates('accounts', $accounts[$name], $createdDates[$name]);
}
$setDates('accounts', $accounts['Acme Logistics'], '2026-10-14 09:00:00', '2026-10-20 09:00:00');
Db::run('DELETE FROM accounts_audit');   // seeding edits are not real changes

// ------------------------------------------------------------ contacts (Acme subpanel)
foreach ([
    ['Jane', 'Smith', 'Operations manager', 'jane@acme.example', '+44 20 0000 0011'],
    ['Omar', 'Haddad', 'Finance director', 'omar@acme.example', '+44 20 0000 0012'],
] as [$first, $last, $title, $email, $phone]) {
    Records::create('contacts', [
        'first_name' => $first,
        'last_name' => $last,
        'title' => $title,
        'phone_work' => $phone,
        'account_id' => $accounts['Acme Logistics'],
        'email_addresses' => [$email],
    ]);
}

// ------------------------------------------------------------ opportunities
// Fleet tracking is created in the Proposal/Price Quote stage, so its quotation
// is created and sent automatically (it appears in the outbox).
Records::create('opportunities', [
    'name' => 'Q4 renewal',
    'account_id' => $accounts['Acme Logistics'],
    'sales_stage' => 'Negotiation/Review',
    'date_closed' => '2026-12-15',
    'amount' => 24000,
]);
Records::create('opportunities', [
    'name' => 'Fleet tracking',
    'account_id' => $accounts['Granite Systems'],
    'sales_stage' => 'Proposal/Price Quote',
    'date_closed' => '2026-11-30',
    'amount' => 11500,
]);
// Won deals: the purchase history of Northwind Traders and Daniel Reyes
foreach ([
    ['Pallet racking', 'Northwind Traders', '2026-03-20', 8200],
    ['Warehouse scanners', 'Northwind Traders', '2026-07-08', 5400],
    ['Home office desk', 'Daniel Reyes', '2026-05-14', 950],
    ['Ergonomic chair', 'Daniel Reyes', '2026-09-03', 420],
] as [$dealName, $account, $closed, $amount]) {
    Records::create('opportunities', [
        'name' => $dealName,
        'account_id' => $accounts[$account],
        'sales_stage' => 'Closed Won',
        'date_closed' => $closed,
        'amount' => $amount,
    ]);
}

// ------------------------------------------------------------ calls, meetings, tasks
$activity = static fn (string $account): array => ['parent_type' => 'Accounts', 'parent_id' => $accounts[$account]];

Records::create('calls', ['name' => 'Contract follow-up', 'date_start' => '2026-10-23 10:00', 'duration_minutes' => 30, 'direction' => 'Outbound'] + $activity('Acme Logistics'));
Records::create('calls', ['name' => 'Intro call', 'date_start' => '2026-10-24 14:30', 'duration_minutes' => 30, 'direction' => 'Outbound'] + $activity('Kestrel Foods'));
Records::create('meetings', ['name' => 'Quarterly review', 'date_start' => '2026-10-27 09:00', 'duration_hours' => 1] + $activity('Northwind Traders'));
Records::create('meetings', ['name' => 'Product demo', 'date_start' => '2026-10-29 15:00', 'duration_hours' => 1] + $activity('Bluefin Marine'));
$kickOff = Records::create('meetings', ['name' => 'Kick-off meeting', 'date_start' => '2026-10-15 10:00', 'duration_hours' => 1, 'status' => 'Held'] + $activity('Acme Logistics'));
$setDates('meetings', $kickOff['id'], '2026-10-14 12:00:00', '2026-10-15 11:00:00');
Records::create('tasks', ['name' => 'Send pricing sheet', 'date_due' => '2026-10-25 17:00', 'priority' => 'Medium'] + $activity('Acme Logistics'));

// ------------------------------------------------------------ leads
Records::create('leads', ['first_name' => 'Tom', 'last_name' => 'Becker', 'status' => 'New', 'lead_source' => 'Web Site']);
Records::create('leads', ['first_name' => 'Aisha', 'last_name' => 'Rahman', 'status' => 'Assigned', 'lead_source' => 'Trade Show']);

echo "Seed data added. Users: admin, sally, max (password: $password)\n";
