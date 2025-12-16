# Fix for Asset Loading in Subdirectory Installation

## Problem
When deploying to `https://iamrlw.com/ttrpg-recap`, assets were loading from the wrong path:
- ❌ Loading: `https://iamrlw.com/assets/css/style.css` (404 error)  
- ✅ Should be: `https://iamrlw.com/ttrpg-recap/assets/css/style.css`

## Solution
Added automatic subdirectory detection to `public/index.php` with helper functions:

### Changes Made

1. **`public/index.php`** - Added base path detection and helper functions:
   - `BASE_PATH` constant - Auto-detects subdirectory from URL
   - `asset($path)` - Generates correct asset URLs
   - `route($path)` - Generates correct navigation URLs

2. **`src/Views/layout.php`** - Updated to use helper functions:
   - CSS link: `<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">`
   - JS script: `<script src="<?= asset('assets/js/app.js') ?>"></script>`
   - Navigation links: `<a href="<?= route('/campaigns') ?>">`

3. **All view files** - Updated to use `route()` for links:
   - `dashboard.php`
   - `campaigns.php`
   - `campaign-detail.php`
   - `session-detail.php`
   - `upload.php`

## How It Works

The code automatically detects where the app is installed:

```php
// Auto-detects base path
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Results:
// localhost/ttrpg-recap    → BASE_PATH = '/ttrpg-recap'
// iamrlw.com/ttrpg-recap   → BASE_PATH = '/ttrpg-recap'
// example.com/             → BASE_PATH = ''
```

## Deployment to Production

To deploy these fixes to your production server:

```bash
# SSH into your server
ssh username@iamrlw.com

# Navigate to project directory
cd /home/iamrlw/public_html/ttrpg-recap

# Pull latest changes
git pull origin main

# No composer update needed (no dependencies changed)
```

## Testing

After deployment, verify:
1. ✅ Page loads with styling: https://iamrlw.com/ttrpg-recap
2. ✅ Check browser console - no 404 errors
3. ✅ Navigation works (Dashboard, Campaigns)
4. ✅ All buttons and links work correctly

## Benefits

This solution:
- ✅ Works in any subdirectory automatically
- ✅ Works at domain root (no subdirectory)  
- ✅ No hardcoded paths
- ✅ Easier to move between environments
- ✅ Matches best practices for PHP applications

---

**Issue:** Fixed
**Status:** Ready to deploy
**Files changed:** 7 files (1 controller, 6 views)
