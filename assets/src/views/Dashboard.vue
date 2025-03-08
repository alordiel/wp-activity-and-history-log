<script setup>
import { useRoute } from 'vue-router';
import { ref } from 'vue';

const route = useRoute();
const dashboardId = ref(route.params.id);

</script>

<template>
  <h1>You are viewing Dashboard with ID {{ dashboardId }}</h1>
</template>
