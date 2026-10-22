<?php
// Which customer details, tabs and subpanels each customer Type and Stage shows.
// Edit GROUP_RULES to change what a Type or Stage shows.
//
// Type  (account_type):   Individual (B2C), Business (B2B), Enterprise
// Stage (customer_stage): Lead (potential customer), Existing
//
// A group listed in GROUP_RULES shows only for the kinds in 'kinds' and the stages in 'stages'.
// A group with no 'kinds' list shows for every Type; with no 'stages' list, for every Stage.
// A customer with no Type (or Stage) set shows the group as far as that field is concerned.
// Groups not listed here always show. A group that is hidden is left unchanged when the form is saved.
//
// Groups:
//   contact_person  contact name and job title (also in the page header)
//   website         website
//   fax             fax
//   industry        industry                      } Company tab on the detail page
//   company_size    annual revenue and employees  } (the tab shows when one of them shows)
//   member_of       member of (parent account)    }
//   contacts        Contacts subpanel
//   status          customer status (New, Contacted, Qualified, Proposal, Won)  } Lead details tab
//   lead_source     lead source                                                  } (the tab shows when
//   campaign        campaign                                                     }  one of them shows)
//   leads           Leads subpanel
//   purchases       Purchases tab (won deals: count, total, first and last purchase)
//   cases           Cases subpanel
//
// Always shown: email, office phone, mobile, billing and shipping address, description, assigned to,
// tags, the quotation panel, and the Overview, More information, Activity, Deals, Tasks and Notes tabs.

const GROUP_RULES = [
    'contact_person' => ['kinds' => ['Business', 'Enterprise']],
    'website'        => ['kinds' => ['Business', 'Enterprise']],
    'fax'            => ['kinds' => ['Business', 'Enterprise']],
    'industry'       => ['kinds' => ['Business', 'Enterprise']],
    'company_size'   => ['kinds' => ['Enterprise']],
    'member_of'      => ['kinds' => ['Enterprise']],
    'contacts'       => ['kinds' => ['Business', 'Enterprise']],

    'status'         => ['stages' => ['Lead']],
    'lead_source'    => ['stages' => ['Lead']],
    'campaign'       => ['stages' => ['Lead']],
    'leads'          => ['stages' => ['Lead']],
    'purchases'      => ['stages' => ['Existing']],
    'cases'          => ['stages' => ['Existing']],
];

const KNOWN_KINDS = ['Individual', 'Business', 'Enterprise'];
const KNOWN_STAGES = ['Lead', 'Existing'];

/** Whether a customer of Type $kind and Stage $stage shows the details in $group. */
function type_shows(?string $kind, ?string $stage, string $group): bool
{
    $rule = GROUP_RULES[$group] ?? [];
    if (isset($rule['kinds']) && in_array($kind, KNOWN_KINDS, true) && !in_array($kind, $rule['kinds'], true)) {
        return false;
    }
    if (isset($rule['stages']) && in_array($stage, KNOWN_STAGES, true) && !in_array($stage, $rule['stages'], true)) {
        return false;
    }

    return true;
}

/** The same rules for app.js, which applies them when the Type or Stage changes on a form. */
function type_rules_json(): string
{
    return json_encode(['rules' => GROUP_RULES, 'kinds' => KNOWN_KINDS, 'stages' => KNOWN_STAGES]);
}
