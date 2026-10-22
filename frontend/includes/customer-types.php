<?php
// Which customer details the detail and edit pages show for each Type (account_type).
// Edit these lists to change what a type shows.
//
// Every type shows the common groups. A customer with no Type (or a type not listed)
// shows every group. A group that a type hides is left unchanged when the form is saved.
//
// Groups:
//   contact       contact name, job title, email, office phone, mobile
//   website       website
//   description   description
//   assigned      assigned to
//   tags          tags
//   fax           fax
//   billing       billing address
//   shipping      shipping address
//   status        customer status (New, Contacted, Qualified, Proposal, Won)
//   lead_source   lead source
//   campaign      campaign
//   industry      industry
//   company_size  annual revenue and employees
//   member_of     member of (parent account)
//   quotes        quotation panel and Quotations subpanel (always shown if the customer has quotations)

const COMMON_GROUPS = ['contact', 'website', 'description', 'assigned', 'tags'];

const TYPE_GROUPS = [
    'Customer'   => ['billing', 'shipping', 'fax', 'status', 'lead_source', 'industry', 'company_size', 'member_of', 'quotes'],
    'Prospect'   => ['billing', 'status', 'lead_source', 'campaign', 'industry', 'company_size', 'quotes'],
    'Reseller'   => ['billing', 'shipping', 'fax', 'industry', 'member_of', 'quotes'],
    'Partner'    => ['billing', 'fax', 'industry', 'member_of', 'campaign'],
    'Integrator' => ['billing', 'industry', 'member_of'],
    'Investor'   => ['billing', 'industry', 'company_size'],
    'Analyst'    => ['industry'],
    'Press'      => [],
    'Competitor' => ['industry', 'company_size'],
    'Other'      => ['billing'],
];

/** Whether a customer of $type shows the details in $group. */
function type_shows(?string $type, string $group): bool
{
    if ($type === null || $type === '' || !isset(TYPE_GROUPS[$type]) || in_array($group, COMMON_GROUPS, true)) {
        return true;
    }

    return in_array($group, TYPE_GROUPS[$type], true);
}

/** The same rules for app.js, which applies them when the Type changes on the edit page. */
function type_rules_json(): string
{
    return json_encode(['common' => COMMON_GROUPS, 'types' => TYPE_GROUPS]);
}
