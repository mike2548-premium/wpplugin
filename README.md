# Client Dashboard - WordPress Plugin

A comprehensive frontend dashboard for WordPress clients to manage posts, pages, and Elementor content without accessing the WordPress admin panel.

## 🚀 Features

### Core Features
- **Frontend Post Management** - Create, edit, and delete posts with a beautiful WYSIWYG editor
- **Page Editing** - Edit existing pages with full Elementor integration
- **Elementor Form Submissions** - View, filter, and export form submissions
- **Media Library** - Upload, manage, and organize media files
- **Custom User Role** - Dedicated "Client User" role with safe, limited capabilities
- **Activity Logging** - Track all client actions for accountability
- **Security First** - Prevents system-level changes and WordPress admin access

### User Experience
- Clean, modern, and intuitive interface
- Mobile-responsive design
- Real-time AJAX interactions
- No page reloads for smooth experience
- Professional dashboard statistics
- Quick action buttons

### Developer Features
- Translation ready (i18n)
- Extensive hooks and filters
- Well-documented code
- PSR-4 autoloading ready
- WordPress coding standards
- Extensible architecture

## 📋 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Elementor (optional, for page editing)
- Elementor Pro (optional, for form submissions)

## 🔧 Installation

### Automatic Installation
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Select the ZIP file and click "Install Now"
5. Activate the plugin

### Manual Installation
1. Upload the `client-dashboard` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Configure settings at Client Dashboard → Settings

## 🎯 Getting Started

### 1. Configure Settings
Navigate to **Client Dashboard → Settings** and:
- Enable/disable features (Posts, Pages, Media, Forms)
- Set allowed post types
- Configure permissions

### 2. Create Client Users
1. Go to **Users → Add New**
2. Fill in user details
3. Select **"Client User"** as the role
4. Click "Add New User"

### 3. Share Dashboard URL
The client dashboard is automatically created at:
```
https://yoursite.com/client-dashboard/
```

Share this URL with your clients along with their login credentials.

## 📚 Usage

### For Clients

#### Creating Posts
1. Login and navigate to the Client Dashboard
2. Click "Posts" in the sidebar
3. Click "Create New Post"
4. Fill in title, content, categories, and tags
5. Set featured image (optional)
6. Click "Create Post"

#### Editing Pages
1. Navigate to "Pages" in the dashboard
2. Find the page you want to edit
3. Click "Edit" or "Edit with Elementor"
4. Make your changes
5. Click "Update Page"

#### Viewing Form Submissions
1. Navigate to "Form Submissions"
2. Filter by form name if needed
3. Click "View Details" to see full submission
4. Export to CSV for reporting

#### Managing Media
1. Navigate to "Media Library"
2. Click "Upload File" to add new files
3. View, search, and filter existing media
4. Delete files you no longer need

### For Administrators

#### Customizing the Dashboard
Use the provided hooks and filters to extend functionality:

```php
// Add custom menu item
add_filter('client_dashboard_menu_items', function($items) {
    $items['custom'] = array(
        'title' => 'Custom Section',
        'url' => add_query_arg('view', 'custom', get_permalink()),
        'icon' => 'dashicons-admin-tools',
    );
    return $items;
});

// Modify statistics
add_filter('client_dashboard_statistics', function($stats) {
    $stats['custom'] = array(
        'total' => 100,
        'label' => 'Custom Metric',
        'icon' => 'dashicons-chart-line',
    );
    return $stats;
});
```

## 🔒 Security Features

### Client Role Capabilities
Client users can:
- ✅ Read content
- ✅ Create and edit own posts
- ✅ Edit published pages (not delete)
- ✅ Upload files
- ✅ View Elementor forms
- ✅ Edit with Elementor

Client users cannot:
- ❌ Install/deactivate plugins
- ❌ Change themes
- ❌ Modify WordPress settings
- ❌ Access other users' content
- ❌ Delete pages
- ❌ Edit PHP/theme files

### Admin Access Prevention
- Clients are automatically redirected from WordPress admin
- Only profile and media upload pages are accessible
- Admin bar is hidden for client users
- All AJAX requests are nonce-verified

### Activity Logging
All client actions are logged including:
- Post creation/editing/deletion
- Page updates
- Media uploads/deletions
- Form submission views

## 🎨 Customization

### Custom Templates
Override default templates by copying them to your theme:
```
your-theme/
  client-dashboard/
    templates/
      dashboard.php
      posts/
        list.php
        create.php
        edit.php
```

### Custom Styles
Enqueue custom CSS to override default styles:
```php
add_action('wp_enqueue_scripts', function() {
    if (is_page('client-dashboard')) {
        wp_enqueue_style('custom-dashboard', get_stylesheet_directory_uri() . '/custom-dashboard.css');
    }
});
```

## 🛠️ Development

### File Structure
```
client-dashboard/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── includes/
│   ├── class-client-dashboard.php
│   ├── class-role-manager.php
│   ├── class-security.php
│   ├── class-post-manager.php
│   ├── class-page-manager.php
│   ├── class-elementor-forms.php
│   ├── class-media-manager.php
│   └── class-dashboard-manager.php
├── templates/
│   ├── dashboard.php
│   ├── posts/
│   ├── pages/
│   ├── forms/
│   └── media/
├── languages/
├── client-dashboard.php
└── readme.txt
```

### Available Hooks

#### Actions
- `client_dashboard_init` - Plugin initialization
- `client_dashboard_before_content` - Before dashboard content
- `client_dashboard_after_content` - After dashboard content

#### Filters
- `client_dashboard_menu_items` - Modify menu items
- `client_dashboard_statistics` - Modify statistics
- `client_dashboard_post_capabilities` - Customize capabilities

## 🐛 Troubleshooting

### Dashboard page not found
Run this in your functions.php temporarily:
```php
flush_rewrite_rules();
```

### Elementor editor not loading
Ensure the client user has the `edit_with_elementor` capability.

### Form submissions not showing
- Verify Elementor Pro is installed and activated
- Check that forms are using Elementor Pro submission database

## 📝 Changelog

### Version 1.0.0
- Initial release
- Frontend post management
- Page editing with Elementor
- Form submissions viewer
- Media library
- Custom client role
- Activity logging
- Security features

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024 Your Agency Name

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## 👥 Credits

Developed for WordPress agencies and freelancers who need a safe, professional way to give clients content management access.

## 🔗 Links

- [Documentation](#)
- [Support](#)
- [GitHub Repository](#)

## 💬 Support

For support, please:
1. Check the documentation
2. Search existing issues
3. Create a new issue on GitHub
4. Contact us through our website

---

Made with ❤️ for the WordPress community
