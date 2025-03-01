# WP Activity Tracker

A WordPress plugin for tracking and logging admin activities, both automatically and manually.

This plugin allows WordPress administrators to monitor changes made to the WordPress admin area, including plugin updates, activations, deactivations, and settings changes. It also provides a way to manually log activities that cannot be automatically tracked.

## Features

- **Automatic tracking** of WordPress admin activities:
  - Plugin updates, activations, deactivations, and deletions
  - WordPress core updates
  - General settings changes
  - Permalink structure changes

- **Manual event logging** with custom categories and importance levels

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

## Development

### Prerequisites

- PHP 8.2+
- Node.js and npm
- WordPress development environment

### Setting Up Development Environment

1. Clone the repository to your WordPress plugins directory
2. Install PHP dependencies (if any):
   ```
   composer install
   ```
3. Install JavaScript dependencies:
   ```
   cd assets/js
   npm install
   ```

### Building the Frontend

```
cd assets/js
npm run build
```

### Development Mode

```
cd assets/js
npm run dev
```

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
  - `js/` - Vue.js application
    - `src/` - Vue components and assets
    - `dist/` - Compiled assets (generated after build)