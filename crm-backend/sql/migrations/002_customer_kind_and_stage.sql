-- Customer kind and stage: account_type now holds Individual, Business or Enterprise,
-- and the new customer_stage column holds Lead or Existing.
-- Old SuiteCRM types are converted: Prospect and Competitor become Business leads,
-- the other types become Business with stage Existing.
-- Run once:  mysql -u root -p crm < sql/migrations/002_customer_kind_and_stage.sql

ALTER TABLE accounts
  ADD COLUMN customer_stage VARCHAR(50) NULL;

UPDATE accounts SET customer_stage = 'Lead', account_type = 'Business'
  WHERE account_type IN ('Prospect', 'Competitor');
UPDATE accounts SET customer_stage = 'Existing', account_type = 'Business'
  WHERE account_type IN ('Customer', 'Partner', 'Reseller', 'Integrator', 'Investor', 'Analyst', 'Press', 'Other');
