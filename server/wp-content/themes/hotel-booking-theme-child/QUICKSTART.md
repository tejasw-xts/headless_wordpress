# Child Theme Quick Start Guide

## ✅ What You Have Now

- **Parent Theme:** hotel-booking-theme (Don't edit this!)
- **Child Theme:** hotel-booking-theme-child (Work here!)

## 🚀 Activation Steps

### Step 1: Activate Child Theme (1 minute)

1. Go to WordPress Admin
2. Navigate to **Appearance → Themes**
3. Find **"Hotel Booking Theme Child"**
4. Click **Activate**

**Important:** Activate the CHILD theme, not the parent!

### Step 2: Verify It's Working

1. Visit your website
2. Right-click → View Page Source
3. Search for "hotel-booking-theme-child"
4. You should see child theme CSS loading

## 📝 Making Customizations

### Option 1: Add Custom CSS (Easiest)

Edit: `style.css` in child theme

```css
/* Change button color */
.btn-primary {
    background: #3498db;
}

/* Change header color */
.site-header {
    background: #ffffff;
}
```

### Option 2: Add Custom Functions

Edit: `functions.php` in child theme

```php
// Add custom function
function my_custom_function() {
    // Your code
}
add_action('init', 'my_custom_function');
```

### Option 3: Override Parent Template

1. Copy template from parent theme
2. Paste in child theme (same location)
3. Edit the child theme version

**Example:**
- Copy: `/hotel-booking-theme/single-room.php`
- To: `/hotel-booking-theme-child/single-room.php`
- Edit child version

## 🎨 Common Customizations

### Change Primary Color

In child theme `style.css`:
```css
.btn-primary {
    background: #your-color;
}

.price {
    color: #your-color;
}
```

### Add Custom Logo Size

In child theme `functions.php`:
```php
function child_custom_logo_size() {
    add_theme_support('custom-logo', array(
        'height' => 100,
        'width' => 300,
    ));
}
add_action('after_setup_theme', 'child_custom_logo_size');
```

### Modify Room Price Display

In child theme `functions.php`:
```php
function custom_price_format($price) {
    return '€' . number_format($price, 2);
}
// Use this in your templates
```

## 📁 Child Theme Structure

```
hotel-booking-theme-child/
├── style.css              ← Add your CSS here
├── functions.php          ← Add your PHP here
├── css/
│   └── custom.css        ← Additional CSS
├── js/
│   └── custom.js         ← Custom JavaScript
├── images/               ← Your custom images
└── templates/            ← Override templates here
```

## ⚠️ Important Rules

1. **Never edit parent theme files**
2. **Always work in child theme**
3. **Test changes before going live**
4. **Backup before major changes**
5. **Keep child theme organized**

## 🔧 Enqueue Custom Files

To use `css/custom.css`, add to child `functions.php`:

```php
function child_custom_styles() {
    wp_enqueue_style('child-custom', 
        get_stylesheet_directory_uri() . '/css/custom.css'
    );
}
add_action('wp_enqueue_scripts', 'child_custom_styles');
```

To use `js/custom.js`, add to child `functions.php`:

```php
function child_custom_scripts() {
    wp_enqueue_script('child-custom-js', 
        get_stylesheet_directory_uri() . '/js/custom.js', 
        array('jquery'), 
        '1.0', 
        true
    );
}
add_action('wp_enqueue_scripts', 'child_custom_scripts');
```

## 🎯 Next Steps

1. ✅ Activate child theme
2. ✅ Add custom CSS to style.css
3. ✅ Test your changes
4. ✅ Add more customizations as needed

## 💡 Pro Tips

- Use browser DevTools to find CSS selectors
- Comment your code for future reference
- Keep backups of working versions
- Test on mobile devices
- Clear cache after changes

## 🆘 Troubleshooting

**Problem:** Changes not showing
- Clear browser cache (Ctrl+F5)
- Clear WordPress cache
- Check if child theme is activated

**Problem:** Site looks broken
- Deactivate child theme
- Check functions.php for errors
- Verify parent theme is installed

**Problem:** CSS not loading
- Check file paths in functions.php
- Verify file permissions
- Check browser console for errors

## 📚 Resources

- Parent theme docs: See parent README.md
- WordPress Codex: https://codex.wordpress.org/
- Child Themes: https://developer.wordpress.org/themes/advanced-topics/child-themes/

---

**You're all set!** Start customizing in the child theme. 🎨
