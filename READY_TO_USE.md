# ✅ System Ready - Next Steps

## 🎉 Configuration Complete!

Your TTRPG Session Recap system is properly configured and ready to use!

### ✅ Verified Working:
- ✅ Environment variables loaded from `c:/laragon/www/.env`
- ✅ Database connected (MySQL 8.4.3)
- ✅ All 7 required tables created
- ✅ Campaign #1 created and ready
- ✅ PHP upload limits: **500M** (perfect for your 193MB file!)
- ✅ Storage directories writable
- ✅ All PHP extensions loaded

---

## 🚀 Start Using the Application

### Step 1: Start the Background Worker

**Important:** The worker must be running to process uploaded sessions!

Open a **new terminal/command prompt** and run:

```powershell
cd c:\laragon\www\ttrpg-recap
php worker.php
```

You should see:
```
Worker started. Listening on queue: default
Press Ctrl+C to stop.
```

**Keep this window open!** The worker processes jobs in the background.

### Step 2: Upload Your First Session

1. **Visit:** `http://ttrpg-recap.test/`

2. **Click:** "Campaigns" → Select your campaign

3. **Click:** "Upload Session"

4. **Drag & Drop** your 193MB audio file
   - It will now upload successfully! ✅
   - Previous limit: 20MB ❌
   - New limit: 500MB ✅

5. **Fill in details:**
   - Session Title: e.g., "Session 1: The Adventure Begins"
   - Session Number: 1
   - Date: (today's date)

6. **Click:** "Upload & Start Processing"

7. **Wait for processing:**
   - Uploading: ~1-2 minutes (193MB)
   - Transcription: ~5-10 minutes (Whisper API)
   - Summarization: ~2-3 minutes (Claude API)
   - **Total:** ~10-15 minutes

### Step 3: Monitor Progress

**Option A: Watch Worker Terminal**
```
[2024-12-15 10:15:22] Processing job #1
Transcribing session 1...
Transcription complete for session 1. Queued summarization.
Job #1 completed successfully

[2024-12-15 10:20:15] Processing job #2
Generating recap for session 1...
Processing 8 entities...
Session 1 processing complete!
Job #2 completed successfully
```

**Option B: Check Campaign Page**

Refresh the campaign page to see session status:
- 🟡 **Pending** → Waiting in queue
- 🔵 **Transcribing** → Whisper is converting audio to text
- 🔵 **Processing** → Claude is generating recap
- 🟢 **Complete** → Ready to view! ✅
- 🔴 **Failed** → Check error message

### Step 4: View Your Recap

Once status shows "Complete":

1. Click "View Recap"

2. You'll see:
   - 📜 **Brief Summary** (2-3 sentences)
   - ✨ **Full Narrative Recap** (500-1500 words)
   - 💬 **Memorable Quotes** (if any)
   - 🎣 **Plot Hooks** (unresolved threads)
   - 📚 **Entities Mentioned** (NPCs, locations, items, etc.)
   - 📄 **Full Transcript** (collapsible)

3. **Export Options:**
   - 📋 Copy to clipboard
   - ⬇️ Download as Markdown

4. **Campaign Wiki:**
   - Return to campaign page
   - See all extracted entities organized by type
   - Builds automatically over time!

---

## 💰 Cost for Your Session

For your 193MB file (~3 hours estimated):

- **Whisper Transcription:** ~$1.08 (180 min × $0.006/min)
- **Claude Summarization:** ~$0.20
- **Total:** ~$1.28 per session

You'll use your own API keys, billed directly by OpenAI and Anthropic.

---

## 🔍 Debugging Tips

### If Upload Fails:

1. **Check browser console** (F12 → Console tab)
   - Shows exact error message
   - Look for `Upload response:` log

2. **Check Apache error log:**
   ```powershell
   Get-Content c:\laragon\bin\apache\logs\error.log -Tail 50
   ```

3. **Re-run diagnostic:**
   ```
   http://ttrpg-recap.test/debug.php
   ```

### If Processing Stalls:

1. **Check worker is running**
   - Terminal should show activity
   - No output for 5+ minutes? Check for errors

2. **Check jobs table:**
   ```sql
   SELECT * FROM ttrpg_recap.jobs ORDER BY id DESC LIMIT 5;
   ```

3. **Check for failed jobs:**
   ```sql
   SELECT * FROM ttrpg_recap.jobs WHERE status='failed';
   ```

---

## 📝 Common Workflows

### Create New Campaign
1. Campaigns → New Campaign
2. Fill in: Name, Game System, Setting Context
3. Add party members (optional but helps AI)

### Upload Multiple Sessions
1. Upload one at a time
2. Worker processes in order
3. Each session takes ~10-15 minutes
4. Can upload next while previous processes

### Export for Discord/Notion
1. View session recap
2. Click "Export MD"
3. Paste in Discord/Notion
4. Beautifully formatted!

---

## 🛠️ Advanced: Run Worker as Service

Instead of keeping terminal open, set up worker to run automatically:

**Option 1: Windows Task Scheduler**
- Run worker on startup
- Restart if crashes
- See: `DEPLOYMENT.md` for details

**Option 2: NSSM (Non-Sucking Service Manager)**
```powershell
# Download NSSM, then:
nssm install TTRPGWorker "C:\laragon\bin\php\php.exe" "C:\laragon\www\ttrpg-recap\worker.php"
nssm start TTRPGWorker
```

---

## 📚 Documentation Reference

- **README.md** - Complete documentation
- **QUICKSTART.md** - 5-minute setup guide
- **TROUBLESHOOTING.md** - All error solutions
- **FIX_UPLOAD_LIMIT.md** - Upload limit configuration
- **DEBUG_UPLOAD.md** - Upload debugging
- **DEPLOYMENT.md** - Production deployment

---

## ✨ You're All Set!

**What you have:**
- ✅ Fully configured system
- ✅ Database ready
- ✅ Upload limits fixed (500MB)
- ✅ Campaign created
- ✅ Ready to process sessions

**What you need to do:**
1. Start worker: `php worker.php` ← **Do this now!**
2. Upload your 193MB file ← **Will now work!**
3. Wait ~15 minutes
4. View epic recap! 🎉

---

**Happy adventuring!** ⚔️🎲📜

Roll for initiative and upload that first session!
