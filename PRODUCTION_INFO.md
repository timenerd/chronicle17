# Chronicle Production Server Information

## Live Site
🌐 **URL**: https://iamrlw.com/ttrpg-recap

## Server Details
- **Host**: iamrlw.com
- **Path**: `/home/iamrlw/public_html/ttrpg-recap`
- **Environment**: Production
- **Database**: `iamrlw_ttrpg_recap`

## Configuration
Location of `.env` file: `/home/iamrlw/public_html/.env`

```env
# OpenAI API Key (for Whisper transcription)
OPENAI_API_KEY=sk-proj-your-actual-key-here

# Anthropic API Key (for Claude AI recap generation)
ANTHROPIC_API_KEY=sk-ant-your-actual-key-here

# Database Configuration
DB_HOST=localhost
DB_NAME=iamrlw_ttrpg_recap
DB_USER=iamrlw_dbuser
DB_PASS=your-secure-password

# Application Environment
APP_ENV=production

# Base URL
BASE_URL=https://iamrlw.com/ttrpg-recap
```

## Deployment Commands

### Update from GitHub
```bash
cd /home/iamrlw/public_html/ttrpg-recap
git pull origin main
composer install --no-dev --optimize-autoloader
```

### Restart Background Worker
```bash
# If using systemd
sudo systemctl restart ttrpg-worker

# If using screen
screen -r ttrpg-worker
# Ctrl+C to stop, then restart: php worker.php
```

### Check Status
```bash
# View worker logs
sudo journalctl -u ttrpg-worker -f

# Check recent jobs
mysql -u iamrlw_dbuser -p iamrlw_ttrpg_recap -e "SELECT id, type, status, created_at FROM jobs ORDER BY created_at DESC LIMIT 10;"
```

## Important URLs
- **Main App**: https://iamrlw.com/ttrpg-recap
- **Debug Page**: https://iamrlw.com/ttrpg-recap/debug.php (remove in production!)

## Documentation
- Full deployment guide: `PRODUCTION_DEPLOY.md`
- Troubleshooting: `TROUBLESHOOTING.md`
- Local setup: `README.md`

---

Last updated: 2025-12-16
