# Troubleshooting: All Sessions Stuck in "Pending"

## The Problem

All uploaded sessions show status "pending" and never progress to "transcribing" or "complete".

---

## Root Cause

**The worker is not running!**

The worker (`php worker.php`) is responsible for processing jobs from the queue. If it's not running, jobs will remain in "pending" status forever.

---

## ✅ Solution

### **Step 1: Check if Worker is Running**

**Windows:**
```powershell
# Check for running PHP processes
Get-Process php | Select-Object Id, ProcessName, StartTime

# Or check if worker is in process list
tasklist | findstr php
```

**You should see:** A PHP process that's been running since you started the worker.

**You won't see anything if:** The worker is not running.

### **Step 2: Start the Worker**

Open a **new terminal** and run:

```bash
cd c:\laragon\www\ttrpg-recap
php worker.php
```

**You should see:**
```
Worker started. Listening on queue: default
Press Ctrl+C to stop.
```

**Keep this terminal open!** The worker must stay running to process jobs.

### **Step 3: Wait for Processing**

Once the worker starts, it will immediately pick up pending jobs:

```
[13:02:15] 🎙️  Starting transcription for session 1
[13:02:15] 📄 Session: Test Session
[13:02:15] 📦 File size: 5.23 MB
[13:02:15] 🚀 Sending to Whisper API...
...
```

Sessions will progress:
- **Pending** → **Transcribing** → **Processing** → **Complete**

---

## 🗑️ Delete Stuck Sessions

I've added a delete function. Here's how to use it:

### **Option 1: Delete via SQL**

```sql
-- Delete a specific session
DELETE FROM sessions WHERE id = 1;

-- Delete all pending sessions
DELETE FROM sessions WHERE status = 'pending';

-- Clean up orphaned data as well
DELETE FROM jobs WHERE payload LIKE '%"session_id":1%';
DELETE FROM transcripts WHERE session_id = 1;
DELETE FROM recaps WHERE session_id = 1;
DELETE FROM session_entities WHERE session_id = 1;
```

### **Option 2: Delete via API (JavaScript)**

Add this to your campaign page console:

```javascript
// Delete session #1
fetch('/sessions/1', { method: 'DELETE' })
  .then(r => r.json())
  .then(data => console.log(data));

// Delete all pending sessions (run this in SQL instead)
```

### **Option 3: Add Delete Button to UI**

I can add delete buttons to the campaign page if you'd like!

---

## 🔍 Verify Jobs are Processing

### **Check Jobs Table**

```sql
SELECT id, queue, status, attempts, created_at, completed_at 
FROM jobs 
ORDER BY created_at DESC 
LIMIT 10;
```

**Before worker starts:**
```
id | queue   | status  | attempts
1  | default | pending | 0
2  | default | pending | 0
```

**After worker starts:**
```
id | queue   | status    | attempts
1  | default | completed | 1
2  | default | completed | 1
```

### **Watch Worker Output**

The worker terminal shows real-time progress:
- Job picked up
- API calls
- Processing time
- Completion status

---

## 📋 Prevention Checklist

To avoid this issue:

- [ ] **Always start worker before uploading**
  ```bash
  php worker.php
  ```

- [ ] **Keep worker terminal open**
  - Don't close the window
  - Worker must run continuously

- [ ] **Monitor worker status**
  - Check for errors
  - Watch for crashes
  - Restart if needed

- [ ] **Set up auto-restart (Production)**
  - Use Supervisor (Linux)
  - Use NSSM (Windows)
  - See `DEPLOYMENT.md`

---

## 🔧 Quick Cleanup Script

Run this to clean up ALL pending sessions and jobs:

```sql
-- Backup first!
SELECT * FROM sessions WHERE status = 'pending';
SELECT * FROM jobs WHERE status = 'pending';

-- Then delete
DELETE FROM jobs WHERE status = 'pending';
DELETE FROM sessions WHERE status = 'pending';
```

**Warning:** This deletes data permanently!

---

## 🚀 Proper Workflow

### **1. Start Worker First**
```bash
cd c:\laragon\www\ttrpg-recap
php worker.php
```

### **2. Upload Sessions**
- Upload via web interface
- Sessions queue automatically

### **3. Monitor Progress**
- Watch worker terminal
- Refresh campaign page
- Check AI Workflow Debugging section

### **4. Keep Worker Running**
- Don't close terminal
- Watch for errors
- Restart if crashes

---

## 🔒 Production Setup

For production, set up worker as a service:

### **Windows (NSSM)**
```powershell
nssm install TTRPGWorker "C:\laragon\bin\php\php.exe" "C:\laragon\www\ttrpg-recap\worker.php"
nssm set TTRPGWorker AppDirectory "C:\laragon\www\ttrpg-recap"
nssm start TTRPGWorker
```

### **Linux (Supervisor)**
```ini
[program:ttrpg-worker]
command=php /var/www/ttrpg-recap/worker.php
directory=/var/www/ttrpg-recap
autostart=true
autorestart=true
user=www-data
```

See `DEPLOYMENT.md` for full details.

---

## ✅ Success Criteria

You'll know it's working when:

✅ Worker terminal shows activity
✅ Sessions progress from pending → transcribing → processing → complete
✅ Jobs table shows "completed" status
✅ Campaign page shows processing statistics
✅ Recaps are generated

---

## 🆘 Still Having Issues?

1. **Check worker terminal for errors**
2. **Review Apache error log**
3. **Verify API keys are valid**
4. **Check database connection**
5. **See `DEBUG_AI_WORKFLOW.md` for detailed debugging**

---

**Remember: The worker MUST be running for jobs to process!** 🚨

This is the #1 cause of "pending" sessions. Always start the worker before uploading!
