<template>
  <div class="settings-container p-6 bg-white rounded-lg shadow">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Settings</h1>

    <div class="space-y-6">
      <!-- Tracking Toggle -->
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-medium text-gray-900">Track plugins activity</h3>
          <p class="text-sm text-gray-500">Monitor when plugins are added, deactivated, or deleted</p>
        </div>
        <div>
          <button
              type="button"
              @click="toggleTracking"
              class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :class="tracking ? 'bg-blue-600' : 'bg-gray-200'"
          >
            <span
                class="pointer-events-none relative inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                :class="tracking ? 'translate-x-5' : 'translate-x-0'"
            />
          </button>
        </div>
      </div>

      <!-- Divider -->
      <div class="border-t border-gray-200"></div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
            type="button"
            @click="saveSettings"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            :class="{ 'opacity-50 cursor-not-allowed': saving }"
            :disabled="saving"
        >
          <span v-if="saving">Saving...</span>
          <span v-else>Save Settings</span>
        </button>
      </div>
    </div>

    <!-- Success Message -->
    <div
        v-if="showSuccess"
        class="mt-4 p-3 bg-green-50 text-green-700 rounded-md flex items-center"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"/>
      </svg>
      Settings saved successfully
    </div>
  </div>
</template>

<script setup>
import {ref} from 'vue';


const tracking = ref(true); // Default to enabled
const saving = ref(false);
const showSuccess = ref(false);

// Toggle tracking on/off
const toggleTracking = () => {
  tracking.value = !tracking.value;
};

// Save settings
const saveSettings = async () => {
  // Show saving state
  saving.value = true;

  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 800));

    // Here you would normally save to your backend
    console.log('Saving settings:', {trackPlugins: tracking.value});

    // Show success message
    showSuccess.value = true;

    // Hide success message after 3 seconds
    setTimeout(() => {
      showSuccess.value = false;
    }, 3000);
  } catch (error) {
    console.error('Error saving settings:', error);
    // Here you would handle any errors
  } finally {
    saving.value = false;
  }
};
</script>