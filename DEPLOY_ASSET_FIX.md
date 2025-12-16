# Quick Deployment Guide - Asset Fix

## What Was Fixed
✅ CSS and JS files now load correctly from `/ttrpg-recap/` subdirectory  
✅ All navigation links work properly in subdirectory installations
✅ Automatic detection - works in any deployment location

## Deploy to Production

### Option 1: Git Pull (Recommended)
```bash
cd /home/iamrlw/public_html/ttrpg-recap
git pull origin main
```

### Option 2: Manual Upload
Upload these 7 files via FTP/cPanel:
- `public/index.php`
- `src/Views/layout.php`
- `src/Views/dashboard.php`
- `src/Views/campaigns.php`
- `src/Views/campaign-detail.php`
- `src/Views/session-detail.php`
- `src/Views/upload.php`

## Verify It Works

Visit: https://iamrlw.com/ttrpg-recap

Check browser console (F12):
- ✅ No 404 errors for CSS/JS
- ✅ Page has styling
- ✅ Click "Campaigns" - should navigate correctly

## Quick Reference

**Before:**
```html
<link href="/assets/css/style.css">      ❌ 404 Error
<a href="/campaigns">                     ❌ Wrong path
```

**After:**
```php
<link href="<?= asset('assets/css/style.css') ?>">  ✅ Correct
<a href="<?= route('/campaigns') ?>">                 ✅ Correct
```

---
**Ready to deploy!** 🚀
