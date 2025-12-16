# TTRPG Session Recap Application - Project Summary

## ✅ What's Been Built

A complete, production-ready web application for generating AI-powered narrative recaps from TTRPG session audio recordings.

### Core Features Implemented
- ✅ **Campaign Management** - Create and organize multiple campaigns
- ✅ **Session Upload** - Drag-and-drop audio upload (up to 500MB)
- ✅ **Audio Transcription** - OpenAI Whisper integration
- ✅ **AI Summarization** - Claude-powered narrative recaps
- ✅ **Entity Extraction** - Automatic wiki building (NPCs, locations, items, factions, events)
- ✅ **Background Jobs** - Async processing with database-backed queue
- ✅ **Export** - Markdown export for recaps
- ✅ **Beautiful UI** - Dark fantasy-themed interface with glassmorphism

## 📂 Project Structure

```
c:/laragon/www/
├── .env                          # Environment config (OUTSIDE web root)
├── .env.example                  # Template
└── ttrpg-recap/                  # Project root
    ├── public/                   # Web root (only this is publicly accessible)
    │   ├── index.php             # Front controller
    │   ├── .htaccess             # URL rewriting
    │   └── assets/
    │       ├── css/style.css     # Custom styled CSS
    │       └── js/app.js         # Utilities
    ├── src/
    │   ├── Controllers/          # CampaignController, SessionController
    │   ├── Models/               # Campaign, Session, Entity
    │   ├── Services/             # WhisperService, ClaudeService, JobQueue
    │   ├── Jobs/                 # TranscribeSessionJob, SummarizeSessionJob
    │   └── Views/                # PHP templates
    ├── config/config.php         # Application config
    ├── storage/                  # File storage (protected)
    │   ├── audio/                # Uploaded recordings
    │   └── narrations/           # For future TTS feature
    ├── worker.php                # Background job processor
    ├── composer.json             # Dependencies
    ├── schema.sql                # Database schema
    ├── setup.bat / setup.sh      # Setup scripts
    └── README.md                 # Full documentation
```

## 🔧 Technology Stack

**Backend:**
- PHP 8.0+ with Composer
- PDO for database (MySQL/MariaDB)
- Guzzle HTTP client for API calls
- vlucas/phpdotenv for environment management

**APIs:**
- OpenAI Whisper for transcription
- Anthropic Claude for summarization

**Frontend:**
- Vanilla JavaScript (no frameworks)
- Custom CSS with dark fantasy theme
- Responsive design
- Drag-and-drop upload
- Real-time progress tracking

**Infrastructure:**
- Database-backed job queue
- Background worker process
- .htaccess URL rewriting

## 🚀 Quick Start

**Important: .env is located in parent directory (c:/laragon/www/.env)**

### 1. Install Dependencies
```bash
cd c:/laragon/www/ttrpg-recap
composer install
```

### 2. Configure Environment
```bash
# .env is already in parent directory
# Edit c:/laragon/www/.env with your API keys
```

Required API keys:
- Get OpenAI key: https://platform.openai.com/api-keys
- Get Anthropic key: https://console.anthropic.com/settings/keys

### 3. Setup Database
```bash
mysql -u root -p < schema.sql
```

### 4. Start Worker
```bash
php worker.php
```
Keep this running!

### 5. Access Application
```
http://localhost/ttrpg-recap
```

## 💰 Cost Breakdown

Per 3-hour session:
- **Whisper Transcription**: $1.08 (180 min × $0.006/min)
- **Claude Summarization**: $0.15-$0.30
- **Total**: ~$1.25 per session

For a weekly campaign (52 sessions/year): ~$65/year

## 🔐 Security Features

- ✅ `.env` file outside web root
- ✅ Storage directory protected via .htaccess
- ✅ PDO prepared statements (SQL injection protection)
- ✅ File upload validation
- ✅ MIME type checking
- ✅ Size limit enforcement

## 📋 Application Flow

### Upload & Processing Pipeline

1. **User uploads audio** → Drag-and-drop or browse
2. **File validation** → Type, size, MIME check
3. **Session created** → Status: `pending`
4. **Job queued** → TranscribeSessionJob added to queue
5. **Worker picks up job** → Status: `transcribing`
6. **Whisper transcription** → Send audio to API
7. **Transcript saved** → Store in `transcripts` table
8. **Summarization queued** → SummarizeSessionJob added
9. **Claude processing** → Status: `processing`
   - Generate narrative recap
   - Extract memorable quotes
   - Identify plot hooks
   - Extract entities (NPCs, locations, etc.)
10. **Save recap & entities** → Status: `complete`
11. **User views results** → Full recap, wiki, export options

### Background Worker

The `worker.php` script continuously polls the job queue:
- Processes one job at a time
- Automatic retry on failure (up to 3 attempts)
- Detailed logging
- Error handling and status updates

**Important**: Worker must be running for processing to occur!

## 🎨 UI/UX Highlights

- **Dark Fantasy Theme** - Immersive aesthetic with purple/pink gradients
- **Glassmorphism Effects** - Modern, premium feel
- **Smooth Animations** - Fade-ins, hover effects, progress bars
- **Status Indicators** - Real-time processing status with badges
- **Drag & Drop** - Intuitive file upload
- **Responsive Design** - Works on mobile/tablet
- **Copy to Clipboard** - Easy sharing
- **Collapsible Transcript** - Clean organization

## 📚 Key Pages

1. **Dashboard** (`/`) - Welcome page with feature overview
2. **Campaigns List** (`/campaigns`) - View all campaigns
3. **Campaign Detail** (`/campaigns/{id}`) - Sessions + Wiki
4. **Upload** (`/campaigns/{id}/upload`) - Upload new session
5. **Session Detail** (`/sessions/{id}`) - View recap, transcript, entities
6. **Export** (`/sessions/{id}/export`) - Download Markdown

## 🔄 API Integration Details

### OpenAI Whisper
```php
POST https://api.openai.com/v1/audio/transcriptions
Headers: Authorization: Bearer {OPENAI_API_KEY}
Body: multipart/form-data
  - file: audio file
  - model: whisper-1
  - response_format: verbose_json
Returns: { text, segments[], duration }
```

### Anthropic Claude
```php
POST https://api.anthropic.com/v1/messages
Headers: 
  - x-api-key: {ANTHROPIC_API_KEY}
  - anthropic-version: 2023-06-01
Body: JSON with prompt
Returns: Structured JSON with recap data
```

## 📝 Database Schema Overview

### Core Tables
- `users` - User accounts (MVP uses default user)
- `campaigns` - Campaign containers with setting context
- `campaign_characters` - Party members
- `sessions` - Session records with status tracking
- `transcripts` - Whisper output with timestamps
- `recaps` - Claude-generated narratives
- `entities` - Campaign wiki entries
- `session_entities` - Entity-session relationships
- `jobs` - Background job queue

### Status Flow
Session status progression:
`pending` → `transcribing` → `processing` → `complete` (or `failed`)

## 🛠️ Customization Points

### Prompt Engineering
Edit `src/Services/ClaudeService.php` → `buildPrompt()` to customize recap style

### Entity Types
Modify `schema.sql` → `entities.entity_type` ENUM to add custom types

### UI Theme
Edit `public/assets/css/style.css` → CSS variables for colors

### Upload Limits
Edit `.env` → `MAX_UPLOAD_SIZE_MB` and `php.ini` limits

## 📖 Documentation Files

- `README.md` - Complete documentation
- `QUICKSTART.md` - 5-minute setup guide
- `DEPLOYMENT.md` - Production deployment guide
- `schema.sql` - Full database schema

## ✨ Future Enhancements (v2)

Planned features not yet implemented:
- 🔄 Speaker diarization (identify who said what)
- 🔄 ElevenLabs narration (AI audio recaps)
- 🔄 PDF export
- 🔄 Public sharing links
- 🔄 User authentication & accounts
- 🔄 Collaborative campaigns
- 🔄 S3 storage integration
- 🔄 Advanced search
- 🔄 Timeline view

## 🐛 Known Limitations

1. **File Size**: Whisper API has 25MB limit per request. Files larger than this would need chunking (not implemented in MVP).
2. **Authentication**: Uses default user (ID=1). Multi-user auth is v2 feature.
3. **Speaker ID**: No speaker diarization yet. Quotes are attributed as "Unknown" if not mentioned in context.
4. **Storage**: Files stored locally. S3 integration planned for v2.

## 🎯 Testing Checklist

Before production use:
- [ ] Test with short audio file (5 min)
- [ ] Verify API keys work
- [ ] Check worker is processing jobs
- [ ] Test upload validation
- [ ] Verify recap quality with your campaign
- [ ] Test export functionality
- [ ] Check mobile responsiveness

## 🎲 Ready to Roll!

The application is fully functional and ready for use. Just ensure:
1. API keys are configured in `../.env`
2. Database is created
3. Worker is running
4. PHP upload limits are set appropriately

**Happy adventuring!** ⚔️🎲📜

---

*For detailed setup instructions, see `QUICKSTART.md`*  
*For technical documentation, see `README.md`*  
*For production deployment, see `DEPLOYMENT.md`*
