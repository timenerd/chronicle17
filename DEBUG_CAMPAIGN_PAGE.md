# AI Workflow Debugging - Campaign Page Integration

## ✅ What Was Added

The campaign detail page now includes a comprehensive **AI Workflow Debugging** section that appears **only in development mode** (`APP_ENV=development`).

---

## 🔬 Features

### **1. Processing Statistics Dashboard**

Visual overview of all sessions in the campaign:

- ✅ **Complete** - Successfully processed sessions
- ⏳ **Processing** - Currently being summarized by Claude
- 🎙️ **Transcribing** - Currently being transcribed by Whisper  
- 🟡 **Pending** - Waiting in queue
- ❌ **Failed** - Processing errors

Each stat shows:
- Count of sessions in that status
- Visual icon for quick identification

### **2. Recent Jobs Queue Table**

Real-time view of background job processing:

| Column | Shows |
|--------|-------|
| **Job ID** | Unique job identifier |
| **Type** | 🎙️ Transcribe or ✨ Summarize |
| **Session** | Linked to session detail page |
| **Status** | pending, completed, failed |
| **Attempts** | X/3 retry attempts |
| **Created** | When job was queued (HH:MM:SS) |
| **Duration** | How long it took to complete |

**Features:**
- Scrollable table (max 400px height)
- Shows last 15 jobs
- Sticky header while scrolling
- Color-coded status badges
- Clickable session links

### **3. Live Refresh**

- Shows last update timestamp
- 🔄 Refresh button to reload page
- See real-time job queue updates

### **4. Debug Hints**

Quick reference section with:
- Worker terminal command
- Error log location
- Documentation link

---

## 📊 What It Looks Like

```
┌─────────────────────────────────────────────────────────────┐
│ 🔬 AI Workflow Debugging                  [DEV MODE]   │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  [✅ 5 Complete] [⏳ 1 Processing] [🎙️ 1 Transcribing]     │
│                                                               │
├─────────────────────────────────────────────────────────────┤
│ 📋 Recent Jobs                                               │
├─────────────────────────────────────────────────────────────┤
│ Job  │ Type        │ Session       │ Status     │ Duration  │
│ #12  │ ✨ Summarize│ Episode 1     │ Completed  │ 1m 23s   │
│ #11  │ 🎙️ Transcribe│ Episode 1     │ Completed  │ 2m 45s   │
│ #10  │ ✨ Summarize│ Session 2     │ Running... │ running...│
├─────────────────────────────────────────────────────────────┤
│ Last updated: 10:19:31            [🔄 Refresh]            │
├─────────────────────────────────────────────────────────────┤
│ 💡 Debug Hints:                                             │
│ • Watch worker terminal: php worker.php                      │
│ • Check error log: c:/laragon/bin/apache/logs/error.log    │
│ • See detailed guide: DEBUG_AI_WORKFLOW.md                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 When to Use

### **Development Mode**
Perfect for:
- ✅ Monitoring AI processing in real-time
- ✅ Debugging stuck jobs
- ✅ Seeing processing duration
- ✅ Tracking job failures
- ✅ Understanding task flow

### **Production Mode**
Automatically hidden when `APP_ENV=production`:
- Clean user-facing interface
- No technical details shown
- Better UX for end users

---

## 📝 How to Use

### **1. Enable Development Mode**

Edit `c:/laragon/www/.env`:
```env
APP_ENV=development
```

### **2. Visit Campaign Page**

Navigate to any campaign:
```
http://ttrpg-recap.test/campaigns/1
```

You'll see the debugging section at the top!

### **3. Upload a Session**

Click "Upload Session" and upload an audio file.

### **4. Watch Live**

The debugging section will show:
1. Job added to queue (#13, #14)
2. Transcribe job running
3. Transcribe job completes (with duration)
4. Summarize job running
5. Summarize job completes
6. Session status updates

### **5. Refresh for Updates**

Click "🔄 Refresh" button to see latest status.

Or set up auto-refresh (see below).

---

## 🔄 Auto-Refresh (Optional)

Add to `campaign-detail.php` at the bottom:

```php
<?php if (($_ENV['APP_ENV'] ?? 'production') === 'development'): ?>
<script>
// Auto-refresh every 5 seconds
setInterval(() => {
    const hasActiveJobs = <?= json_encode(
        !empty(array_filter($recentJobs, fn($j) => 
            in_array($j['status'], ['pending', 'processing'])
        ))
    ) ?>;
    
    if (hasActiveJobs) {
        location.reload();
    }
}, 5000);
</script>
<?php endif; ?>
```

This will auto-reload the page every 5 seconds if there are active jobs.

---

## 🐛 Debugging Tips

### **Job Stuck in "Pending"**

**Check:**
1. Is worker running? `php worker.php`
2. Check jobs table: `SELECT * FROM jobs WHERE status='pending'`
3. Look for errors in worker terminal

### **Job Failed**

**Check:**
1. Error message in jobs table
2. Apache error log for details
3. API key validity
4. API credit balance

### **No Jobs Showing**

**Check:**
1. `APP_ENV=development` in `.env`
2. Sessions exist for this campaign
3. Database connection working

---

## 💡 Advanced: Add Real-Time WebSockets

For instant updates without refresh:

1. Install Ratchet WebSocket library
2. Broadcast job status changes
3. Update UI via JavaScript

This is beyond MVP scope but could be a future enhancement!

---

## 📊 Performance Impact

**Minimal:**
- Only runs in development mode
- Simple SQL queries (~10ms)
- Cached on page load
- No background polling

**Production:**
- Zero impact (not rendered)
- No queries executed

---

## 🎨 Customization

### **Change Number of Jobs Shown**

In `campaign-detail.php`, line 44:
```php
LIMIT 15  // Change to any number
```

### **Filter Jobs**

Show only failures:
```php
WHERE j.status = 'failed'
```

Show only this campaign:
```php
WHERE s.campaign_id = ?
```

### **Add More Stats**

In the stats query, add:
```php
AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_duration,
MAX(updated_at) as last_processed
```

---

## 📚 Related Files

- **Campaign Detail**: `src/Views/campaign-detail.php`
- **Worker**: `worker.php`
- **Jobs**: `src/Jobs/*.php`
- **Database**: `schema.sql` (jobs table)
- **Documentation**: `DEBUG_AI_WORKFLOW.md`

---

## ✨ What You Get

With this debugging interface, you can:

1. ✅ **Monitor** - See all AI processing in real-time
2. ✅ **Debug** - Identify stuck or failed jobs instantly
3. ✅ **Optimize** - Track processing durations
4. ✅ **Learn** - Understand the workflow visually
5. ✅ **Troubleshoot** - Quick access to hints and logs

---

**Perfect for development and testing!** 🎉

Switch to production mode (`APP_ENV=production`) to hide it from users.
