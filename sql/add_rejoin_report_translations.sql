-- Translations for the new "Employee Rejoin Report" (reports.php)
-- Table: translations (lang_key, lang_code, translation)
-- NOTE: 'requested_rejoin_date' and 'rejection_reason' already exist (used by the
-- rejoin approval workflow) and are intentionally NOT touched here to avoid
-- overwriting their existing wording. 'approved_by' also already exists (used
-- elsewhere with a trailing colon, e.g. "Approved by:") so the rejoin report
-- column uses a distinct key 'rejoin_approved_by' instead.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('rejoin_report', 'en', 'Employee Rejoin Report'),
('final_approved_date', 'en', 'Final Rejoin Date'),
('rejoin_approved_by', 'en', 'Approved By'),
('requested_reason', 'en', 'Requested Reason'),
('requested_at', 'en', 'Requested At'),
('approved_at', 'en', 'Approved At'),
('adjusted', 'en', 'Adjusted')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('rejoin_report', 'ar', 'تقرير عودة الموظف من الإجازة'),
('final_approved_date', 'ar', 'تاريخ العودة المعتمد'),
('rejoin_approved_by', 'ar', 'تمت الموافقة من قبل'),
('requested_reason', 'ar', 'سبب الطلب'),
('requested_at', 'ar', 'تاريخ تقديم الطلب'),
('approved_at', 'ar', 'تاريخ الموافقة'),
('adjusted', 'ar', 'معدّل')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
