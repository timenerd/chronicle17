# Quick Start Guide

## 5-Minute Setup

### 1. Prerequisites Check
```bash
php -v    # Should be 8.0+
mysql -V  # Should be installed
composer -V  # Should be installed
```

### 2. Download & Install
```bash
cd c:/laragon/www/ttrpg-recap
composer install
cp .env.example ../.env  # .env goes in parent directory for security
```

### 3. Configure API Keys
Edit `../.env` (in parent directory, outside web root):
```env
OPENAI_API_KEY=sk-your-key-here
ANTHROPIC_API_KEY=sk-ant-your-key-here
```

Get API keys:
- OpenAI: https://platform.openai.com/api-keys
- Anthropic: https://console.anthropic.com/settings/keys

### 4. Setup Database
```bash
# Option 1: CLI
mysql -u root -p < schema.sql

# Option 2: phpMyAdmin
# Import schema.sql via phpMyAdmin interface
```

### 5. Start Worker
```bash
php worker.php
```
Keep this terminal open!

### 6. Access Application
Open browser: `http://localhost/ttrpg-recap`

## First Campaign

1. **Create Campaign**
   - Click "Campaigns" → "New Campaign"
   - Name: "Test Campaign"
   - Game System: "D&D 5e"
   - Setting Context: "A high fantasy adventure in the Forgotten Realms"

2. **Add Characters** (Optional)
   - Navigate to campaign
   - Add party members for better AI context

3. **Upload Session**
   - Click "Upload Session"
   - Drag & drop an audio file
   - Fill in details
   - Submit

4. **Wait for Processing**
   - Transcription: ~3-5 minutes
   - Summarization: ~1-2 minutes
   - Total: ~5-15 minutes depending on length

5. **View Recap**
   - Click on completed session
   - Read narrative recap
   - Explore extracted entities
   - Export to Markdown

## Tips for Best Results

### Audio Quality
- ✅ Clear speech, moderate background noise okay
- ✅ MP3 at 128kbps+ recommended
- ❌ Avoid heavy distortion or very low volume

### Setting Context
Provide detailed campaign context to help AI:
```
A dark fantasy campaign set in Ravenloft. The tone is gothic horror
with heavy emphasis on moral choices. Recurring themes include 
redemption, sacrifice, and the corruption of power. Key NPCs speak 
in formal, archaic language.
```

### Character Information
Add main PCs with:
- Name
- Race/Class
- Player name
- Brief personality note

This helps Claude identify speakers and write better recaps!

## Troubleshooting

### "Upload failed"
- Check `php.ini` upload limits:
  ```ini
  upload_max_filesize = 500M
  post_max_size = 500M
  ```

### "Jobs not processing"
- Ensure worker is running: `php worker.php`
- Check `jobs` table in database for errors

### "API Error"
- Verify API keys in `.env`
- Check API account has credits
- Review error message in session details

## Cost Management

### Test Mode
Use shorter clips (5-10 min) for testing:
- Cost: ~$0.05 per test
- Full validation without spending much

### Batch Processing
Process multiple sessions together:
- Worker handles queue automatically
- Efficient API usage

## Production Deployment

For production use, see `DEPLOYMENT.md` for:
- Proper authentication
- Supervisord setup
- Nginx configuration
- S3 storage integration
- Rate limiting
- Monitoring

---

**Need help?** Check `README.md` for full documentation!
