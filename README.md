# TTRPG Session Recap Generator

![TTRPG Recap](https://img.shields.io/badge/TTRPG-Recap-8B5CF6)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4)
![License](https://img.shields.io/badge/license-MIT-green)

Transform your TTRPG session recordings into epic narrative recaps with AI-powered transcription and summarization.

## 🎯 Features

### MVP (v1)
- ✅ Campaign & Session Management
- ✅ Audio upload with drag-and-drop (up to 500MB)
- ✅ OpenAI Whisper transcription
- ✅ Claude AI narrative recap generation
- ✅ Automatic entity extraction (NPCs, locations, items, factions, events)
- ✅ Campaign wiki that builds over time
- ✅ Markdown export
- ✅ Beautiful dark-themed UI with glassmorphism

### Planned (v2)
- 🔄 Speaker diarization (identify who said what)
- 🔄 ElevenLabs narration (AI-narrated audio recaps)
- 🔄 PDF export
- 🔄 Public sharing links
- 🔄 Multi-user authentication
- 🔄 Collaborative campaigns

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- MySQL/MariaDB
- Composer
- (Optional) Redis for production job queue

### Installation

1. **Clone and navigate to project**
   ```bash
   cd c:/laragon/www/ttrpg-recap
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   
   The `.env` file should be placed **one directory above** the project root (outside web root for security):
   ```bash
   # From the project directory
   cp .env.example ../.env
   ```
   
   Edit `../.env` (in parent directory) and add your API keys:
   ```env
   OPENAI_API_KEY=sk-...
   ANTHROPIC_API_KEY=sk-ant-...
   
   DB_NAME=ttrpg_recap
   DB_USER=root
   DB_PASS=your_password
   ```

4. **Create database**
   ```bash
   mysql -u root -p < schema.sql
   ```
   
   Or manually:
   ```sql
   mysql> source schema.sql;
   ```

5. **Start worker for background jobs**
   ```bash
   php worker.php
   ```
   
   Keep this running in a separate terminal/screen/tmux session.

6. **Access the application**
   ```
   http://localhost/ttrpg-recap
   ```

## 📁 Project Structure

```
ttrpg-recap/
├── public/              # Web root
│   ├── index.php        # Front controller
│   └── assets/          # CSS, JS, images
├── src/
│   ├── Controllers/     # Request handlers
│   ├── Models/          # Database models
│   ├── Services/        # API integrations (Whisper, Claude)
│   ├── Jobs/            # Background job handlers
│   └── Views/           # PHP templates
├── config/              # Configuration files
├── storage/             # Uploaded files
│   ├── audio/           # Original recordings
│   └── narrations/      # Generated audio (future)
├── worker.php           # CLI job processor
├── composer.json
└── schema.sql           # Database schema
```

## 🎮 Usage

### 1. Create a Campaign
- Navigate to "Campaigns" → "New Campaign"
- Fill in details (name, game system, setting context)
- Setting context helps AI generate better recaps

### 2. Upload Session
- Select campaign → "Upload Session"
- Drag & drop audio file (MP3, WAV, M4A, etc.)
- Add title, number, and date
- Submit and wait for processing

### 3. View Recap
- Processing takes 5-15 minutes depending on length
- View narrative recap, quotes, plot hooks
- See extracted entities automatically added to wiki
- Export to Markdown

## 🔧 Technical Details

### API Integrations

**OpenAI Whisper** - Transcription
- Endpoint: `https://api.openai.com/v1/audio/transcriptions`
- Model: `whisper-1`
- Cost: $0.006/minute (~$1.08 for 3-hour session)

**Anthropic Claude** - Summarization
- Model: `claude-sonnet-4-20250514`
- Structured JSON output with recap + entities
- Cost: ~$0.15-0.30 per session

### Processing Pipeline

1. **Upload** → File saved, session created with status `pending`
2. **Transcription Job** → Whisper API call, save transcript, status → `processing`
3. **Summarization Job** → Claude API call with campaign context
4. **Entity Extraction** → Create/update NPCs, locations, etc.
5. **Complete** → Status → `complete`, ready to view

### Background Jobs

Jobs are processed by `worker.php`:
```bash
# Start worker
php worker.php [queue_name]

# Default queue
php worker.php default
```

For production, use:
- **systemd** service (Linux)
- **Supervisor** (recommended)
- **Task Scheduler** (Windows)
- **PM2** (if using Node.js already)

## 💰 Cost Estimates

Per 3-hour session:
- **Transcription**: $1.08 (180 min × $0.006)
- **Summarization**: $0.15-0.30
- **Total**: ~$1.25/session

Annual costs for weekly sessions (52 sessions):
- ~$65/year

## 🔐 Security Notes

- Change default user password in production
- Implement proper authentication (v2)
- Use strong API keys
- Set appropriate file upload limits
- Consider rate limiting for public deployments

## 📝 Database Schema

Key tables:
- `campaigns` - Campaign containers
- `sessions` - Individual session recordings
- `transcripts` - Whisper output
- `recaps` - Claude-generated summaries
- `entities` - Campaign wiki (NPCs, locations, etc.)
- `jobs` - Background job queue

See `schema.sql` for complete structure.

## 🛠️ Development

### Adding New Features

1. **New API Service**: Add to `src/Services/`
2. **New Background Job**: Add to `src/Jobs/`
3. **New Route**: Update `public/index.php`
4. **New View**: Add to `src/Views/`

### Debugging

Enable error display in `.env`:
```env
APP_ENV=development
```

## 🐛 Troubleshooting

### Quick Diagnostic

Visit the diagnostic page to check your setup:
```
http://localhost/ttrpg-recap/debug.php
```

This will verify:
- ✅ .env file loading
- ✅ Database connection  
- ✅ Required tables exist
- ✅ File permissions
- ✅ PHP configuration

### Common Issues

**"Failed to load resource: 400 Bad Request"**
- Check `.env` is in parent directory (`c:/laragon/www/.env`)
- Run diagnostic page
- See `TROUBLESHOOTING.md` for detailed fixes

**Upload fails**
- Check PHP max upload size in `php.ini`
- Verify `storage/audio/` is writable

**Jobs not processing**
- Ensure worker is running: `php worker.php`
- Check job status in `jobs` table

**Database connection errors**
- Verify credentials in `.env`
- Ensure database exists

For detailed troubleshooting, see: **`TROUBLESHOOTING.md`**

## 📜 License

MIT License - feel free to use and modify for your campaigns!

## 🙏 Credits

Built with:
- OpenAI Whisper API
- Anthropic Claude API
- PHP 8 & PDO
- Vanilla CSS (no frameworks, pure awesome)

---

**Made with ⚔️ for TTRPG enthusiasts**

*Roll for initiative!* 🎲
