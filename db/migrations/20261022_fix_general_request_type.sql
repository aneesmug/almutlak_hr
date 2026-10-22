-- General Requests: approval_request_types row had main_table_name = 'general_request' (no such table).
-- handle_approval_action() updates `main_table_name` by name, so approve/reject failed, and pages that
-- looked the type up by main_table_name = 'general_requests' showed an empty approval chain.
-- Also repaired automatically by gr_request_type_id() (includes/general_request_helpers.php). Idempotent.
UPDATE approval_request_types SET main_table_name = 'general_requests'
WHERE type_name = 'general_request' AND main_table_name <> 'general_requests';
