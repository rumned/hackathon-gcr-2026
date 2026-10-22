-- Customer sales status: the stages of the sales tracker.
-- accounts.status now holds Lead, Follow-up, Test Drive, Quotation, Follow-up 2 or Won.
-- Old values are converted: New -> Lead, Contacted -> Follow-up, Qualified -> Test Drive,
-- Proposal -> Quotation. Customers with no status get Won when their Stage is Existing,
-- otherwise Lead. Won customers get Stage Existing.
-- Run once, after 002:  mysql -u root -p crm < sql/migrations/003_sales_status_pipeline.sql

UPDATE accounts SET status = CASE status
    WHEN 'New' THEN 'Lead'
    WHEN 'Contacted' THEN 'Follow-up'
    WHEN 'Qualified' THEN 'Test Drive'
    WHEN 'Proposal' THEN 'Quotation'
    ELSE status
  END
  WHERE status IN ('New', 'Contacted', 'Qualified', 'Proposal');

UPDATE accounts SET status = IF(customer_stage = 'Existing', 'Won', 'Lead')
  WHERE status IS NULL;

UPDATE accounts SET customer_stage = 'Existing'
  WHERE status = 'Won';
