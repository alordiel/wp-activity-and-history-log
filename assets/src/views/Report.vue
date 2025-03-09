<template>
  <div id="wp-activity-tracker-app" class="wrap">
    <div v-cloak>
      <header class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">WP Activity Tracker</h1>
        <button
            @click="openCreateModal"
            class="px-4 py-2 font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition"
        >
          Create Manual Event
        </button>
      </header>

      <div class="mb-6 bg-gray-100 p-4 rounded flex flex-wrap gap-4">
        <!-- Search & Filter Bar -->
        <div class="flex-1 min-w-[280px]">
          <input
              v-model="search"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded"
              placeholder="Search events..."
              @input="debounceSearch"
          />
        </div>

        <div class="flex-1 min-w-[200px]">
          <select
              v-model="filters.category"
              class="w-full px-3 py-2 border border-gray-300 rounded"
              @change="fetchEvents(1)"
          >
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category" :value="category">
              {{ category }}
            </option>
          </select>
        </div>

        <div class="flex-1 min-w-[200px]">
          <select
              v-model="filters.type"
              class="w-full px-3 py-2 border border-gray-300 rounded"
              @change="fetchEvents(1)"
          >
            <option value="">All Types</option>
            <option value="manual">Manual</option>
            <option value="automatic">Automatic</option>
          </select>
        </div>

        <div class="flex-1 min-w-[200px]">
          <select
              v-model="filters.importance"
              class="w-full px-3 py-2 border border-gray-300 rounded"
              @change="fetchEvents(1)"
          >
            <option value="">All Importance</option>
            <option v-for="(label, value) in wpData.importanceOptions" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Events Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
          <thead>
          <tr class="bg-gray-100">
            <th class="px-4 py-2 text-left border-b">Event</th>
            <th class="px-4 py-2 text-left border-b">Category</th>
            <th class="px-4 py-2 text-left border-b">User</th>
            <th class="px-4 py-2 text-left border-b">Type</th>
            <th class="px-4 py-2 text-left border-b">Importance</th>
            <th class="px-4 py-2 text-left border-b">Date</th>
            <th class="px-4 py-2 text-left border-b">Actions</th>
          </tr>
          </thead>
          <tbody v-if="events.length">
          <tr v-for="event in events" :key="event.id" class="hover:bg-gray-50 border-b">
            <td class="px-4 py-3">
              <div class="font-medium">{{ event.event_name }}</div>
              <div v-if="event.note" class="text-sm text-gray-600 mt-1">{{ event.note }}</div>
            </td>
            <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            {{ event.category }}
                        </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center">
                <img
                    v-if="event.user.avatar"
                    :src="event.user.avatar"
                    alt=""
                    class="w-6 h-6 mr-2 rounded-full"
                />
                <span
                    v-else
                    class="w-6 h-6 mr-2 rounded-full bg-gray-200 flex items-center justify-center text-xs">
                                {{ event.user.name.charAt(0).toUpperCase() }}
                            </span>
                <span>{{ event.user.name }}</span>
              </div>
            </td>
            <td class="px-4 py-3">
                        <span
                            :class="['px-2 py-1 text-xs rounded-full', event.type === 'manual' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800']"
                        >
                            {{ event.type }}
                        </span>
            </td>
            <td class="px-4 py-3">
                        <span :class="[
                                'px-2 py-1 text-xs rounded-full',
                                event.importance === 'low' ? 'bg-blue-100 text-blue-800' :
                                event.importance === 'medium' ? 'bg-green-100 text-green-800' :
                                event.importance === 'high' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-red-100 text-red-800'
                            ]"
                        >
                            {{ wpData.importanceOptions[event.importance] }}
                        </span>
            </td>
            <td class="px-4 py-3">
              {{ event.date_formatted }}
            </td>
            <td class="px-4 py-3">
              <div class="flex space-x-2 justify-end action-buttons">
                <button
                    v-if="event.type === 'manual'"
                    @click="openEditModal(event)"
                    class="text-blue-600 hover:text-blue-800"
                    title="<?php _e('Edit', 'wp-activity-tracker')?>"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <button
                    v-if="event.type === 'manual'"
                    @click="deleteEvent(event.id)"
                    class="text-red-600 hover:text-red-800"
                    title="<?php _e('Delete', 'wp-activity-tracker')?>"
                    :disabled="isDeleting"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          </tbody>
          <tbody v-else>
          <tr>
            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
              <div v-if="loading">Loading events...</div>
              <div v-else>No events found.</div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-between items-center mt-6">
        <div class="text-sm text-gray-600">
          Showing {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, totalItems) }}
          of {{ totalItems }} events
        </div>
        <div class="flex space-x-2">
          <button
              @click="fetchEvents(1)"
              :disabled="currentPage === 1"
              :class="['px-3 py-1 rounded',
                 currentPage === 1 ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'
              ]"
          >
            First
          </button>
          <button
              @click="fetchEvents(currentPage - 1)"
              :disabled="currentPage === 1"
              :class="[ 'px-3 py-1 rounded',
                 currentPage === 1  ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'
              ]"
          >
            Previous
          </button>
          <button
              @click="fetchEvents(currentPage + 1)"
              :disabled="currentPage === totalPages"
              :class="['px-3 py-1 rounded',
                 currentPage === totalPages? 'bg-gray-200 text-gray-500 cursor-not-allowed': 'bg-blue-600 text-white hover:bg-blue-700'
              ]"
          >
            Next
          </button>
          <button
              @click="fetchEvents(totalPages)"
              :disabled="currentPage === totalPages"
              :class="['px-3 py-1 rounded',
                 currentPage === totalPages? 'bg-gray-200 text-gray-500 cursor-not-allowed': 'bg-blue-600 text-white hover:bg-blue-700'
              ]"
          >
            Last
          </button>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import {useRoute} from 'vue-router';
import {ref, reactive} from 'vue';

// Get WordPress data
const wpData = window.wpActivityTracker || {
    apiUrl: '',
    nonce: '',
    categories: [],
};

const route = useRoute();
const dashboardId = ref(route.params.id);

// State
const events = ref([]);
const loading = ref(true);
const error = ref(null);
const currentPage = ref(1);
const perPage = ref(40);
const totalPages = ref(0);
const totalItems = ref(0);
const search = ref('');
const filters = reactive({
  category: '',
  type: '',
  importance: ''
});
const categories = ref(Array.isArray(wpData.categories) ? wpData.categories : []);
const showCreateModal = ref(false);
const isEditMode = ref(false);
const editingEventId = ref(null);
const isDeleting = ref(false);

const newEvent = reactive({
  event_name: '',
  category: '',
  importance: '',
  note: '',
  date: formatDateTimeForInput(new Date())
});

// Format date for input
function formatDateTimeForInput(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');

  return `${year}-${month}-${day}T${hours}:${minutes}`;
}



// API
const fetchEvents = async (page = 1) => {
  loading.value = true;
  error.value = null;
  currentPage.value = page;

  try {
    // Build query parameters
    const params = new URLSearchParams({
      page: page,
      per_page: perPage.value
    });

    if (search.value) {
      params.append('search', search.value);
    }

    if (filters.category) {
      params.append('category', filters.category);
    }

    if (filters.type) {
      params.append('type', filters.type);
    }

    if (filters.importance) {
      params.append('importance', filters.importance);
    }

    // Fetch data from API
    const response = await fetch(`${wpData.apiUrl}/events?${params.toString()}`, {
      method: 'GET',
      headers: {
        'X-WP-Nonce': wpData.nonce,
        'Content-Type': 'application/json'
      }
    });

    if (!response.ok) {
      return new Error(`API Error: ${response.status}`);
    }

    // Update state
    events.value = await response.json();
    totalPages.value = parseInt(response.headers.get('X-WP-TotalPages')) || 1;
    totalItems.value = parseInt(response.headers.get('X-WP-Total')) || 0;
  } catch (err) {
    console.error('Error fetching events:', err);
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};



const deleteEvent = async (id) => {
  if (!confirm('Are you sure you want to delete this event?')) {
    return;
  }

  isDeleting.value = true;

  try {
    const response = await fetch(`${wpData.apiUrl}/events/${id}`, {
      method: 'DELETE',
      headers: {
        'X-WP-Nonce': wpData.nonce,
        'Content-Type': 'application/json'
      }
    });

    if (!response.ok) {
      const errorData = await response.json();
      return new Error(errorData.message || 'Failed to delete event.');
    }

    // Refresh events list
    fetchEvents(currentPage.value).then(() => isDeleting.value = false);
  } catch (err) {
    console.error('Error deleting event:', err);
    alert(err.message);
    isDeleting.value = false;
  }
};

// Modal controls
const openCreateModal = () => {
  showCreateModal.value = true;
  // Set default date to now
  newEvent.date = formatDateTimeForInput(new Date());
};

const openEditModal = (event) => {
  isEditMode.value = true;
  editingEventId.value = event.id;
  showCreateModal.value = true;

  // Convert date format from MySQL to input datetime-local format
  const eventDate = new Date(event.date);
  const formattedDate = formatDateTimeForInput(eventDate);

  // Populate form with event data
  Object.assign(newEvent, {
    event_name: event.event_name,
    category: event.category,
    importance: event.importance,
    note: event.note,
    date: formattedDate
  });
};



// Search debouncing
let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchEvents(1);
  }, 500);
};

// Lifecycle hooks
onMounted(() => {
  fetchEvents();
});
</script>
