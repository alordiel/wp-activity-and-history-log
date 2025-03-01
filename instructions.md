# Plugin Name: WP Activity Tracker
I want to create a plugin that will track manually things like plugings update, plugins activation and deactivations, deleting, changes in the general settings, permalincs. This will be the automatic part. It will get the user that triggered any of the actions, and store this in a separate database table. There will be also option for users manual input, where the user add an event for some actions that they have took - like updating specific settings of some plugin that can't be tracked manually. The plugin will be for admin use to monitor and maintain the current state of the admin side of the WordPress. There is already a plugin that do part of this - called Stream, but lack that functionality for manual adding of events and has too many other functionalities that are not applied to my concept.


## Automatic tracking of:

* Plugin updates, activations, deactivations, and deletions
* General settings changes
* Permalink structure changes

## Database structure:
* database should be created on plugin activation
* it should check if database isn't created previously
* on uninstalling of the plugin - purge and delete database table:

`ID` (type int(16)) Primary key
`user_id` (type int(16)) can include value of 0 - for the automatic events that are not triggered by user
`event_name` (varchar (255)) - general name for the event
`type` (varchart (255)) can be either "manual" or "automatic"
`category` (varchart (255)) - can be any of a fixed list of categories (provided below) but also user can add new categories (as text)
`importance` (varchart (255)) - can be 'low', 'medium', 'high', 'critical'
`note` - (type text) - this will be for any notes that the admin want to leave like descriptions and ect
`date` - (date time format) - the date and time when the event was registered

## List of categories
- Plugin update
- Adding new plugin
- Activating plugin
- Deactivating plugin
- Deleting plugin
- WP core update
- Plugin settings change

## Automatic events
* Hook to events:
- plugin added
- plugin activated
- plugin deleted
- plugin deactivated
- wp core updated
* save the events in the database using the associated category.
* user_id is 0
* `event_name` should be as the related action
* `category` as related action
* `type` is always `automatic`
* `note` could contain info about what was the version before that update and what was the new version (only if this info is easy to get)
* `importance` should be as follow - if minor version was updated (like from 2.3 to 2.4) the importance is `medium`, if major version updated (2.3 to 3.0) - it is `high`
* `date` is always current date

## Admin part/page
* the plugin should be operated from the wp-admin area, so add a new admin menu item call "WP Activity"
* the screen should render the vue.js front end
* it will display the last 40 events,
* on top-left it will have search and filter (refer to filter functionality below)
* top-right with have button that will open a modal for creating manual event (refer to event-creation form below)
* pagination is required if more then 40 events in the database

## Event-creation form
* it will be opened in modal/dialog screen, tiggered from button "Create manual event"
* the modal will have button in the top right for closing it.
* it will contain a input form with the following field
- event name - input text
- category - select box with all the current categories (with option to add a new one)
- importance - select box with values 'low', 'medium', 'high', 'critical'
- note - textarea
- date - defaults to current date but it will allow selected previous date (validation of date form to match mysql date format)
* All fields are required
* A button for creating the event + cancel button at the bottom
* on clicking "create event" the input form data will be taken and sent via API call to the server 
* type will be "manual"
* a wp_nonce should be provided 


## Technical Approach:
* front-end use vue.js 3 with vite 
* use tailwindcss for styling
* for api calls use `fetch`
* using PHP8.2
* pay attention to typed variables 
* use wp_nonce for security


## WordPress Backend:
* Custom database table for activity logs (name it {wpdb->prefix}pp_activity_logger)
* Use WordPress hooks to capture automatic events
* Create REST API endpoints for Vue.js to interact with
- api for search and filtering (uses GET method)
- api for creating events (uses POST method)
- api for pagination events (uses GET) => this might be duplication of the first API so ignore if you consider so
* Auth handling to ensure only admins have access (if needed relay on JWT, I am using JWT Authentication for WP REST API plugin)
