<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * Module definitions, playing the role of SuiteCRM 7's vardefs.
 * Records.php reads these to validate input, build list queries and format output,
 * so adding a field here (and a column in schema.sql) is enough to support it.
 *
 * Field types
 *   varchar (len), text, int, decimal, date, datetime
 *   enum         one of 'options' (stored value => label); dropdown values match SuiteCRM 7
 *   user         a user id (assigned_user_id); output adds assigned_user_name
 *   relate       id of a record in 'module', stored in this table; output adds <field>_name
 *   parent_type  module label of the parent record (Accounts, Contacts, ...)
 *   parent_id    id of the parent record, checked against parent_type; output adds parent_name
 *   link         not a column: the account on the other side of a relationship table
 *                (a contact's or opportunity's account); output adds account_name
 * Other keys: required, default, readonly (returned but not accepted as input),
 * name_key (output key for the related record's name).
 *
 * Module keys: table, label (module name used in parent_type and other tables),
 * singular, person (name is first_name + last_name), emails (has email addresses),
 * audited (changes go to <table>_audit), search_fields, global_search,
 * duplicate_rules (field groups that must all match for "Find duplicates"), subpanels.
 */
final class Modules
{
    public const CLOSED_SALES_STAGES = ['Closed Won', 'Closed Lost'];
    public const TARGET_LIST_MEMBERS = ['accounts', 'contacts', 'leads'];

    private static ?array $modules = null;

    /** Relationship tables. Each right-hand record has at most one left-hand record. */
    private const RELATIONSHIPS = [
        'accounts_contacts' => [
            'table' => 'accounts_contacts',
            'lhs_module' => 'accounts', 'lhs_key' => 'account_id',
            'rhs_module' => 'contacts', 'rhs_key' => 'contact_id',
        ],
        'accounts_opportunities' => [
            'table' => 'accounts_opportunities',
            'lhs_module' => 'accounts', 'lhs_key' => 'account_id',
            'rhs_module' => 'opportunities', 'rhs_key' => 'opportunity_id',
        ],
    ];

    public static function all(): array
    {
        return self::$modules ??= self::define();
    }

    public static function get(string $key): array
    {
        $modules = self::all();
        if (!isset($modules[$key])) {
            throw new HttpError(404, "Unknown module: $key");
        }

        return $modules[$key] + ['key' => $key];
    }

    /** 'Accounts' => 'accounts' */
    public static function keyForLabel(?string $label): ?string
    {
        foreach (self::all() as $key => $module) {
            if ($module['label'] === $label) {
                return $key;
            }
        }

        return null;
    }

    public static function relationship(string $name): array
    {
        return self::RELATIONSHIPS[$name] + ['name' => $name];
    }

    public static function relationships(): array
    {
        $out = [];
        foreach (array_keys(self::RELATIONSHIPS) as $name) {
            $out[$name] = self::relationship($name);
        }

        return $out;
    }

    /** SQL for a record's display name, with the table aliased as t. */
    public static function nameSql(array $module): string
    {
        return !empty($module['person']) ? "CONCAT_WS(' ', t.first_name, t.last_name)" : 't.name';
    }

    /** Field definitions for the frontend's edit forms (GET /api/meta/{module}). */
    public static function describe(string $key): array
    {
        $module = self::get($key);
        $fields = [];
        foreach ($module['fields'] as $name => $spec) {
            $field = ['type' => $spec['type'], 'required' => !empty($spec['required']), 'readonly' => !empty($spec['readonly'])];
            foreach (['len', 'module', 'default'] as $extra) {
                if (isset($spec[$extra])) {
                    $field[$extra] = $spec[$extra];
                }
            }
            if ($spec['type'] === 'link') {
                $field['module'] = self::relationship($spec['relationship'])['lhs_module'];
            }
            if (isset($spec['options'])) {
                $field['options'] = array_map(
                    static fn ($value, $label): array => ['value' => (string) $value, 'label' => $label],
                    array_keys($spec['options']),
                    $spec['options'],
                );
            }
            $fields[$name] = $field;
        }

        return [
            'module' => $key,
            'label' => $module['label'],
            'singular' => $module['singular'],
            'has_email_addresses' => !empty($module['emails']),
            'subpanels' => array_keys($module['subpanels'] ?? []),
            'fields' => $fields,
        ];
    }

    private static function options(array $values): array
    {
        return array_combine($values, $values);
    }

    private static function define(): array
    {
        // Dropdown values copied from SuiteCRM 7 (include/language/en_us.lang.php)
        // Customer kind and stage used by the CRMPro frontend (replace SuiteCRM's account types)
        $accountTypes = ['Individual' => 'Individual (B2C)', 'Business' => 'Business (B2B)', 'Enterprise' => 'Enterprise'];
        $customerStages = ['Lead' => 'Potential (Lead)', 'Existing' => 'Existing'];
        $industries = self::options([
            'Apparel', 'Banking', 'Biotechnology', 'Chemicals', 'Communications', 'Construction', 'Consulting',
            'Education', 'Electronics', 'Energy', 'Engineering', 'Entertainment', 'Environmental', 'Finance',
            'Government', 'Healthcare', 'Hospitality', 'Insurance', 'Machinery', 'Manufacturing', 'Media',
            'Not For Profit', 'Recreation', 'Retail', 'Shipping', 'Technology', 'Telecommunications',
            'Transportation', 'Utilities', 'Other',
        ]);
        $leadSources = self::options([
            'Cold Call', 'Existing Customer', 'Self Generated', 'Employee', 'Partner', 'Public Relations',
            'Direct Mail', 'Conference', 'Trade Show', 'Web Site', 'Word of mouth', 'Email', 'Campaign', 'Other',
        ]);
        $leadStatuses = self::options(['New', 'Assigned', 'In Process', 'Converted', 'Recycled', 'Dead']);
        // Customer status used by the CRMPro frontend
        $customerStatuses = self::options(['New', 'Contacted', 'Qualified', 'Proposal', 'Won']);
        $salesStages = self::options([
            'Prospecting', 'Qualification', 'Needs Analysis', 'Value Proposition', 'Id. Decision Makers',
            'Perception Analysis', 'Proposal/Price Quote', 'Negotiation/Review', 'Closed Won', 'Closed Lost',
        ]);
        $salesStages['Id. Decision Makers'] = 'Identifying Decision Makers';
        $activityStatuses = self::options(['Planned', 'Held', 'Not Held']);
        $taskStatuses = self::options(['Not Started', 'In Progress', 'Completed', 'Pending Input', 'Deferred']);
        $priorities = self::options(['High', 'Medium', 'Low']);
        $caseStatuses = [
            'Open_New' => 'New', 'Open_Assigned' => 'Assigned', 'Closed_Closed' => 'Closed',
            'Open_Pending Input' => 'Pending Input', 'Closed_Rejected' => 'Rejected', 'Closed_Duplicate' => 'Duplicate',
        ];
        $parentTypes = self::options(['Accounts', 'Contacts', 'Leads', 'Opportunities', 'Cases']);

        $assigned = ['assigned_user_id' => ['type' => 'user']];
        $parent = [
            'parent_type' => ['type' => 'parent_type', 'options' => $parentTypes],
            'parent_id' => ['type' => 'parent_id'],
        ];
        $address = static function (string $prefix): array {
            return [
                "{$prefix}_street" => ['type' => 'varchar', 'len' => 150],
                "{$prefix}_city" => ['type' => 'varchar', 'len' => 100],
                "{$prefix}_state" => ['type' => 'varchar', 'len' => 100],
                "{$prefix}_postalcode" => ['type' => 'varchar', 'len' => 20],
                "{$prefix}_country" => ['type' => 'varchar', 'len' => 255],
            ];
        };

        return [
            'accounts' => [
                'table' => 'accounts',
                'label' => 'Accounts',
                'singular' => 'Account',
                'emails' => true,
                'audited' => true,
                'global_search' => true,
                'search_fields' => ['name'],
                'duplicate_rules' => [['name'], ['phone_office']],
                'fields' => [
                    // "Overview" tab
                    'name' => ['type' => 'varchar', 'len' => 150, 'required' => true],
                    'contact_name' => ['type' => 'varchar', 'len' => 150],     // main contact person
                    'contact_title' => ['type' => 'varchar', 'len' => 100],
                    'phone_office' => ['type' => 'varchar', 'len' => 100],
                    'phone_mobile' => ['type' => 'varchar', 'len' => 100],
                    'website' => ['type' => 'varchar', 'len' => 255],
                    'phone_fax' => ['type' => 'varchar', 'len' => 100],
                ] + $address('billing_address') + $address('shipping_address') + [
                    'description' => ['type' => 'text'],
                ] + $assigned + [
                    // "More information" tab
                    'account_type' => ['type' => 'enum', 'options' => $accountTypes],          // customer kind
                    'customer_stage' => ['type' => 'enum', 'options' => $customerStages],
                    'industry' => ['type' => 'enum', 'options' => $industries],
                    'annual_revenue' => ['type' => 'varchar', 'len' => 100],
                    'employees' => ['type' => 'varchar', 'len' => 10],
                    'parent_id' => ['type' => 'relate', 'module' => 'accounts'],      // "Member of"
                    'campaign_id' => ['type' => 'relate', 'module' => 'campaigns'],
                    'status' => ['type' => 'enum', 'options' => $customerStatuses],
                    'lead_source' => ['type' => 'enum', 'options' => $leadSources],
                    'tags' => ['type' => 'varchar', 'len' => 255],                   // comma-separated
                ],
                // Subpanels under the detail view
                'subpanels' => [
                    'activities' => ['type' => 'activities'],
                    'history' => ['type' => 'history'],
                    'contacts' => ['type' => 'relationship', 'relationship' => 'accounts_contacts'],
                    'opportunities' => ['type' => 'relationship', 'relationship' => 'accounts_opportunities'],
                    'leads' => ['type' => 'foreign_key', 'module' => 'leads', 'field' => 'account_id'],
                    'cases' => ['type' => 'foreign_key', 'module' => 'cases', 'field' => 'account_id'],
                ],
            ],

            'contacts' => [
                'table' => 'contacts',
                'label' => 'Contacts',
                'singular' => 'Contact',
                'person' => true,
                'emails' => true,
                'audited' => true,
                'global_search' => true,
                'search_fields' => ['first_name', 'last_name'],
                'duplicate_rules' => [['first_name', 'last_name']],
                'fields' => [
                    'first_name' => ['type' => 'varchar', 'len' => 100],
                    'last_name' => ['type' => 'varchar', 'len' => 100, 'required' => true],
                    'title' => ['type' => 'varchar', 'len' => 100],
                    'phone_work' => ['type' => 'varchar', 'len' => 100],
                    'phone_mobile' => ['type' => 'varchar', 'len' => 100],
                    'account_id' => ['type' => 'link', 'relationship' => 'accounts_contacts'],
                    'lead_source' => ['type' => 'enum', 'options' => $leadSources],
                ] + $address('primary_address') + [
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],

            'leads' => [
                'table' => 'leads',
                'label' => 'Leads',
                'singular' => 'Lead',
                'person' => true,
                'emails' => true,
                'audited' => true,
                'global_search' => true,
                'search_fields' => ['first_name', 'last_name', 'account_name'],
                'duplicate_rules' => [['first_name', 'last_name']],
                'fields' => [
                    'first_name' => ['type' => 'varchar', 'len' => 100],
                    'last_name' => ['type' => 'varchar', 'len' => 100, 'required' => true],
                    'title' => ['type' => 'varchar', 'len' => 100],
                    'phone_work' => ['type' => 'varchar', 'len' => 100],
                    'phone_mobile' => ['type' => 'varchar', 'len' => 100],
                    'status' => ['type' => 'enum', 'options' => $leadStatuses, 'default' => 'New'],
                    'lead_source' => ['type' => 'enum', 'options' => $leadSources],
                    'account_name' => ['type' => 'varchar', 'len' => 255],   // company name typed on the lead
                    // the account record the lead belongs to (Leads subpanel of an account)
                    'account_id' => ['type' => 'relate', 'module' => 'accounts', 'name_key' => 'linked_account_name'],
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],

            'opportunities' => [
                'table' => 'opportunities',
                'label' => 'Opportunities',
                'singular' => 'Opportunity',
                'audited' => true,
                'global_search' => true,
                'search_fields' => ['name'],
                'duplicate_rules' => [['name']],
                'fields' => [
                    'name' => ['type' => 'varchar', 'len' => 50, 'required' => true],
                    'account_id' => ['type' => 'link', 'relationship' => 'accounts_opportunities'],
                    'amount' => ['type' => 'decimal'],
                    'date_closed' => ['type' => 'date', 'required' => true],
                    'sales_stage' => ['type' => 'enum', 'options' => $salesStages, 'required' => true],
                    'probability' => ['type' => 'int'],
                    'lead_source' => ['type' => 'enum', 'options' => $leadSources],
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],

            'cases' => [
                'table' => 'cases',
                'label' => 'Cases',
                'singular' => 'Case',
                'audited' => true,
                'global_search' => true,
                'search_fields' => ['name'],
                'duplicate_rules' => [['name']],
                'fields' => [
                    'case_number' => ['type' => 'int', 'readonly' => true],
                    'name' => ['type' => 'varchar', 'len' => 255, 'required' => true],
                    'account_id' => ['type' => 'relate', 'module' => 'accounts'],
                    'status' => ['type' => 'enum', 'options' => $caseStatuses, 'default' => 'Open_New'],
                    'priority' => ['type' => 'enum', 'options' => ['P1' => 'High', 'P2' => 'Medium', 'P3' => 'Low']],
                    'type' => ['type' => 'enum', 'options' => self::options(['Administration', 'Product', 'User'])],
                    'state' => ['type' => 'enum', 'options' => self::options(['Open', 'Closed']), 'default' => 'Open'],
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],

            'calls' => [
                'table' => 'calls',
                'label' => 'Calls',
                'singular' => 'Call',
                'search_fields' => ['name'],
                'fields' => [
                    'name' => ['type' => 'varchar', 'len' => 50, 'required' => true],
                    'date_start' => ['type' => 'datetime', 'required' => true],
                    'duration_hours' => ['type' => 'int', 'default' => 0],
                    'duration_minutes' => ['type' => 'int', 'default' => 0],
                    'date_end' => ['type' => 'datetime', 'readonly' => true],   // date_start + duration
                    'status' => ['type' => 'enum', 'options' => $activityStatuses, 'default' => 'Planned'],
                    'direction' => ['type' => 'enum', 'options' => self::options(['Inbound', 'Outbound'])],
                ] + $parent + [
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],

            'meetings' => [
                'table' => 'meetings',
                'label' => 'Meetings',
                'singular' => 'Meeting',
                'search_fields' => ['name'],
                'fields' => [
                    'name' => ['type' => 'varchar', 'len' => 50, 'required' => true],
                    'location' => ['type' => 'varchar', 'len' => 50],
                    'date_start' => ['type' => 'datetime', 'required' => true],
                    'duration_hours' => ['type' => 'int', 'default' => 0],
                    'duration_minutes' => ['type' => 'int', 'default' => 0],
                    'date_end' => ['type' => 'datetime', 'readonly' => true],
                    'status' => ['type' => 'enum', 'options' => $activityStatuses, 'default' => 'Planned'],
                ] + $parent + [
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],

            'tasks' => [
                'table' => 'tasks',
                'label' => 'Tasks',
                'singular' => 'Task',
                'search_fields' => ['name'],
                'fields' => [
                    'name' => ['type' => 'varchar', 'len' => 50, 'required' => true],
                    'status' => ['type' => 'enum', 'options' => $taskStatuses, 'default' => 'Not Started'],
                    'priority' => ['type' => 'enum', 'options' => $priorities],
                    'date_start' => ['type' => 'datetime'],
                    'date_due' => ['type' => 'datetime'],
                ] + $parent + [
                    'contact_id' => ['type' => 'relate', 'module' => 'contacts'],
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],

            'notes' => [
                'table' => 'notes',
                'label' => 'Notes',
                'singular' => 'Note',
                'search_fields' => ['name'],
                'fields' => [
                    'name' => ['type' => 'varchar', 'len' => 255, 'required' => true],
                ] + $parent + [
                    'contact_id' => ['type' => 'relate', 'module' => 'contacts'],
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],

            'campaigns' => [
                'table' => 'campaigns',
                'label' => 'Campaigns',
                'singular' => 'Campaign',
                'search_fields' => ['name'],
                'fields' => [
                    'name' => ['type' => 'varchar', 'len' => 50, 'required' => true],
                    'status' => ['type' => 'enum', 'options' => self::options(['Planning', 'Active', 'Inactive', 'Complete'])],
                    'campaign_type' => ['type' => 'enum', 'options' => [
                        'Telesales' => 'Telesales', 'Mail' => 'Mail', 'Email' => 'Email', 'Print' => 'Print', 'Web' => 'Web',
                        'Radio' => 'Radio', 'Television' => 'Television', 'NewsLetter' => 'Newsletter', 'Survey' => 'Survey',
                    ]],
                    'start_date' => ['type' => 'date'],
                    'end_date' => ['type' => 'date'],
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],

            // Target lists, for the "Add to target list" bulk action
            'prospect_lists' => [
                'table' => 'prospect_lists',
                'label' => 'ProspectLists',
                'singular' => 'Target list',
                'search_fields' => ['name'],
                'fields' => [
                    'name' => ['type' => 'varchar', 'len' => 50, 'required' => true],
                    'list_type' => ['type' => 'enum', 'default' => 'default', 'options' => [
                        'default' => 'Default', 'seed' => 'Seed', 'exempt_domain' => 'Suppression List - By Domain',
                        'exempt_address' => 'Suppression List - By Email Address', 'exempt' => 'Suppression List - By Id',
                        'test' => 'Test',
                    ]],
                    'description' => ['type' => 'text'],
                ] + $assigned,
            ],
        ];
    }
}
