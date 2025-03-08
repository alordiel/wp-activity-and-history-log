<script setup>
import HelloWorld from './components/HelloWorld.vue'
</script>
<template>
  <div class="wp-vue-app bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Vue.js Dashboard in WordPress</h1>
    <nav class="mb-6 pb-4 border-b border-gray-200">
      <router-link
        class="mr-4 py-2 px-4 text-blue-600 hover:bg-blue-100 rounded-md"
        to="/dashboards">Dashboard</router-link>
      <router-link
        class="py-2 px-4 text-blue-600 hover:bg-blue-100 rounded-md"
        to="/dashboards/plugins">Plugins</router-link>
    </nav>
    <router-view></router-view>
  </div>
</template>
