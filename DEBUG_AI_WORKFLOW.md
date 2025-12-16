# AI Workflow Debugging Guide

## Overview

The TTRPG Recap system now includes comprehensive debugging for the entire AI processing pipeline. When `APP_ENV=development`, you'll see detailed logs for every step.

---

## 🔍 What Gets Logged

### **1. Transcription (Whisper API)**

**Worker Terminal Output:**
```
[10:15:30] 🎙️  Starting transcription for session 1
[10:15:30] 📄 Session: Episode 1: The Adventure Begins
[10:15:30] 📦 File size: 193.45 MB
[10:15:30] 🚀 Sending to Whisper API...
[10:15:45] ✅ Transcription received in 15.23s
[10:15:45] 📝 Transcript: 45,230 characters, 8,150 words
[10:15:45] 💾 Transcript saved to database
[10:15:45] ✨ Transcription job complete in 15.45s. Queued summarization.
```

**Error Log (c:/laragon/bin/apache/logs/error.log):**
```
=== WHISPER TRANSCRIPTION START ===
Audio file: C:\laragon\www\ttrpg-recap\storage\audio\session_abc123.mp3
File size: 193.45 MB
Sending request to Whisper API...
Whisper API response received in 15.23 seconds
Response status: 200
Transcription length: 45230 characters
Segments count: 142
Duration: 5430 seconds
Estimated cost: $0.54
=== WHISPER TRANSCRIPTION COMPLETE ===
```

### **2. Summarization (Claude API)**

**Worker Terminal Output:**
```
[10:16:00] ✨ Starting summarization for session 1
[10:16:00] 📄 Session: Episode 1: The Adventure Begins
[10:16:00] 📝 Transcript: 45,230 characters, 8,150 words
[10:16:00] 🏰 Campaign: Curse of Strahd
[10:16:00] 👥 Party: 4 characters
[10:16:00] 🤖 Sending to Claude API...
[10:16:25] ✅ Recap received in 25.34s
[10:16:25] 📖 Narrative: 1,247 characters
[10:16:25] 💬 Quotes: 3
[10:16:25] 🎣 Plot hooks: 2
[10:16:25] 💾 Recap saved to database
[10:16:25] 📚 Processing 8 entities...
[10:16:25] 🆕 New entities: 6
[10:16:25] 🔄 Updated entities: 2
[10:16:25] 👤 npc: 4
[10:16:25] 📍 location: 2
[10:16:25] ⚔️  item: 2
[10:16:25] 🎉 Session 1 processing complete in 25.67s!
============================================================
```

**Error Log:**
```
=== CLAUDE SUMMARIZATION START ===
Transcript length: 45230 characters
Word count: 8150
Campaign context: ["setting_context","characters"]
Prompt length: 46789 characters
Estimated input tokens: 11,697
Sending request to Claude API...
Claude API response received in 25.34 seconds
Response status: 200
Model used: claude-sonnet-4-20250514
Stop reason: end_turn
Token usage:
  Input: 11,842
  Output: 1,523
  Estimated cost: $0.0584
Response content length: 6,543 characters
Extracted JSON length: 6,498 characters
Recap parsed successfully:
  Narrative length: 1247 characters
  Quotes: 3
  Plot hooks: 2
  Entities: 8
  Entity breakdown: {"npc":4,"location":2,"item":2}
=== CLAUDE SUMMARIZATION COMPLETE ===
```

---

## 📊 Understanding the Output

### **Timing Information**

| Metric | Typical Range | What It Means |
|--------|---------------|---------------|
| **Whisper API** | 10-60s | Depends on file size, not duration |
| **Claude API** | 15-45s | Depends on transcript length |
| **Total Processing** | 30-120s | Combined time + database operations |

### **Cost Tracking**

Both services log estimated costs:
- **Whisper**: Based on audio duration (minutes)
- **Claude**: Based on actual token usage

### **Entity Breakdown**

Icons help identify types at a glance:
- 👤 **NPC** - Characters met in session
- 📍 **Location** - Places visited
- ⚔️ **Item** - Objects obtained/mentioned
- 🏛️ **Faction** - Organizations/groups
- 📅 **Event** - Significant occurrences

---

## 🐛 Debugging Common Issues

### **Issue: Whisper API Times Out**

**Symptoms:**
```
[10:15:30] 🚀 Sending to Whisper API...
[10:20:30] ❌ Transcription failed after 300s
[10:20:30] 🔴 Error: cURL error 28: Operation timed out
```

**Causes:**
- File too large (> 25MB requires chunking)
- Network issues
- OpenAI API rate limits

**Solutions:**
```php
// Check error log for:
Whisper API error: cURL error 28...
Response body: {"error":{"message":"..."}}

// Fix:
1. Reduce file size (compress audio)
2. Check internet connection
3. Verify API key and quota
```

### **Issue: Claude Returns Invalid JSON**

**Symptoms:**
```
[10:16:25] ❌ Summarization failed
[10:16:25] 🔴 Error: Failed to parse Claude response as JSON: Syntax error
```

**Error Log Shows:**
```
JSON decode failed!
Raw content: The session began with the party...
JSON error: Syntax error
```

**Cause:** Claude sometimes adds text before/after JSON

**Solution:** The system automatically extracts JSON from markdown blocks, but if it fails:
1. Check error log for "Raw content"
2. See if Claude wrapped response in explanation
3. Improve prompt clarity (already handled in code)

### **Issue: No Entities Extracted**

**Symptoms:**
```
[10:16:25] ℹ️  No entities extracted
```

**Causes:**
- Short/simple session
- Minimal NPC interaction
- Claude didn't identify notable entities

**This is normal** for some sessions. Not every session has new entities.

### **Issue: API Key Invalid**

**Symptoms:**
```
[10:15:30] ❌ Transcription failed
[10:15:30] 🔴 Error: Whisper API error: HTTP 401 Unauthorized
```

**Error Log:**
```
Whisper API error: HTTP 401
Error response: {"error":{"message":"Invalid API key","type":"invalid_request_error"}}
```

**Solution:**
1. Check `.env` file: `c:/laragon/www/.env`
2. Verify API key is correct
3. Check API key has not expired
4. Verify account has credits

---

## 📝 Log File Locations

### **Worker Output** (Real-time)
- Visible in terminal running `php worker.php`
- Shows emoji icons and progress

### **Error Log** (Detailed debugging)
- **Windows (Laragon):** `c:/laragon/bin/apache/logs/error.log`
- **Linux:** `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- Contains full API request/response details

### **Viewing Logs in Real-Time**

**PowerShell (Windows):**
```powershell
# Tail Apache error log
Get-Content c:\laragon\bin\apache\logs\error.log -Wait -Tail 50
```

**Bash (Linux):**
```bash
# Tail error log
tail -f /var/log/apache2/error.log

# Grep for specific session
tail -f /var/log/apache2/error.log | grep "session 1"
```

---

## 🔬 Testing AI Workflow

### **1. Test with Short File**

Create a 30-second test file to verify the pipeline:

```bash
# Expected processing time: ~2-3 minutes
# Expected cost: ~$0.01

# Watch for:
- Upload completes
- Job appears in queue
- Worker picks it up
- Whisper transcribes (fast with short file)
- Claude summarizes
- Status → complete
```

### **2. Monitor Database**

```sql
-- Check job progression
SELECT id, queue, status, attempts, created_at 
FROM jobs 
ORDER BY id DESC LIMIT 5;

-- Check session status
SELECT id, title, status, error_message 
FROM sessions 
ORDER BY id DESC;

-- View transcript
SELECT session_id, LENGTH(raw_text) as length, word_count 
FROM transcripts;
```

### **3. API Response Validation**

The system validates responses:

**Whisper Response:**
```php
// Must contain:
- 'text': Full transcript
- 'segments': Array of timestamped segments
- 'duration': Audio duration in seconds
```

**Claude Response:**
```php
// Must be valid JSON with:
- 'narrative_recap': String
- 'brief_summary': String
- 'memorable_quotes': Array
- 'plot_hooks': Array
- 'entities': Array
```

If validation fails, you'll see in error log exactly what was received.

---

## 📊 Performance Benchmarks

### **Typical Processing Times**

| File Size | Duration | Est. Processing | Est. Cost |
|-----------|----------|-----------------|-----------|
| 10MB | 15 min | 2-3 min | $0.15 |
| 50MB | 1 hour | 5-7 min | $0.50 |
| 100MB | 2 hours | 8-12 min | $0.90 |
| 193MB | 3.5 hours | 12-18 min | $1.50 |

**Breakdown:**
- **Upload**: 1-3% of total (depends on connection)
- **Whisper**: 40-50% of total
- **Claude**: 50-60% of total

---

## 🎯 Debug Checklist

When a session fails:

- [ ] Check worker terminal output
- [ ] Read error message: `[HH:MM:SS] 🔴 Error: ...`
- [ ] Open Apache error log
- [ ] Look for `=== WHISPER` or `=== CLAUDE` sections
- [ ] Check API response status codes
- [ ] Verify token counts and costs
- [ ] Check database for session status
- [ ] Review extracted entities (if got to that step)

---

## 🔧 Advanced Debugging

### **Enable Maximum Verbosity**

Already enabled with `APP_ENV=development`!

All debug logging automatically activates.

### **Simulate API Calls**

Test without using API credits:

```php
// Add to WhisperService::transcribe() for testing:
return [
    'text' => 'This is a test transcript...',
    'segments' => [],
    'duration' => 300,
];
```

### **Monitor Network Requests**

Use browser DevTools Network tab to monitor:
- Session upload
- Status polling
- Export requests

Worker API calls are logged to error log.

---

## 📖 Related Documentation

- **READY_TO_USE.md** - First upload walkthrough
- **TROUBLESHOOTING.md** - General error solutions
- **FIX_UPLOAD_LIMIT.md** - PHP configuration
- **DEBUG_UPLOAD.md** - Upload-specific debugging

---

## ✅ Verification

Your AI workflow is working correctly when you see:

```
[10:15:30] 🎙️  Starting transcription for session 1
...
[10:15:45] ✨ Transcription job complete in 15.45s
[10:16:00] ✨ Starting summarization for session 1
...
[10:16:25] 🎉 Session 1 processing complete in 25.67s!
============================================================
```

No red ❌ or 🔴 icons = Success! 🎉

---

**Happy debugging!** The detailed logs make it easy to track exactly what's happening at every step of the AI processing pipeline.
