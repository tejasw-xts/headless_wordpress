# Hotel Booking Theme - Child Theme

## What is a Child Theme?

A child theme allows you to make customizations without modifying the parent theme files. This means:
- ✅ Your customizations are safe when parent theme updates
- ✅ You can override any parent template
- ✅ You can add custom CSS and functions
- ✅ Easy to maintain and debug

## Installation

1. **Activate Child Theme:**
   - Go to WordPress Admin → Appearance → Themes
   - Find "Hotel Booking Theme Child"
   - Click **Activate**
   - (The parent theme will automatically be used as base)

2. **Verify Installation:**
   - Visit your site
   - Check if everything looks correct
   - Both parent and child styles should load

## How to Customize

### 1. Custom CSS

Add your custom CSS to `style.css` in this child theme:

```css
/* Example: Change button color */
.btn-primary {
    background: #3498db;
}
```

### 2. Custom PHP Functions

Add custom functions to `functions.php`:

```php
// Example: Add custom shortcode
function my_custom_shortcode() {
    return 'Hello from child theme!';
}
add_shortcode('hello', 'my_custom_shortcode');
```

### 3. Override Parent Templates

To override a parent template:
1. Copy the template file from parent theme
2. Paste it in child theme (same folder structure)
3. Modify the child theme version

**Example:** Override `single-room.php`
- Copy: `/hotel-booking-theme/single-room.php`
- To: `/hotel-booking-theme-child/single-room.php`
- Edit the child theme version

### 4. Add Custom JavaScript

Create `js/custom.js` and enqueue it in `functions.php`:

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

### 5. Add Custom CSS File

Create `css/custom.css` and enqueue it:

```php
function child_custom_styles() {
    wp_enqueue_style('child-custom-css', 
        get_stylesheet_directory_uri() . '/css/custom.css'
    );
}
add_action('wp_enqueue_scripts', 'child_custom_styles');
```

## File Structure

```
hotel-booking-theme-child/
├── style.css           (Your custom CSS)
├── functions.php       (Your custom functions)
├── screenshot.png      (Optional: Theme screenshot)
├── css/               (Your custom CSS files)
├── js/                (Your custom JavaScript files)
├── images/            (Your custom images)
├── templates/         (Your custom templates)
└── README.md          (This file)
```

## Common Customizations

### Change Colors

In `style.css`:
```css
/* Primary color */
.btn-primary,
.price {
    color: #your-color;
}

/* Header background */
.site-header {
    background: #your-color;
}
```

### Modify Room Display

Copy `single-room.php` from parent to child theme and edit.

### Add Custom Widget Area

In `functions.php`:
```php
function my_custom_widget_area() {
    register_sidebar(array(
        'name' => 'My Custom Area',
        'id' => 'custom-area',
    ));
}
add_action('widgets_init', 'my_custom_widget_area');
```

### Change Booking Form

Copy `index.php` or `single-room.php` to child theme and modify the booking form section.

## Best Practices

1. **Always work in child theme** - Never edit parent theme files
2. **Use child theme style.css** - For all CSS customizations
3. **Use child theme functions.php** - For all PHP customizations
4. **Copy templates when needed** - Only copy files you want to modify
5. **Keep it organized** - Use folders (css/, js/, templates/)
6. **Comment your code** - Explain what your customizations do
7. **Test changes** - Always test after making changes
8. **Backup regularly** - Before major customizations

## Template Override Examples

### Override Homepage
Copy `index.php` from parent to child theme

### Override Room Page
Copy `single-room.php` from parent to child theme

### Override Header
Copy `header.php` from parent to child theme

### Override Footer
Copy `footer.php` from parent to child theme

### Add Custom Page Template
Create new file in child theme:
```php
<?php
/**
 * Template Name: My Custom Template
 */
get_header();
// Your custom code
get_footer();
?>
```

## Troubleshooting

### Styles not loading?
- Clear browser cache
- Clear WordPress cache
- Check if child theme is activated
- Verify functions.php has no errors

### Parent theme styles missing?
- Check functions.php enqueue code
- Make sure parent theme is installed
- Verify Template header in style.css

### Changes not showing?
- Hard refresh browser (Ctrl+F5)
- Clear all caches
- Check file permissions
- Verify you're editing child theme files

## Resources

- [WordPress Child Themes](https://developer.wordpress.org/themes/advanced-topics/child-themes/)
- [Theme Development](https://developer.wordpress.org/themes/)
- [Template Hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/)

## Support

For parent theme features, see parent theme documentation.
For child theme customizations, refer to WordPress documentation.

---

**Child Theme Version:** 1.0.0  
**Parent Theme:** Hotel Booking Theme  
**WordPress Version:** 5.0+
