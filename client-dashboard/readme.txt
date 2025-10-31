=== Client Dashboard ===
Contributors: youragency
Tags: client, dashboard, frontend, elementor, forms
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive frontend dashboard for clients to manage posts, pages, and Elementor content without accessing the WordPress admin.

== Description ==

Client Dashboard is a powerful WordPress plugin designed for agency owners who need to provide their clients with a safe, user-friendly interface to manage their website content without risking accidental changes to critical WordPress settings.

= Features =

* **Frontend Post Management** - Create, edit, and delete posts from a beautiful frontend interface
* **Page Editing** - Edit existing pages with full Elementor integration
* **Elementor Form Submissions** - View and manage form submissions from Elementor Pro
* **Media Library** - Upload and manage media files
* **Custom User Role** - Dedicated "Client User" role with limited, safe capabilities
* **Security First** - Prevents system-level changes and restricts access to sensitive areas
* **Activity Logging** - Track client actions for accountability
* **Mobile Responsive** - Works perfectly on all devices
* **Translation Ready** - Fully prepared for multilingual sites

= Perfect For =

* WordPress agencies managing client websites
* Freelancers who need to give clients content management access
* Website maintainers who want to restrict client access safely
* Anyone who needs a cleaner, simpler interface for content management

= Key Benefits =

* **No WordPress Admin Access Required** - Clients never see the confusing WordPress backend
* **Safe Content Management** - Clients can only edit content, not break your site
* **Elementor Integration** - Seamlessly edit Elementor pages from the frontend
* **Professional Interface** - Clean, modern design that clients will love
* **Zero Learning Curve** - Intuitive interface that anyone can use

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* Elementor (optional, for page editing)
* Elementor Pro (optional, for form submissions)

== Installation ==

1. Upload the `client-dashboard` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Client Dashboard > Settings to configure your preferences
4. Assign the "Client User" role to your client users
5. Share the Client Dashboard page URL with your clients

= Manual Installation =

1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

== Frequently Asked Questions ==

= Can clients access the WordPress admin? =

No, client users are automatically redirected to the frontend dashboard when they try to access the WordPress admin. They only have access to their profile, media uploads, and the Elementor editor for pages.

= What can clients edit? =

Clients can:
- Create and edit their own posts
- Edit existing pages (including with Elementor)
- Upload media files
- View form submissions (if Elementor Pro is installed)

Clients cannot:
- Install or deactivate plugins
- Change themes
- Modify WordPress settings
- Delete pages
- Access other users' content (unless given permission)

= Does this work with Elementor? =

Yes! The plugin has full Elementor integration. Clients can edit Elementor pages directly from the dashboard, and they can view Elementor Pro form submissions.

= Is it translation ready? =

Yes, the plugin is fully translation ready and uses WordPress translation standards.

= Can I customize the dashboard? =

Yes, the plugin includes filters and actions for developers to customize the dashboard, add custom menu items, and extend functionality.

= How do I create a client user? =

1. Go to WordPress Admin > Users > Add New
2. Fill in the user details
3. Select "Client User" as the role
4. Click "Add New User"

= What happens when I deactivate the plugin? =

The plugin can be safely deactivated. The "Client User" role will remain in the database but users with that role won't be able to access the dashboard. No content will be lost.

= Can clients see other users' posts? =

By default, client users can only see and edit their own posts. Administrators can modify this behavior through the plugin settings or custom code.

== Screenshots ==

1. Main Dashboard - Clean overview with statistics and quick actions
2. Posts Management - Create and edit posts with a familiar interface
3. Page Editor - Edit pages with full Elementor integration
4. Form Submissions - View and export Elementor form submissions
5. Media Library - Upload and manage media files
6. Admin Settings - Configure plugin features and permissions

== Changelog ==

= 1.0.0 =
* Initial release
* Frontend post creation and editing
* Page editing with Elementor integration
* Elementor form submissions viewer
* Media library management
* Custom client user role
* Activity logging
* Security features
* Responsive design
* Translation ready

== Upgrade Notice ==

= 1.0.0 =
Initial release of Client Dashboard.

== Additional Information ==

= Support =

For support, please visit our GitHub repository or contact us through our website.

= Privacy =

This plugin does not collect any user data or send information to external servers. All data remains on your WordPress installation.

= Credits =

Developed with ❤️ for WordPress agencies and freelancers worldwide.

== Developer Notes ==

= Hooks and Filters =

The plugin provides several hooks for customization:

**Actions:**
- `client_dashboard_init` - Fires when the plugin is initialized
- `client_dashboard_before_content` - Before dashboard content
- `client_dashboard_after_content` - After dashboard content

**Filters:**
- `client_dashboard_menu_items` - Modify dashboard menu items
- `client_dashboard_statistics` - Modify dashboard statistics
- `client_dashboard_post_capabilities` - Customize post capabilities

= Custom Development =

The plugin is designed to be developer-friendly and easy to extend. Check the documentation for more information on customization.
