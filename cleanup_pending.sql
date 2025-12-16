-- TTRPG Recap - Cleanup Script for Stuck Pending Sessions
-- Use this to remove all pending sessions and their jobs

-- =====================================================
-- STEP 1: BACKUP (Always backup first!)
-- =====================================================

-- Backup pending sessions
CREATE TABLE IF NOT EXISTS sessions_backup AS 
SELECT * FROM sessions WHERE status = 'pending';

-- Backup pending jobs
CREATE TABLE IF NOT EXISTS jobs_backup AS 
SELECT * FROM jobs WHERE status = 'pending';

-- =====================================================
-- STEP 2: VIEW WHAT WILL BE DELETED
-- =====================================================

-- Check pending sessions
SELECT id, title, status, created_at 
FROM sessions 
WHERE status = 'pending'
ORDER BY created_at DESC;

-- Check pending jobs
SELECT id, queue, status, attempts, created_at 
FROM jobs 
WHERE status = 'pending'
ORDER BY created_at DESC;

-- =====================================================
-- STEP 3: DELETE PENDING JOBS
-- =====================================================

-- Delete all pending jobs
DELETE FROM jobs WHERE status = 'pending';

-- Verify deletion
SELECT COUNT(*) as pending_jobs FROM jobs WHERE status = 'pending';
-- Should return: 0

-- =====================================================
-- STEP 4: DELETE PENDING SESSIONS
-- =====================================================

-- Delete transcripts for pending sessions (if any)
DELETE FROM transcripts 
WHERE session_id IN (
    SELECT id FROM sessions WHERE status = 'pending'
);

-- Delete session entities for pending sessions  
DELETE FROM session_entities 
WHERE session_id IN (
    SELECT id FROM sessions WHERE status = 'pending'
);

-- Delete the pending sessions themselves
DELETE FROM sessions WHERE status = 'pending';

-- Verify deletion
SELECT COUNT(*) as pending_sessions FROM sessions WHERE status = 'pending';
-- Should return: 0

-- =====================================================
-- STEP 5: OPTIONAL - DELETE AUDIO FILES
-- =====================================================

-- You need to manually delete audio files for removed sessions
-- Files are in: c:\laragon\www\ttrpg-recap\storage\audio\

-- List audio files to delete:
SELECT 
    id,
    title,
    audio_file_path 
FROM sessions_backup;

-- Then manually delete those files from the filesystem

-- =====================================================
-- STEP 6: VERIFY CLEANUP
-- =====================================================

-- Check all sessions
SELECT status, COUNT(*) as count 
FROM sessions 
GROUP BY status;

-- Check all jobs
SELECT status, COUNT(*) as count 
FROM jobs 
GROUP BY status;

-- =====================================================
-- STEP 7: RESTORE IF NEEDED (Optional)
-- =====================================================

-- If you deleted something by mistake:
-- INSERT INTO sessions SELECT * FROM sessions_backup WHERE id = <ID>;
-- INSERT INTO jobs SELECT * FROM jobs_backup WHERE id = <ID>;

-- =====================================================
-- CLEANUP COMPLETE!
-- =====================================================

-- Now you can:
-- 1. Start the worker: php worker.php
-- 2. Upload new sessions
-- 3. Watch them process correctly!
