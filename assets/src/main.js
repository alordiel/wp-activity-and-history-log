import { createApp } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import App from './App.vue';
import Dashboard from './views/Dashboard.vue';
import Plugins from './views/Plugins.vue';
import './assets/tailwind.css'; // Import Tailwind CSS
// Create routes
const routes = [
  { path: '/', redirect: '/dashboards' },
  { path: '/dashboards', component: Dashboard },
  { path: '/dashboards/plugins', component: Plugins }
];

// Create router instance
const router = createRouter({
  history: createWebHashHistory(),
  routes
});

// Create app and mount to specific element
const app = createApp(App);
app.use(router);
app.mount('#app-dashboard'); // Mount to your specific element