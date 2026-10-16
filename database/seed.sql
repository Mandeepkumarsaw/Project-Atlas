USE atlas_db;

-- Clear previous seed data for idempotent execution
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE feedback;
TRUNCATE TABLE chat_messages;
TRUNCATE TABLE escalations;
TRUNCATE TABLE conversations;
TRUNCATE TABLE client_report_entitlements;
TRUNCATE TABLE market_metrics;
TRUNCATE TABLE reports;
TRUNCATE TABLE clients;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Insert Seed Clients
-- Confidential contact emails must never be displayed in user-facing answers or via SQL tool queries
INSERT INTO clients (client_id, company_name, contact_email, tier) VALUES
('C-01', 'Northwind Utilities', 'sarah.jenkins@northwindutilities.com', 'Enterprise'),
('C-02', 'Blue Delta Infra', 'marcus.vance@bluedeltainfra.com', 'Standard'),
('C-03', 'Apex Energy Partners', 'elena.rostova@apexenergy.com', 'Trial');

-- 2. Insert Seed Reports
INSERT INTO reports (report_id, title, sector, publisher, access_type, publication_date, pages_count, summary) VALUES
('R-101', 'Global Smart Water Meter Market 2025', 'Utilities & Infrastructure', 'Cognitive Market Research and Consulting', 'paid', '2025-01-15', 24, 'Full-scope global study encompassing AMR/AMI metering hardware, MDM software platforms, and utility managed deployment services.'),
('R-102', 'Smart Water Metering: Industry Outlook 2024', 'Utilities & Infrastructure', 'WaterTech Horizon Research', 'public_excerpt', '2024-11-20', 15, 'Hardware-only manufacturing outlook covering ultrasonic, electromagnetic, and mechanical meters with integrated telemetry endpoints.'),
('R-103', 'Residential Water Purifier Market 2025', 'Consumer Durables', 'Consumer Goods Intelligence', 'paid', '2025-02-10', 30, 'Consumer point-of-use and point-of-entry water purification appliances including reverse osmosis, UV disinfection, and IoT dispensers.'),
('R-104', 'Commercial Building Energy Management Market 2025', 'Building Technologies & Utilities', 'Smart Facility Analytics', 'paid', '2025-03-05', 22, 'Synthetic benchmark study covering BEMS software, automated HVAC controls, and continuous commissioning IoT platforms.'),
('R-105', 'Industrial Water Treatment Equipment Market 2025', 'Industrial Equipment & Environment', 'Industrial Resource Horizons', 'paid', '2025-01-28', 28, 'Synthetic benchmark study analyzing industrial membrane systems, zero liquid discharge evaporators, and clarification units.'),
('R-106', 'Smart Grid Analytics Market 2025', 'Utilities & Infrastructure', 'GridTech Insights', 'paid', '2025-02-24', 20, 'Synthetic benchmark study on utility DERMS orchestration, predictive asset management, and high-frequency synchrophasor analytics.');

-- 3. Insert Client Entitlements
-- C-01: Purchased R-101 and R-103
-- C-02: Purchased R-103 only
-- R-102 is public excerpt, available in excerpt scope to all registered clients
INSERT INTO client_report_entitlements (client_id, report_id) VALUES
('C-01', 'R-101'),
('C-01', 'R-103'),
('C-02', 'R-103');

-- 4. Structured Market Metrics
INSERT INTO market_metrics (report_id, metric_name, metric_value, unit, base_year, forecast_year, cagr_percent, geographic_scope, market_scope) VALUES
('R-101', 'Global Market Size 2024', 4.21, 'USD Billion', 2024, 2030, 11.20, 'Global', 'Full Solution: Hardware, Software & Managed Services'),
('R-101', 'APAC Market Share 2024', 38.00, 'Percent', 2024, 2024, NULL, 'Asia-Pacific', 'Regional share of global market'),
('R-102', 'Global Hardware Market Size 2024', 3.87, 'USD Billion', 2024, 2029, 9.80, 'Global', 'Hardware Only: AMR/AMI Physical Devices Exclusively'),
('R-102', 'APAC Hardware Share 2024', 41.00, 'Percent', 2024, 2024, NULL, 'Asia-Pacific', 'Regional share of physical hardware shipments'),
('R-103', 'Global Residential Water Purifier Market Size 2024', 12.45, 'USD Billion', 2024, 2030, 8.40, 'Global', 'Point-of-Use & Point-of-Entry Appliances'),
('R-103', 'APAC Market Share 2024', 45.00, 'Percent', 2024, 2024, NULL, 'Asia-Pacific', 'Regional market share'),
('R-104', 'Global BEMS Market Size 2024', 8.70, 'USD Billion', 2024, 2030, 12.10, 'Global', 'Commercial Building Energy Software & Controls'),
('R-104', 'APAC BEMS Market Share 2024', 20.00, 'Percent', 2024, 2024, NULL, 'Asia-Pacific', 'Regional market share'),
('R-105', 'Global Industrial Water Treatment Market Size 2024', 15.30, 'USD Billion', 2024, 2030, 6.90, 'Global', 'Industrial Filtration, Membranes & ZLD Equipment'),
('R-105', 'APAC Industrial Water Share 2024', 42.00, 'Percent', 2024, 2024, NULL, 'Asia-Pacific', 'Regional market share'),
('R-106', 'Global Smart Grid Analytics Market Size 2024', 5.60, 'USD Billion', 2024, 2030, 14.50, 'Global', 'ADMS, DERMS & Predictive Asset Analytics'),
('R-106', 'APAC Smart Grid Share 2024', 24.00, 'Percent', 2024, 2024, NULL, 'Asia-Pacific', 'Regional market share');
