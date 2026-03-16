# Smart Broken Link Fixer

![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress Compatibility](https://img.shields.io/badge/wordpress-5.8%2B-brightgreen.svg)
![PHP Version](https://img.shields.io/badge/php-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg)

An advanced WordPress plugin that detects broken links and automatically fixes them using AI-powered suggestions and smart redirects.

## 🚀 Features

- ✅ **Automatic Link Detection** - Scans your entire website for broken links
- 🤖 **AI-Powered Suggestions** - Intelligently suggests replacement pages
- 🔄 **Auto Redirect** - Automatically redirect broken links (301/302)
- 📧 **Email Notifications** - Get notified when broken links are detected
- ⏰ **Scheduled Scans** - Daily or weekly automatic scans
- 📊 **Detailed Reports** - Comprehensive scan history and statistics
- 🎯 **Easy Management** - Simple interface to fix, ignore, or redirect
- 🌐 **External Link Scanning** - Option to scan external links
- 🔌 **REST API** - Full REST API support for developers

## 📋 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## 💾 Installation

### From WordPress Admin

1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

### Manual Installation

1. Upload `smart-broken-link-fixer` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Broken Links > Settings to configure

## 🎯 Usage

### Starting a Scan

1. Navigate to **Broken Links > Dashboard**
2. Click **"Start New Scan"** button
3. Wait for the scan to complete
4. View results in the Broken Links page

### Fixing Broken Links

1. Go to **Broken Links > Broken Links**
2. Click **"AI Fix"** to generate suggestions
3. Click **"Redirect"** to apply a redirect
4. Or click **"Ignore"** to skip the link

### Configuration

Navigate to **Broken Links > Settings** to configure:

- Auto-scan frequency
- Email notifications
- AI suggestions
- External link scanning
- Default redirect type
- Request timeout

## 🏗️ Project Structure

```
smart-broken-link-fixer/
├── assets/
│   ├── css/
│   │   └── admin.css
│   ├── js/
│   │   └── admin.js
│   └── images/
├── includes/
│   ├── admin/
│   │   └── class-menu.php
│   ├── api/
│   │   └── class-routes.php
│   ├── core/
│   │   ├── class-scanner.php
│   │   ├── class-redirector.php
│   │   └── class-notifier.php
│   ├── class-activator.php
│   └── class-deactivator.php
├── languages/
├── templates/
│   └── admin/
│       ├── dashboard.php
│       ├── broken-links.php
│       ├── scan-history.php
│       └── settings.php
├── smart-broken-link-fixer.php
├── uninstall.php
├── readme.txt
└── README.md
```

## 🔌 REST API

The plugin provides a REST API for developers:

### Endpoints

- `GET /wp-json/sblf/v1/links` - Get all broken links
- `GET /wp-json/sblf/v1/links/{id}` - Get specific link
- `DELETE /wp-json/sblf/v1/links/{id}` - Delete a link
- `GET /wp-json/sblf/v1/stats` - Get statistics

### Example

```javascript
fetch('/wp-json/sblf/v1/stats', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

## 🛠️ Development

### Database Tables

The plugin creates two custom tables:

1. `wp_sblf_wordpress_headlesss` - Stores broken link information
2. `wp_sblf_scan_history` - Stores scan history

### Hooks & Filters

**Actions:**
- `sblf_daily_scan` - Triggered for scheduled scans
- `sblf_send_notification` - Triggered to send notifications

**Filters:**
- Coming soon...

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📝 License

This plugin is licensed under the GPL v2 or later.

## 👨‍💻 Author

**Your Name**
- Website: https://yourwebsite.com
- GitHub: [@yourusername](https://github.com/yourusername)

## 🙏 Support

If you find this plugin helpful, please consider:
- ⭐ Starring the repository
- 🐛 Reporting bugs
- 💡 Suggesting new features
- 📖 Improving documentation

## 📸 Screenshots

Coming soon...

## 🔄 Changelog

### 1.0.0 (2024)
- Initial release
- Broken link detection
- AI-powered suggestions
- Automatic redirects
- Email notifications
- Scheduled scans
- REST API support
