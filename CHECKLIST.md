# Installation & Setup Checklist

## Pre-Installation ✓

- [x] PHP 8.0+ installed
- [x] MySQL/MariaDB installed
- [x] Composer installed
- [x] Laragon running (or similar environment)

## Installation Steps

### 1. Dependencies ✓
```bash
cd c:/laragon/www/ttrpg-recap
composer install
```
- [x] Composer dependencies installed
- [x] Vendor directory created

### 2. Environment Configuration

**IMPORTANT: .env file is located at `c:/laragon/www/.env` (parent directory)**

- [ ] Copy `.env.example` to parent directory:
  ```bash
  cp .env.example ../.env
  ```
  
- [ ] Edit `c:/laragon/www/.env` with:
  - [ ] `OPENAI_API_KEY=sk-...` (Get from https://platform.openai.com/api-keys)
  - [ ] `ANTHROPIC_API_KEY=sk-ant-...` (Get from https://console.anthropic.com/settings/keys)
  - [ ] `DB_NAME=ttrpg_recap`
  - [ ] `DB_USER=root` (or your MySQL user)
  - [ ] `DB_PASS=your_password`

### 3. Database Setup

- [ ] Create database:
  ```bash
  mysql -u root -p < schema.sql
  ```
  Or via phpMyAdmin:
  - [ ] Import `schema.sql`
  
- [ ] Verify tables created:
  - [ ] users
  - [ ] campaigns
  - [ ] sessions
  - [ ] transcripts
  - [ ] recaps
  - [ ] entities
  - [ ] session_entities
  - [ ] campaign_characters
  - [ ] jobs

### 4. PHP Configuration (if needed)

Check `php.ini` for upload limits:
- [ ] `upload_max_filesize = 500M`
- [ ] `post_max_size = 500M`
- [ ] `max_execution_time = 300`
- [ ] `memory_limit = 256M`

Restart web server if changed.

### 5. File Permissions

- [ ] `storage/audio/` is writable
- [ ] `storage/narrations/` is writable

### 6. Test Access

- [ ] Open http://localhost/ttrpg-recap
- [ ] Dashboard loads correctly
- [ ] No PHP errors in browser

## First Use

### 1. Start Background Worker

**CRITICAL: Worker must be running for processing!**

Open a terminal/command prompt:
```bash
cd c:/laragon/www/ttrpg-recap
php worker.php
```

Leave this window open! You should see:
```
Worker started. Listening on queue: default
Press Ctrl+C to stop.
```

### 2. Create First Campaign

- [ ] Click "Campaigns" → "New Campaign"
- [ ] Fill in:
  - [ ] Name: "Test Campaign"
  - [ ] Game System: "D&D 5e" (or your system)
  - [ ] Setting Context: Detailed campaign description
- [ ] Click "Create Campaign"
- [ ] Campaign appears in list

### 3. Upload Test Session

**Use a SHORT audio file (2-5 minutes) for first test!**

- [ ] Click campaign → "Upload Session"
- [ ] Drag & drop audio file (or click to browse)
- [ ] Supported formats: MP3, WAV, M4A, WebM, MP4
- [ ] Fill in title and session number
- [ ] Click "Upload & Start Processing"
- [ ] See success message

### 4. Monitor Processing

Watch the worker terminal - you should see:
```
Processing job #1
Transcribing session 1...
Transcription complete for session 1. Queued summarization.
Job #1 completed successfully

Processing job #2
Generating recap for session 1...
Processing 5 entities...
Session 1 processing complete!
Job #2 completed successfully
```

Processing time:
- 2-minute audio: ~1-3 minutes total
- 30-minute audio: ~3-5 minutes
- 3-hour audio: ~10-15 minutes

### 5. View Results

- [ ] Return to campaign page
- [ ] Session status shows "Complete"
- [ ] Click "View Recap"
- [ ] See:
  - [ ] Brief summary
  - [ ] Full narrative recap
  - [ ] Memorable quotes (if any)
  - [ ] Plot hooks
  - [ ] Extracted entities
- [ ] Campaign wiki updated with entities

### 6. Test Export

- [ ] Click "Export MD" button
- [ ] Markdown file downloads
- [ ] Open in text editor - formatted correctly

## Troubleshooting

### Upload Fails
- [ ] Check PHP upload limits in `php.ini`
- [ ] Verify file is audio format
- [ ] Check file size < 500MB
- [ ] Check `storage/audio/` exists and is writable

### Jobs Not Processing
- [ ] Worker is running? Check terminal
- [ ] Check jobs table: `SELECT * FROM jobs WHERE status='pending'`
- [ ] Look for errors in worker terminal
- [ ] Check `.env` file location (should be in parent directory)

### API Errors
- [ ] Verify API keys in `../.env`
- [ ] Check OpenAI account has credits
- [ ] Check Anthropic account has credits
- [ ] Test API keys independently

### Database Connection Error
- [ ] MySQL is running
- [ ] Credentials in `../.env` are correct
- [ ] Database `ttrpg_recap` exists
- [ ] User has permissions

### Blank/White Page
- [ ] Check PHP error log
- [ ] Enable error display temporarily:
  ```php
  // In public/index.php, add at top:
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

## Cost Verification

After first test session:
- [ ] Note processing time
- [ ] Check OpenAI usage dashboard
- [ ] Check Anthropic usage dashboard
- [ ] Verify cost matches estimate (~$0.05 for 2-min test)

## Production Readiness

Before using with real campaigns:

### Security
- [ ] Change default user password in database
- [ ] Consider implementing authentication
- [ ] Review file upload security
- [ ] Set up regular database backups

### Monitoring
- [ ] Set up log rotation
- [ ] Monitor disk space (audio files accumulate)
- [ ] Consider storage cleanup policy
- [ ] Monitor API usage/costs

### Worker Management
- [ ] Set up worker as system service (see DEPLOYMENT.md)
- [ ] Configure auto-restart on failure
- [ ] Set up logging

## Regular Maintenance

Weekly:
- [ ] Check worker is running
- [ ] Monitor disk space
- [ ] Review failed jobs

Monthly:
- [ ] Database backup
- [ ] Clear old completed jobs
- [ ] Review API costs
- [ ] Check for storage cleanup

## Support Resources

- Full docs: `README.md`
- Quick start: `QUICKSTART.md`
- Production setup: `DEPLOYMENT.md`
- Project overview: `PROJECT_SUMMARY.md`

## Success Criteria ✅

You're ready when:
- [x] Application loads at http://localhost/ttrpg-recap
- [x] Worker is running and processing jobs
- [x] Test upload completes successfully
- [x] Recap is readable and makes sense
- [x] Entities are extracted correctly
- [x] Export works

**Roll for initiative!** 🎲⚔️

---

*Last updated: 2025-12-15*
