-- ============================================================================
-- CLEANUP SCRIPT: Remove Duplicate Approvers in Request Chain
-- ============================================================================
-- This script identifies and removes duplicate approver entries where the same
-- employee appears multiple times in the same request's approval chain
-- ============================================================================

-- STEP 1: IDENTIFY DUPLICATES
-- Lists all duplicate approver entries (same approver_id, request_inv_no, request_type_id)
-- keeping only the first occurrence
SELECT 'DUPLICATES FOUND' as analysis;
SELECT 
    request_inv_no,
    request_type_id,
    approver_id,
    COUNT(*) as duplicate_count,
    GROUP_CONCAT(id) as duplicate_ids
FROM request_approvers
GROUP BY request_inv_no, request_type_id, approver_id
HAVING COUNT(*) > 1
ORDER BY request_inv_no, approver_id;

-- STEP 2: DELETE DUPLICATES (KEEP FIRST OCCURRENCE)
-- WARNING: This will DELETE duplicate rows. Backup your database first!
-- This query keeps the row with the lowest ID and deletes all others
DELETE FROM request_approvers
WHERE id NOT IN (
    SELECT MIN(id)
    FROM (
        SELECT MIN(id) as id
        FROM request_approvers
        GROUP BY request_inv_no, request_type_id, approver_id
    ) as keep_rows
);

-- STEP 3: VERIFY CLEANUP
-- Should show 0 results if all duplicates are removed
SELECT 'VERIFICATION - Should be empty' as check;
SELECT 
    request_inv_no,
    request_type_id,
    approver_id,
    COUNT(*) as duplicate_count
FROM request_approvers
GROUP BY request_inv_no, request_type_id, approver_id
HAVING COUNT(*) > 1;

-- STEP 4: FINAL COUNT
SELECT 'FINAL STATUS' as info;
SELECT COUNT(*) as total_approvers FROM request_approvers;
SELECT COUNT(DISTINCT CONCAT(request_inv_no, '-', request_type_id, '-', approver_id)) as unique_approvers FROM request_approvers;
