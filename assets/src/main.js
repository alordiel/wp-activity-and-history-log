import { createApp } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import App from './App.vue';
import Home from './views/Home.vue';
import Settings from './views/Settings.vue';
import Dashboard from './views/Dashboard.vue';
import './assets/tailwind.css'; // Import Tailwind CSS
// Create routes
const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', component: Home },
  { path: '/dashboard/:id', component: Dashboard },
  { path: '/settings', component: Settings }
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