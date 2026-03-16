# Installation & Setup Guide

## Smart Broken Link Fixer - Complete Installation Instructions

### Prerequisites

Before installing, ensure your server meets these requirements:
- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- At least 64MB PHP memory limit (128MB recommended)

---

## Installation Methods

### Method 1: WordPress Admin (Recommended for End Users)

1. **Download the Plugin**
   - Download the plugin ZIP file from WordPress.org or GitHub releases

2. **Upload via WordPress Admin**
   - Log in to your WordPress admin dashboard
   - Navigate to `Plugins > Add New`
   - Click `Upload Plugin` button at the top
   - Click `Choose File` and select the downloaded ZIP file
   - Click `Install Now`

3. **Activate the Plugin**
   - After installation, click `Activate Plugin`
   - You'll see a new menu item "Broken Links" in your admin sidebar

### Method 2: Manual Installation via FTP

1. **Extract the ZIP File**
   - Extract the plugin ZIP file on your computer

2. **Upload via FTP**
   - Connect to your server via FTP
   - Navigate to `/wp-content/plugins/`
   - Upload the entire `smart-broken-link-fixer` folder

3. **Activate**
   - Go to WordPress Admin > Plugins
   - Find "Smart Broken Link Fixer" and click `Activate`

### Method 3: Manual Installation via SSH

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/yourusername/smart-broken-link-fixer.git
cd smart-broken-link-fixer
```

Then activate via WordPress Admin > Plugins

---

## Initial Configuration

### Step 1: Access Settings

After activation:
1. Go to `Broken Links > Settings` in WordPress admin
2. Configure the following options:

### Step 2: Configure Basic Settings

**Auto Scan Settings:**
- ✅ Enable Automatic Scanning (recommended)
- Choose scan frequency: Daily or Weekly

**AI Settings:**
- ✅ Enable AI Suggestions (recommended)
- ✅ Enable Auto-Fix (optional - automatically applies AI suggestions)

**Link Scanning:**
- ✅ Scan External Links (optional - may slow down scans)

**Notifications:**
- Enter your email address for notifications
- Default: WordPress admin email

**Redirect Settings:**
- Choose default redirect type: 301 (Permanent) or 302 (Temporary)
- Set request timeout: 30 seconds (default)

### Step 3: Run Your First Scan

1. Go to `Broken Links > Dashboard`
2. Click `Start New Scan` button
3. Wait for the scan to complete (time depends on site size)
4. View results in `Broken Links > Broken Links`

---

## Usage Guide

### Dashboard Overview

The dashboard shows:
- Total broken links found
- Pending links (not yet fixed)
- Fixed links (redirects applied)
- Ignored links
- Recent scan history

### Managing Broken Links

**View All Broken Links:**
1. Go to `Broken Links > Broken Links`
2. See all detected broken links with details

**Fix a Broken Link:**

**Option 1: AI Suggestion**
1. Click `AI Fix` button next to a broken link
2. Plugin generates intelligent suggestion
3. Review the suggested URL
4. Click `Redirect` to apply

**Option 2: Manual Redirect**
1. Click `Redirect` button
2. Enter the target URL manually
3. Choose redirect type (301 or 302)
4. Click `Apply Redirect`

**Option 3: Ignore Link**
1. Click `Ignore` button
2. Link will be marked as ignored
3. Won't appear in pending list

### Filtering Links

Use the status filter dropdown:
- All Status
- Pending (needs attention)
- Fixed (redirects applied)
- Ignored (marked as ignore)

### Scan History

View all past scans:
1. Go to `Broken Links > Scan History`
2. See scan details:
   - Scan type (manual/scheduled)
   - Total links checked
   - Broken links found
   - Status and timestamps

---

## Advanced Usage

### Using the REST API

**Get Statistics:**
```javascript
fetch('/wp-json/sblf/v1/stats', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

**Get All Broken Links:**
```javascript
fetch('/wp-json/sblf/v1/links?page=1&per_page=20', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Scheduled Scans

The plugin automatically runs scans based on your settings:
- Daily: Runs once per day
- Weekly: Runs once per week

To manually trigger a scan:
- Go to Dashboard and click `Start New Scan`

### Email Notifications

When broken links are detected:
- Email sent to configured address
- Contains count of broken links
- Includes link to view details
- Only sent for new broken links

---

## Troubleshooting

### Scan Not Starting

**Issue:** Clicking "Start New Scan" does nothing

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify WordPress cron is working
3. Check PHP error logs
4. Increase PHP max_execution_time

### No Broken Links Found

**Issue:** Scan completes but shows 0 broken links

**Possible Reasons:**
1. Your site has no broken links (great!)
2. External link scanning is disabled
3. Timeout is too short for slow links

**Solutions:**
- Enable external link scanning in settings
- Increase timeout value
- Check if posts have actual links

### AI Suggestions Not Working

**Issue:** AI Fix button doesn't generate suggestions

**Solutions:**
1. Ensure "Enable AI Suggestions" is checked in settings
2. Check if similar content exists on your site
3. Verify database tables are created properly

### Redirects Not Working

**Issue:** Applied redirects don't work

**Solutions:**
1. Clear WordPress cache
2. Clear browser cache
3. Check .htaccess file permissions
4. Verify redirect URL is correct
5. Check for conflicting redirect plugins

### Performance Issues

**Issue:** Scans are very slow

**Solutions:**
1. Reduce timeout value in settings
2. Disable external link scanning
3. Run scans during off-peak hours
4. Increase PHP memory limit
5. Consider scanning in smaller batches

---

## Database Tables

The plugin creates two tables:

**wp_sblf_wordpress_headlesss:**
- Stores all broken link information
- Includes source, status, redirects

**wp_sblf_scan_history:**
- Stores scan history
- Includes statistics and timestamps

---

## Uninstallation

### Clean Uninstall

1. Deactivate the plugin
2. Delete the plugin
3. Database tables are automatically removed
4. All settings are deleted

### Manual Cleanup (if needed)

```sql
DROP TABLE IF EXISTS wp_sblf_wordpress_headlesss;
DROP TABLE IF EXISTS wp_sblf_scan_history;
DELETE FROM wp_options WHERE option_name LIKE 'sblf_%';
```

---

## Support & Documentation

- **Documentation:** See README.md
- **Issues:** Report on GitHub Issues
- **Support Forum:** WordPress.org support forum
- **Email:** your.email@example.com

---

## Tips for Best Results

1. **Run Regular Scans:** Enable automatic scanning
2. **Review AI Suggestions:** Always verify before applying
3. **Use 301 Redirects:** For permanent changes
4. **Monitor Email Notifications:** Stay informed
5. **Check Scan History:** Track improvements over time
6. **Fix High-Priority Links First:** Focus on important pages
7. **Test Redirects:** Verify they work correctly
8. **Keep Plugin Updated:** Get latest features and fixes

---

## Next Steps

After installation:
1. ✅ Configure settings
2. ✅ Run first scan
3. ✅ Review broken links
4. ✅ Apply fixes
5. ✅ Set up automatic scanning
6. ✅ Monitor via email notifications

Congratulations! Your WordPress site is now protected against broken links! 🎉
