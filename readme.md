# WP Activity Tracker

A WordPress plugin for tracking and logging admin activities, both automatically and manually.

This plugin allows WordPress administrators to monitor changes made to the WordPress admin area, including plugin updates, activations, deactivations, and settings changes. It also provides a way to manually log activities that cannot be automatically tracked, with options to edit or delete manual entries as needed.

## Features

- **Automatic tracking** of WordPress admin activities:
  - Plugin updates, activations, deactivations, and deletions
  - WordPress core updates
  - General settings changes
  - Permalink structure changes

- **Manual event management**:
  - Create custom events with categories and importance levels
  - Edit existing manual events
  - Delete manual events when no longer needed
  - Automatic events cannot be edited or deleted for integrity

- **Modern UI** built with Vue.js and Tailwind CSS

- **Comprehensive filtering** by event type, category, and importance level

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Activate the plugin

## Usage

### Viewing Activity Logs

1. Go to WordPress Admin > WP Activity
2. View the automatically tracked activities
3. Use the filters to narrow down the events
4. Use the search box to find specific events

### Adding Manual Events

1. Click the "Create Manual Event" button
2. Fill in the required information:
   - Event Name
   - Category (select existing or create new)
   - Importance (Low, Medium, High, Critical)
   - Note
   - Date
3. Click "Create Event"

### Managing Manual Events

1. **Editing Events**:
   - Click the edit (pencil) icon next to any manual event
   - Modify the event details in the form
   - Click "Update Event" to save changes

2. **Deleting Events**:
   - Click the delete (trash) icon next to any manual event
   - Confirm the deletion when prompted
   - Note: Automatic events cannot be deleted

## Development

### Prerequisites

- PHP 8.2+
- WordPress development environment

### Setting Up Development Environment

1. Clone the repository to your WordPress plugins directory
2. Activate the plugin in the WordPress admin

### Frontend Implementation

The frontend uses:
- Vue.js 3 loaded from CDN
- Tailwind CSS loaded from CDN
- No build process required

### API Endpoints

The plugin provides the following REST API endpoints:

- `GET /wp-json/wp-activity-tracker/v1/events` - List all events with filtering
- `POST /wp-json/wp-activity-tracker/v1/events` - Create a manual event
- `GET /wp-json/wp-activity-tracker/v1/events/{id}` - Get a specific event
- `GET /wp-json/wp-activity-tracker/v1/categories` - List all available categories

### Authentication

All API endpoints require administrator privileges. The plugin uses:
- WordPress's built-in REST API authentication
- JWT authentication (via JWT Authentication for WP REST API plugin)
- Nonce verification for form submissions

### Plugin Structure

- `wp-activity-tracker.php` - Main plugin file
- `includes/` - PHP classes
  - `class-database.php` - Database operations
  - `class-event-logger.php` - Event logging
  - `class-event-listeners.php` - WordPress hooks
  - `class-rest-controller.php` - REST API endpoints
- `assets/` - Frontend assets
  - `js/` - JavaScript files
    - `app.js` - Vue.js application
  - `css/` - CSS files
    - `style.css` - Custom styling