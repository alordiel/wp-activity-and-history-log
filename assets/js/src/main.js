import { createApp } from 'vue';
import App from './App.vue';
import './assets/css/main.css';

// Get the data passed from WordPress
const wpData = window.wpActivityTracker || {
  apiUrl: '',
  nonce: '',
  categories: [],
  importanceOptions: {}
};

// Create the Vue app
const app = createApp(App, { wpData });

// Mount the app
app.mount('#wp-activity-tracker-app');
