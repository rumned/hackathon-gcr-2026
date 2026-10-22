<?php
// Shared setup for the app pages: session, helpers, option lists and sample data.
// Later, replace the session data with calls to the API.
session_start();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fmt_date($iso) {
    return $iso === '' ? '' : date('M j, Y', strtotime($iso));
}

function initials($name) {
    $initials = '';
    foreach (explode(' ', $name) as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
    }
    return substr($initials, 0, 2);
}

// Option lists
$statuses   = ['New', 'Contacted', 'Qualified', 'Proposal', 'Won'];
$sources    = ['Website', 'Referral', 'Email campaign', 'Event', 'Cold call'];
$users      = ['Alex Carter', 'Sally', 'Max'];
$industries = ['Technology', 'Retail', 'Finance', 'Healthcare', 'Education', 'Transportation', 'Other'];
$types      = ['Customer', 'Prospect', 'Partner', 'Other'];

// Every field a customer record has: the original Customers fields plus the wireframe's Account fields.
function blank_customer() {
    return [
        'id' => 0,
        // From the original Customers pages
        'name' => '', 'company' => '', 'title' => '', 'email' => '', 'phone' => '', 'mobile' => '',
        'status' => 'New', 'source' => '', 'tags' => [], 'last_contact' => '',
        // From the wireframe's Account pages
        'website' => '', 'fax' => '', 'description' => '',
        'billing_street' => '', 'billing_city' => '', 'billing_state' => '', 'billing_postal' => '', 'billing_country' => '',
        'ship_same' => true,
        'shipping_street' => '', 'shipping_city' => '', 'shipping_state' => '', 'shipping_postal' => '', 'shipping_country' => '',
        'type' => '', 'industry' => '', 'revenue' => '', 'employees' => '', 'member_of' => '', 'campaign' => '',
        // Shared by both
        'assigned' => '', 'created' => '', 'modified' => '',
    ];
}

function seed_customers() {
    $rows = [
        ['id'=>1, 'name'=>'Sarah Johnson', 'company'=>'Acme Corp', 'email'=>'sarah@acme.com', 'phone'=>'+1 234 567 890', 'status'=>'New',
         'title'=>'Senior Marketing Manager', 'mobile'=>'+1 234 567 891', 'website'=>'www.acme.com',
         'billing_street'=>'123 Business Ave', 'billing_city'=>'New York', 'billing_state'=>'NY', 'billing_postal'=>'10001', 'billing_country'=>'USA',
         'description'=>'Marketing lead for the Acme CRM rollout.', 'source'=>'Website', 'type'=>'Customer', 'industry'=>'Technology',
         'revenue'=>'$4.2M', 'employees'=>'120', 'assigned'=>'Alex Carter',
         'created'=>'2024-03-15', 'modified'=>'2024-03-20', 'last_contact'=>'2024-03-20', 'tags'=>['Enterprise', 'High Value']],
        ['id'=>2, 'name'=>'Michael Chen', 'company'=>'TechFlow', 'email'=>'michael@techflow.com', 'phone'=>'+1 234 567 891', 'status'=>'Contacted',
         'title'=>'CTO', 'website'=>'www.techflow.com', 'billing_city'=>'San Francisco', 'billing_state'=>'CA', 'billing_country'=>'USA',
         'source'=>'Referral', 'type'=>'Prospect', 'industry'=>'Technology', 'assigned'=>'Sally',
         'created'=>'2024-03-12', 'modified'=>'2024-03-18', 'last_contact'=>'2024-03-18'],
        ['id'=>3, 'name'=>'Emily Davis', 'company'=>'BrightPath', 'email'=>'emily@brightpath.com', 'phone'=>'+1 234 567 892', 'status'=>'Qualified',
         'title'=>'Head of Operations', 'website'=>'www.brightpath.com', 'billing_city'=>'Boston', 'billing_state'=>'MA', 'billing_country'=>'USA',
         'source'=>'Event', 'type'=>'Prospect', 'industry'=>'Education', 'assigned'=>'Max',
         'created'=>'2024-03-09', 'modified'=>'2024-03-14', 'last_contact'=>'2024-03-14'],
        ['id'=>4, 'name'=>'James Wilson', 'company'=>'NextGen', 'email'=>'james@nextgen.com', 'phone'=>'+1 234 567 893', 'status'=>'Proposal',
         'title'=>'Procurement Lead', 'website'=>'www.nextgen.com', 'billing_city'=>'Austin', 'billing_state'=>'TX', 'billing_country'=>'USA',
         'source'=>'Email campaign', 'type'=>'Prospect', 'industry'=>'Retail', 'assigned'=>'Alex Carter',
         'created'=>'2024-03-05', 'modified'=>'2024-03-19', 'last_contact'=>'2024-03-19'],
        ['id'=>5, 'name'=>'Olivia Martinez', 'company'=>'CloudSync', 'email'=>'olivia@cloudsync.com', 'phone'=>'+1 234 567 894', 'status'=>'Won',
         'title'=>'VP Engineering', 'website'=>'www.cloudsync.com', 'billing_city'=>'Seattle', 'billing_state'=>'WA', 'billing_country'=>'USA',
         'source'=>'Website', 'type'=>'Customer', 'industry'=>'Technology', 'assigned'=>'Sally',
         'created'=>'2024-02-28', 'modified'=>'2024-03-11', 'last_contact'=>'2024-03-11'],
        ['id'=>6, 'name'=>'Daniel Kim', 'company'=>'DataHub', 'email'=>'daniel@datahub.com', 'phone'=>'+1 234 567 895', 'status'=>'Contacted',
         'title'=>'Data Manager', 'website'=>'www.datahub.com', 'billing_city'=>'Toronto', 'billing_state'=>'ON', 'billing_country'=>'Canada',
         'source'=>'Cold call', 'type'=>'Prospect', 'industry'=>'Finance', 'assigned'=>'Max',
         'created'=>'2024-02-21', 'modified'=>'2024-03-02', 'last_contact'=>'2024-03-02'],
        ['id'=>7, 'name'=>'Sophia Lee', 'company'=>'InnoTech', 'email'=>'sophia@innotech.com', 'phone'=>'+1 234 567 896', 'status'=>'New',
         'title'=>'Product Director', 'website'=>'www.innotech.com', 'billing_city'=>'London', 'billing_country'=>'United Kingdom',
         'source'=>'Website', 'type'=>'Prospect', 'industry'=>'Healthcare', 'assigned'=>'Alex Carter',
         'created'=>'2024-02-17', 'modified'=>'2024-02-17', 'last_contact'=>''],
        ['id'=>8, 'name'=>'Liam Brown', 'company'=>'FutureSoft', 'email'=>'liam@futuresoft.com', 'phone'=>'+1 234 567 897', 'status'=>'Qualified',
         'title'=>'IT Manager', 'website'=>'www.futuresoft.com', 'billing_city'=>'Dublin', 'billing_country'=>'Ireland',
         'source'=>'Referral', 'type'=>'Partner', 'industry'=>'Technology', 'assigned'=>'Sally',
         'created'=>'2024-02-09', 'modified'=>'2024-02-26', 'last_contact'=>'2024-02-26'],
    ];
    return array_map(fn($row) => $row + blank_customer(), $rows);
}

if (!isset($_SESSION['customers'])) {
    $_SESSION['customers'] = seed_customers();
}

// Fill in any missing fields, e.g. for records saved by the old list page.
function customers() {
    return array_map(fn($c) => $c + blank_customer(), $_SESSION['customers']);
}

function find_customer($id) {
    foreach (customers() as $c) {
        if ($c['id'] === $id) return $c;
    }
    return null;
}

// Address lines for the detail page, skipping empty parts.
function address_lines(array $c, $prefix) {
    $cityLine = trim($c[$prefix . '_city'] . ($c[$prefix . '_state'] !== '' ? ', ' . $c[$prefix . '_state'] : '') . ' ' . $c[$prefix . '_postal']);
    return array_values(array_filter([$c[$prefix . '_street'], $cityLine, $c[$prefix . '_country']], fn($line) => $line !== ''));
}
