<script setup>
import HelloWorld from './components/HelloWorld.vue'
</script>
<template>
  <div class="wp-vue-app">
    <h1>Vue.js Dashboard in WordPress</h1>
    <nav>
      <router-link to="/dashboards">Dashboard</router-link> |
      <router-link to="/dashboards/plugins">Plugins</router-link>
    </nav>
    <router-view></router-view>
  </div>
</template>
