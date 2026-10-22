-- CRM backend schema (MySQL 8 / MariaDB 10.6+)
-- Table and column names follow SuiteCRM 7: char(36) UUID ids, a `deleted` flag
-- for soft deletes, and date_entered / date_modified / created_by /
-- modified_user_id / assigned_user_id on every module table.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------- users
CREATE TABLE users (
  id CHAR(36) NOT NULL,
  user_name VARCHAR(60) NOT NULL,
  user_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(255) NULL,
  last_name VARCHAR(255) NULL,
  title VARCHAR(50) NULL,
  department VARCHAR(50) NULL,
  phone_work VARCHAR(50) NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(100) NOT NULL DEFAULT 'Active',
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY idx_user_name (user_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- accounts
CREATE TABLE accounts (
  id CHAR(36) NOT NULL,
  name VARCHAR(150) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  account_type VARCHAR(50) NULL,
  industry VARCHAR(50) NULL,
  annual_revenue VARCHAR(100) NULL,
  phone_fax VARCHAR(100) NULL,
  billing_address_street VARCHAR(150) NULL,
  billing_address_city VARCHAR(100) NULL,
  billing_address_state VARCHAR(100) NULL,
  billing_address_postalcode VARCHAR(20) NULL,
  billing_address_country VARCHAR(255) NULL,
  phone_office VARCHAR(100) NULL,
  website VARCHAR(255) NULL,
  employees VARCHAR(10) NULL,
  shipping_address_street VARCHAR(150) NULL,
  shipping_address_city VARCHAR(100) NULL,
  shipping_address_state VARCHAR(100) NULL,
  shipping_address_postalcode VARCHAR(20) NULL,
  shipping_address_country VARCHAR(255) NULL,
  parent_id CHAR(36) NULL,
  campaign_id CHAR(36) NULL,
  PRIMARY KEY (id),
  KEY idx_accnt_name_del (name, deleted),
  KEY idx_accnt_assigned_del (deleted, assigned_user_id),
  KEY idx_accnt_parent_id (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- contacts
CREATE TABLE contacts (
  id CHAR(36) NOT NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  first_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  title VARCHAR(100) NULL,
  phone_work VARCHAR(100) NULL,
  phone_mobile VARCHAR(100) NULL,
  primary_address_street VARCHAR(150) NULL,
  primary_address_city VARCHAR(100) NULL,
  primary_address_state VARCHAR(100) NULL,
  primary_address_postalcode VARCHAR(20) NULL,
  primary_address_country VARCHAR(255) NULL,
  lead_source VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_cont_last_first (last_name, first_name, deleted),
  KEY idx_cont_assigned (assigned_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- leads
CREATE TABLE leads (
  id CHAR(36) NOT NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  first_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  title VARCHAR(100) NULL,
  phone_work VARCHAR(100) NULL,
  phone_mobile VARCHAR(100) NULL,
  status VARCHAR(100) NULL,
  lead_source VARCHAR(100) NULL,
  account_name VARCHAR(255) NULL,
  account_id CHAR(36) NULL,
  PRIMARY KEY (id),
  KEY idx_lead_last_first (last_name, first_name, deleted),
  KEY idx_lead_assigned (assigned_user_id),
  KEY idx_lead_account (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- opportunities
CREATE TABLE opportunities (
  id CHAR(36) NOT NULL,
  name VARCHAR(50) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  lead_source VARCHAR(50) NULL,
  amount DECIMAL(26,6) NULL,
  date_closed DATE NULL,
  sales_stage VARCHAR(255) NULL,
  probability DOUBLE NULL,
  PRIMARY KEY (id),
  KEY idx_opp_name (name),
  KEY idx_opp_assigned (assigned_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- cases
CREATE TABLE cases (
  id CHAR(36) NOT NULL,
  name VARCHAR(255) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  case_number INT NOT NULL AUTO_INCREMENT,
  type VARCHAR(255) NULL,
  status VARCHAR(100) NULL,
  priority VARCHAR(100) NULL,
  state VARCHAR(100) NULL,
  account_id CHAR(36) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY casesnumk (case_number),
  KEY idx_case_name (name),
  KEY idx_account_id (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- calls
CREATE TABLE calls (
  id CHAR(36) NOT NULL,
  name VARCHAR(50) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  duration_hours INT NULL,
  duration_minutes INT NULL,
  date_start DATETIME NULL,
  date_end DATETIME NULL,
  parent_type VARCHAR(255) NULL,
  status VARCHAR(100) NULL,
  direction VARCHAR(100) NULL,
  parent_id CHAR(36) NULL,
  PRIMARY KEY (id),
  KEY idx_call_name (name),
  KEY idx_calls_parent (parent_type, parent_id),
  KEY idx_calls_assigned (assigned_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- meetings
CREATE TABLE meetings (
  id CHAR(36) NOT NULL,
  name VARCHAR(50) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  location VARCHAR(50) NULL,
  duration_hours INT NULL,
  duration_minutes INT NULL,
  date_start DATETIME NULL,
  date_end DATETIME NULL,
  parent_type VARCHAR(100) NULL,
  status VARCHAR(100) NULL,
  parent_id CHAR(36) NULL,
  PRIMARY KEY (id),
  KEY idx_mtg_name (name),
  KEY idx_meet_parent (parent_type, parent_id),
  KEY idx_meet_assigned (assigned_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- tasks
CREATE TABLE tasks (
  id CHAR(36) NOT NULL,
  name VARCHAR(50) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  status VARCHAR(100) NULL,
  date_due DATETIME NULL,
  date_start DATETIME NULL,
  parent_type VARCHAR(255) NULL,
  parent_id CHAR(36) NULL,
  contact_id CHAR(36) NULL,
  priority VARCHAR(100) NULL,
  PRIMARY KEY (id),
  KEY idx_tsk_name (name),
  KEY idx_task_parent (parent_type, parent_id),
  KEY idx_task_assigned (assigned_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- notes
CREATE TABLE notes (
  id CHAR(36) NOT NULL,
  name VARCHAR(255) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  parent_type VARCHAR(255) NULL,
  parent_id CHAR(36) NULL,
  contact_id CHAR(36) NULL,
  PRIMARY KEY (id),
  KEY idx_note_name (name),
  KEY idx_notes_parent (parent_type, parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- campaigns
CREATE TABLE campaigns (
  id CHAR(36) NOT NULL,
  name VARCHAR(50) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  status VARCHAR(100) NULL,
  campaign_type VARCHAR(100) NULL,
  PRIMARY KEY (id),
  KEY idx_campaign_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- target lists
CREATE TABLE prospect_lists (
  id CHAR(36) NOT NULL,
  name VARCHAR(50) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  list_type VARCHAR(100) NULL,
  PRIMARY KEY (id),
  KEY idx_prospect_list_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Members of a target list (Accounts, Contacts or Leads)
CREATE TABLE prospect_lists_prospects (
  id CHAR(36) NOT NULL,
  prospect_list_id CHAR(36) NOT NULL,
  related_id CHAR(36) NOT NULL,
  related_type VARCHAR(25) NOT NULL,
  date_modified DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_plp_list_related (prospect_list_id, related_id),
  KEY idx_plp_related (related_type, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- relationships
-- A contact or opportunity is linked to one account through these tables.
CREATE TABLE accounts_contacts (
  id CHAR(36) NOT NULL,
  contact_id CHAR(36) NOT NULL,
  account_id CHAR(36) NOT NULL,
  date_modified DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_account_contact (account_id, contact_id),
  KEY idx_contid_accid (contact_id, account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE accounts_opportunities (
  id CHAR(36) NOT NULL,
  opportunity_id CHAR(36) NOT NULL,
  account_id CHAR(36) NOT NULL,
  date_modified DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_account_opportunity (account_id, opportunity_id),
  KEY idx_oppid_accid (opportunity_id, account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- email addresses
-- One row per address; email_addr_bean_rel links it to accounts, contacts or leads.
CREATE TABLE email_addresses (
  id CHAR(36) NOT NULL,
  email_address VARCHAR(255) NULL,
  email_address_caps VARCHAR(255) NULL,
  invalid_email TINYINT(1) NOT NULL DEFAULT 0,
  opt_out TINYINT(1) NOT NULL DEFAULT 0,
  date_created DATETIME NULL,
  date_modified DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_ea_caps_opt_out_invalid (email_address_caps, opt_out, invalid_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE email_addr_bean_rel (
  id CHAR(36) NOT NULL,
  email_address_id CHAR(36) NOT NULL,
  bean_id CHAR(36) NOT NULL,
  bean_module VARCHAR(100) NOT NULL,
  primary_address TINYINT(1) NOT NULL DEFAULT 0,
  reply_to_address TINYINT(1) NOT NULL DEFAULT 0,
  date_created DATETIME NULL,
  date_modified DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_email_address_id (email_address_id),
  KEY idx_bean_id (bean_id, bean_module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- change log ("View change log")
CREATE TABLE accounts_audit (
  id CHAR(36) NOT NULL,
  parent_id CHAR(36) NOT NULL,
  date_created DATETIME NULL,
  created_by VARCHAR(36) NULL,
  field_name VARCHAR(100) NULL,
  data_type VARCHAR(100) NULL,
  before_value_string VARCHAR(255) NULL,
  after_value_string VARCHAR(255) NULL,
  before_value_text TEXT NULL,
  after_value_text TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_accounts_parent_id (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contacts_audit LIKE accounts_audit;
CREATE TABLE leads_audit LIKE accounts_audit;
CREATE TABLE opportunities_audit LIKE accounts_audit;
CREATE TABLE cases_audit LIKE accounts_audit;

-- ---------------------------------------------------------------- sidebar: favorites and recently viewed
CREATE TABLE favorites (
  id CHAR(36) NOT NULL,
  assigned_user_id CHAR(36) NOT NULL,
  parent_type VARCHAR(255) NOT NULL,
  parent_id CHAR(36) NOT NULL,
  date_entered DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_fav_user_record (assigned_user_id, parent_type, parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tracker (
  id INT NOT NULL AUTO_INCREMENT,
  user_id VARCHAR(36) NULL,
  module_name VARCHAR(255) NULL,
  item_id VARCHAR(36) NULL,
  item_summary VARCHAR(255) NULL,
  date_modified DATETIME NULL,
  action VARCHAR(255) NULL,
  visible TINYINT(1) NOT NULL DEFAULT 0,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_tracker_userid_vis_id (user_id, visible, id),
  KEY idx_tracker_iid (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- top bar: alerts
CREATE TABLE alerts (
  id CHAR(36) NOT NULL,
  name VARCHAR(255) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  target_module VARCHAR(255) NULL,
  target_id CHAR(36) NULL,
  type VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_alerts_user_read (assigned_user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- per-user settings (dashboard tabs, list columns)
CREATE TABLE user_preferences (
  id CHAR(36) NOT NULL,
  category VARCHAR(50) NOT NULL,
  assigned_user_id CHAR(36) NOT NULL,
  contents LONGTEXT NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY idx_userprefs_user_cat (assigned_user_id, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
