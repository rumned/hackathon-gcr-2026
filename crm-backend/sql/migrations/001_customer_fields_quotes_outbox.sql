-- Brings a database created from an earlier sql/schema.sql up to date:
-- customer fields on accounts, quotations and the sent-email outbox.
-- Run once:  mysql -u root -p crm < sql/migrations/001_customer_fields_quotes_outbox.sql

ALTER TABLE accounts
  ADD COLUMN contact_name VARCHAR(150) NULL,
  ADD COLUMN contact_title VARCHAR(100) NULL,
  ADD COLUMN phone_mobile VARCHAR(100) NULL,
  ADD COLUMN status VARCHAR(100) NULL,
  ADD COLUMN lead_source VARCHAR(100) NULL,
  ADD COLUMN tags VARCHAR(255) NULL;

CREATE TABLE quotes (
  id CHAR(36) NOT NULL,
  quote_number INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NULL,
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  description TEXT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  account_id CHAR(36) NULL,
  opportunity_id CHAR(36) NULL,
  status VARCHAR(25) NOT NULL DEFAULT 'Draft',  -- Draft, Sent, Not sent
  send_error VARCHAR(255) NULL,                 -- why the last send failed
  subtotal DECIMAL(26,2) NOT NULL DEFAULT 0,
  discount_type VARCHAR(10) NULL,               -- percent or amount
  discount_value DECIMAL(26,2) NULL,
  discount_label VARCHAR(100) NULL,             -- promotion name shown on the quotation
  discount_amount DECIMAL(26,2) NOT NULL DEFAULT 0,
  total DECIMAL(26,2) NOT NULL DEFAULT 0,
  valid_until DATE NULL,
  date_sent DATETIME NULL,
  sent_to VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_quote_number (quote_number),
  KEY idx_quote_account (account_id, deleted),
  KEY idx_quote_opportunity (opportunity_id)
) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8mb4;

CREATE TABLE quote_items (
  id CHAR(36) NOT NULL,
  quote_id CHAR(36) NOT NULL,
  position INT NOT NULL DEFAULT 1,
  name VARCHAR(255) NOT NULL,
  quantity DECIMAL(12,2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(26,2) NOT NULL DEFAULT 0,
  total DECIMAL(26,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_quote_items_quote (quote_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- sent emails (outbox)
-- Every email the backend sends is kept here (see src/Mailer.php).
CREATE TABLE emails (
  id CHAR(36) NOT NULL,
  name VARCHAR(255) NULL,          -- subject
  date_entered DATETIME NULL,
  date_modified DATETIME NULL,
  modified_user_id CHAR(36) NULL,
  created_by CHAR(36) NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  assigned_user_id CHAR(36) NULL,
  date_sent DATETIME NULL,
  status VARCHAR(100) NULL,        -- outbox (saved only) or sent (delivered by a mail transport)
  type VARCHAR(100) NULL,          -- out
  from_addr VARCHAR(255) NULL,
  to_addrs TEXT NULL,
  description TEXT NULL,           -- plain-text body
  parent_type VARCHAR(100) NULL,   -- Accounts
  parent_id CHAR(36) NULL,
  quote_id CHAR(36) NULL,
  PRIMARY KEY (id),
  KEY idx_email_parent (parent_type, parent_id),
  KEY idx_email_quote (quote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
