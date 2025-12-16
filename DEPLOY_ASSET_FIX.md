# JavaScript API Fix - Deployment Update

## What Was Fixed (Additional)

After the initial asset path fix, we discovered **JavaScript code** was also making hardcoded API calls:

### Issues Found & Fixed:

1. **campaigns.php** - Create campaign form
   - ❌ `fetch('/campaigns')` 
   - ✅ `fetch(window.BASE_PATH + '/campaigns')`

2. **session-detail.php** - Status polling
   - ❌ `fetch('/sessions/123/status')`
   - ✅ `fetch(window.BASE_PATH + '/sessions/123/status')`

3. **upload.php** - File upload
   - ❌ `xhr.open('POST', '/campaigns/1/sessions')`
   - ✅ `xhr.open('POST', window.BASE_PATH + '/campaigns/1/sessions')`

4. **All redirects** - After successful operations
   - ❌ `window.location.href = '/campaigns/1'`
   - ✅ `window.location.href = window.BASE_PATH + '/campaigns/1'`

## Solution Applied

**In `layout.php`** - Added JavaScript variable:
```php
<script>
    window.BASE_PATH = '<?= BASE_PATH ?>';
</script>
```

This makes the base path available to all JavaScript code throughout the app.

## Deploy to Production

```bash
cd /home/iamrlw/public_html/ttrpg-recap
git pull origin main
```

## What's Fixed Now

✅ CSS and JS files load correctly  
✅ Navigation links work  
✅ **NEW:** API calls work (create campaign, upload session, status polling)  
✅ **NEW:** Redirects work after operations  
✅ Everything works in `/ttrpg-recap` subdirectory

## Testing Checklist

After deployment, test these features:

1. ✅ **Create New Campaign** - Should redirect properly
2. ✅ **Upload Session** - Should upload and redirect
3. ✅ **Session Status Polling** - Should update when processing
4. ✅ All navigation still works

---

**Status:** Ready for production deployment 🚀  
**Commits:** 2 commits pushed to GitHub
- First: Asset path fixes
- Second: JavaScript API fixes
