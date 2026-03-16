# 🚀 QUICK TESTING STEPS - START HERE!

## Your Plugin is Now Installed! Let's Test It!

**Plugin Location:** `/var/www/html/wordpress/wp-content/plugins/smart-broken-link-fixer/`

---

## 📋 STEP-BY-STEP TESTING (5 Minutes)

### Step 1: Access WordPress Admin (30 seconds)

1. Open your browser
2. Go to: `http://localhost/wordpress/wp-admin` (or your WordPress URL)
3. Login with your admin credentials

---

### Step 2: Activate the Plugin (30 seconds)

1. In WordPress Admin, click: **Plugins** (left sidebar)
2. Find: **Smart Broken Link Fixer**
3. Click: **Activate**

**✅ What Should Happen:**
- Plugin activates successfully
- No error messages
- New menu "Broken Links" appears in left sidebar
- Database tables created automatically

**❌ If You See Errors:**
- Check PHP version (needs 7.4+)
- Check file permissions
- Enable WordPress debug mode

---

### Step 3: Check the Dashboard (1 minute)

1. Click: **Broken Links** in left sidebar
2. You should see:
   - Dashboard with 4 statistics cards (all showing 0)
   - "Start New Scan" button
   - Recent Scans section (empty)

**✅ Success:** Dashboard loads with Bootstrap styling

---

### Step 4: Create Test Post with Broken Links (1 minute)

1. Go to: **Posts > Add New**
2. Title: `Test Broken Links`
3. In content, add this HTML (switch to Code Editor):

```html
<p>Testing broken links:</p>

<a href="https://httpstat.us/404">Broken Link 404</a><br>
<a href="https://httpstat.us/500">Broken Link 500</a><br>
<a href="http://this-site-does-not-exist-123456789.com">Dead Domain</a><br>
<a href="https://www.google.com">Working Link</a>
```

4. Click: **Publish**

---

### Step 5: Run Your First Scan (2 minutes)

1. Go to: **Broken Links > Dashboard**
2. Click: **Start New Scan** button
3. Wait 30-60 seconds
4. Refresh the page

**✅ What Should Happen:**
- Alert: "Scan started successfully!"
- After refresh: Statistics update
- Broken links count increases
- Recent scans shows completed scan

---

### Step 6: View Broken Links (30 seconds)

1. Click: **Broken Links > Broken Links** (submenu)
2. You should see:
   - Table with broken links found
   - Each link shows:
     - Broken URL
     - Source page link
     - Status code (404, 500, etc.)
     - Status badge (Pending)
     - Action buttons (AI Fix, Redirect, Ignore)

**✅ Success:** You see 3 broken links from your test post

---

### Step 7: Test AI Fix (30 seconds)

1. Find any broken link in the list
2. Click: **AI Fix** button
3. Wait for processing
4. Alert shows: "AI suggestion generated"
5. Page refreshes
6. Suggested URL appears below the broken link

**✅ Success:** AI suggestion appears (might be homepage if no similar content)

---

### Step 8: Test Redirect Feature (1 minute)

1. Click: **Redirect** button on any broken link
2. Modal popup opens
3. In "Redirect URL" field, enter: `https://www.google.com`
4. Select: **301 Permanent**
5. Click: **Apply Redirect**

**✅ What Should Happen:**
- Alert: "Redirect applied successfully!"
- Page refreshes
- Link status changes to "Fixed" (green badge)

---

### Step 9: Test Ignore Feature (30 seconds)

1. Click: **Ignore** button on another broken link
2. Confirm the action
3. Alert: "Link ignored successfully!"
4. Page refreshes
5. Link status changes to "Ignored" (gray badge)

---

### Step 10: Check Scan History (30 seconds)

1. Go to: **Broken Links > Scan History**
2. You should see:
   - Your completed scan
   - Scan type: Manual
   - Total links checked
   - Broken links found
   - Status: Completed
   - Timestamps

**✅ Success:** Scan history shows your scan details

---

### Step 11: Test Settings (30 seconds)

1. Go to: **Broken Links > Settings**
2. Configure:
   - ✅ Enable Automatic Scanning
   - Scan Frequency: Daily
   - ✅ Enable AI Suggestions
   - ✅ Scan External Links
   - Notification Email: your@email.com
   - Default Redirect Type: 301
   - Request Timeout: 30
3. Click: **Save Settings**

**✅ Success:** Alert shows "Settings saved successfully!"

---

## 🎯 QUICK VERIFICATION CHECKLIST

After completing all steps, verify:

- [ ] Plugin activated without errors
- [ ] "Broken Links" menu appears
- [ ] Dashboard loads with statistics
- [ ] Scan completes successfully
- [ ] Broken links detected and listed
- [ ] AI Fix generates suggestions
- [ ] Redirect applies successfully
- [ ] Ignore function works
- [ ] Scan history recorded
- [ ] Settings save correctly
- [ ] No JavaScript errors in browser console (F12)

---

## 🔍 CHECK DATABASE TABLES

### Verify Tables Were Created:

**Option 1: Using phpMyAdmin**
1. Open phpMyAdmin
2. Select your WordPress database
3. Look for these tables:
   - `wp_sblf_wordpress_headlesss` ✅
   - `wp_sblf_scan_history` ✅

**Option 2: Using MySQL Command**
```bash
mysql -u root -p
USE wordpress;  # or your database name
SHOW TABLES LIKE 'wp_sblf%';
```

**Expected Output:**
```
+---------------------------+
| Tables_in_wordpress       |
+---------------------------+
| wp_sblf_wordpress_headlesss      |
| wp_sblf_scan_history      |
+---------------------------+
```

### View Data in Tables:

```sql
-- See broken links
SELECT * FROM wp_sblf_wordpress_headlesss;

-- See scan history
SELECT * FROM wp_sblf_scan_history;

-- Count by status
SELECT status, COUNT(*) FROM wp_sblf_wordpress_headlesss GROUP BY status;
```

---

## 🐛 COMMON ISSUES & SOLUTIONS

### Issue 1: Plugin Won't Activate

**Error:** "The plugin does not have a valid header"

**Solution:**
```bash
# Check main file exists
ls -la /var/www/html/wordpress/wp-content/plugins/smart-broken-link-fixer/smart-broken-link-fixer.php

# Check file permissions
chmod 644 /var/www/html/wordpress/wp-content/plugins/smart-broken-link-fixer/smart-broken-link-fixer.php
```

### Issue 2: "Broken Links" Menu Not Appearing

**Solution:**
1. Deactivate and reactivate plugin
2. Clear browser cache
3. Check if you're logged in as admin
4. Check PHP error logs

### Issue 3: Scan Button Does Nothing

**Solution:**
1. Open browser console (F12)
2. Check for JavaScript errors
3. Verify jQuery is loaded
4. Check if AJAX URL is correct

**Debug in Console:**
```javascript
console.log(sblf_ajax);  // Should show ajax_url and nonce
```

### Issue 4: No Broken Links Found

**Reasons:**
- No posts with links exist
- External scanning disabled
- Timeout too short

**Solution:**
- Create test post (see Step 4)
- Enable external link scanning in settings
- Increase timeout to 60 seconds

### Issue 5: Database Tables Not Created

**Solution:**
```bash
# Enable WordPress debug
# Edit wp-config.php and add:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

# Then deactivate and reactivate plugin
# Check: /wp-content/debug.log for errors
```

---

## 📊 EXPECTED RESULTS

### After Activation:
```
✅ Plugin Status: Active
✅ Menu: "Broken Links" visible
✅ Database Tables: 2 created
✅ Settings: Default values saved
✅ Cron Job: Scheduled
```

### After First Scan:
```
✅ Scan Status: Completed
✅ Links Scanned: 4 (from test post)
✅ Broken Links: 3 detected
✅ Working Links: 1 (Google)
✅ Database: Links saved
✅ History: Scan recorded
```

### After Applying Fixes:
```
✅ Redirect Applied: Link status = "Fixed"
✅ Ignored Link: Link status = "Ignored"
✅ AI Suggestion: URL generated
✅ Statistics: Updated on dashboard
```

---

## 🎥 VISUAL TESTING GUIDE

### What You Should See:

**1. Dashboard:**
```
┌─────────────────────────────────────────┐
│  Smart Broken Link Fixer Dashboard      │
├─────────────────────────────────────────┤
│  [Start New Scan]                       │
│                                         │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │
│  │  3   │ │  2   │ │  1   │ │  0   │  │
│  │Total │ │Pending│ │Fixed │ │Ignored│ │
│  └──────┘ └──────┘ └──────┘ └──────┘  │
│                                         │
│  Recent Scans:                          │
│  Manual | 4 links | 3 broken | ✓       │
└─────────────────────────────────────────┘
```

**2. Broken Links Page:**
```
┌─────────────────────────────────────────────────────┐
│  Broken Links                    [Filter: All ▼]    │
├─────────────────────────────────────────────────────┤
│ URL                  | Source | Code | Status | Actions │
│ httpstat.us/404     | View   | 404  | Pending| [AI Fix][Redirect][Ignore] │
│ httpstat.us/500     | View   | 500  | Pending| [AI Fix][Redirect][Ignore] │
│ dead-domain.com     | View   | 0    | Pending| [AI Fix][Redirect][Ignore] │
└─────────────────────────────────────────────────────┘
```

---

## ✅ SUCCESS CONFIRMATION

**Your plugin is working if:**

1. ✅ Activates without errors
2. ✅ Menu appears in WordPress admin
3. ✅ All 4 pages load (Dashboard, Broken Links, Scan History, Settings)
4. ✅ Scan detects broken links from test post
5. ✅ AI Fix generates suggestions
6. ✅ Redirect applies and changes status
7. ✅ Ignore function works
8. ✅ Settings save successfully
9. ✅ Database tables exist with data
10. ✅ No errors in browser console

---

## 🎉 CONGRATULATIONS!

If all steps passed, your plugin is **100% WORKING!**

### What You've Tested:
- ✅ Installation & Activation
- ✅ Database Creation
- ✅ Link Scanning
- ✅ AI Suggestions
- ✅ Redirect System
- ✅ Ignore Functionality
- ✅ Settings Management
- ✅ Admin Interface
- ✅ AJAX Operations
- ✅ Data Persistence

### Next Steps:
1. Test with real content on your site
2. Enable automatic scanning
3. Monitor email notifications
4. Check scheduled scans work
5. Test with more broken links
6. Verify redirects work on frontend

---

## 📞 Need Help?

**Check these files:**
- `TESTING_GUIDE.md` - Detailed testing instructions
- `INSTALLATION.md` - Installation help
- `VERIFICATION_REPORT.md` - Quality assurance report
- `PROJECT_SUMMARY.md` - Complete overview

**Debug Mode:**
```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Check logs at:
// /wp-content/debug.log
```

---

## 🚀 YOUR PLUGIN IS READY!

**Start testing now! Follow the steps above and see your plugin in action!**
