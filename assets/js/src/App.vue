<template>
  <div class="wp-activity-tracker">
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">WP Activity Tracker</h1>
      <button
        @click="openCreateModal"
        class="px-4 py-2 font-medium text-white bg-wp-admin-primary rounded hover:bg-opacity-90 transition"
      >
        Create Manual Event
      </button>
    </header>

    <div class="mb-6 bg-wp-admin-gray p-4 rounded flex flex-wrap gap-4">
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
          <option v-for="category in wpData.categories" :key="category" :value="category">
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
                  :alt="event.user.name"
                  class="w-6 h-6 mr-2 rounded-full"
                />
                <span v-else class="w-6 h-6 mr-2 rounded-full bg-gray-200 flex items-center justify-center text-xs">
                  {{ event.user.name.charAt(0).toUpperCase() }}
                </span>
                <span>{{ event.user.name }}</span>
              </div>
            </td>
            <td class="px-4 py-3">
              <span
                :class="['px-2 py-1 text-xs rounded-full', `type-${event.type}`]"
              >
                {{ event.type }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="['px-2 py-1 text-xs rounded-full', `importance-${event.importance}`]"
              >
                {{ wpData.importanceOptions[event.importance] }}
              </span>
            </td>
            <td class="px-4 py-3">
              {{ event.date_formatted }}
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
        Showing {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} events
      </div>
      <div class="flex space-x-2">
        <button
          @click="fetchEvents(1)"
          :disabled="currentPage === 1"
          :class="[
            'px-3 py-1 rounded',
            currentPage === 1
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-wp-admin-primary text-white hover:bg-opacity-90'
          ]"
        >
          First
        </button>
        <button
          @click="fetchEvents(currentPage - 1)"
          :disabled="currentPage === 1"
          :class="[
            'px-3 py-1 rounded',
            currentPage === 1
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-wp-admin-primary text-white hover:bg-opacity-90'
          ]"
        >
          Previous
        </button>
        <button
          @click="fetchEvents(currentPage + 1)"
          :disabled="currentPage === totalPages"
          :class="[
            'px-3 py-1 rounded',
            currentPage === totalPages
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-wp-admin-primary text-white hover:bg-opacity-90'
          ]"
        >
          Next
        </button>
        <button
          @click="fetchEvents(totalPages)"
          :disabled="currentPage === totalPages"
          :class="[
            'px-3 py-1 rounded',
            currentPage === totalPages
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-wp-admin-primary text-white hover:bg-opacity-90'
          ]"
        >
          Last
        </button>
      </div>
    </div>

    <!-- Create Event Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b">
          <h2 class="text-xl font-bold">Create Manual Event</h2>
          <button @click="closeCreateModal" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="createEvent" class="px-6 py-4">
          <div class="space-y-4">
            <div>
              <label for="event_name" class="block text-sm font-medium text-gray-700 mb-1">
                Event Name *
              </label>
              <input
                id="event_name"
                v-model="newEvent.event_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded"
              />
            </div>

            <div>
              <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                Category *
              </label>
              <div class="flex space-x-2">
                <select
                  id="category"
                  v-model="newEvent.category"
                  required
                  class="flex-1 px-3 py-2 border border-gray-300 rounded"
                >
                  <option value="">Select Category</option>
                  <option v-for="category in wpData.categories" :key="category" :value="category">
                    {{ category }}
                  </option>
                </select>
                <button
                  type="button"
                  @click="showNewCategoryInput = !showNewCategoryInput"
                  class="px-3 py-2 text-wp-admin-primary border border-wp-admin-primary rounded hover:bg-wp-admin-primary hover:text-white"
                >
                  {{ showNewCategoryInput ? 'Cancel' : 'New' }}
                </button>
              </div>
              <input
                v-if="showNewCategoryInput"
                v-model="newCategory"
                type="text"
                placeholder="Enter new category"
                class="w-full px-3 py-2 border border-gray-300 rounded mt-2"
                @keyup.enter="addNewCategory"
              />
            </div>

            <div>
              <label for="importance" class="block text-sm font-medium text-gray-700 mb-1">
                Importance *
              </label>
              <select
                id="importance"
                v-model="newEvent.importance"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded"
              >
                <option value="">Select Importance</option>
                <option v-for="(label, value) in wpData.importanceOptions" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
            </div>

            <div>
              <label for="note" class="block text-sm font-medium text-gray-700 mb-1">
                Note *
              </label>
              <textarea
                id="note"
                v-model="newEvent.note"
                required
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 rounded"
              ></textarea>
            </div>

            <div>
              <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                Date *
              </label>
              <input
                id="date"
                v-model="newEvent.date"
                type="datetime-local"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded"
              />
            </div>
          </div>

          <div class="flex justify-end mt-6 space-x-3">
            <button
              type="button"
              @click="closeCreateModal"
              class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 bg-wp-admin-primary text-white rounded hover:bg-opacity-90 disabled:opacity-50"
            >
              {{ isSubmitting ? 'Creating...' : 'Create Event' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import { format } from 'date-fns';

export default {
  name: 'App',
  props: {
    wpData: {
      type: Object,
      required: true
    }
  },
  setup(props) {
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
    const showCreateModal = ref(false);
    const isSubmitting = ref(false);
    const showNewCategoryInput = ref(false);
    const newCategory = ref('');
    const newEvent = reactive({
      event_name: '',
      category: '',
      importance: '',
      note: '',
      date: format(new Date(), "yyyy-MM-dd'T'HH:mm")
    });

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
        const response = await fetch(`${props.wpData.apiUrl}/events?${params.toString()}`, {
          method: 'GET',
          headers: {
            'X-WP-Nonce': props.wpData.nonce,
            'Content-Type': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`API Error: ${response.status}`);
        }

        const data = await response.json();
        
        // Update state
        events.value = data;
        totalPages.value = parseInt(response.headers.get('X-WP-TotalPages')) || 1;
        totalItems.value = parseInt(response.headers.get('X-WP-Total')) || 0;
      } catch (err) {
        console.error('Error fetching events:', err);
        error.value = err.message;
      } finally {
        loading.value = false;
      }
    };

    // Create a new event
    const createEvent = async () => {
      isSubmitting.value = true;
      
      try {
        // Validate form
        if (!newEvent.event_name || !newEvent.category || !newEvent.importance || !newEvent.note || !newEvent.date) {
          throw new Error(__('All fields are required.'));
        }
        
        // Format date for MySQL
        const formattedDate = formatDateForMySQL(newEvent.date);
        
        // Send request to API
        const response = await fetch(`${props.wpData.apiUrl}/events`, {
          method: 'POST',
          headers: {
            'X-WP-Nonce': props.wpData.nonce,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            ...newEvent,
            date: formattedDate
          })
        });
        
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.message || __('Failed to create event.'));
        }
        
        // Get created event
        const createdEvent = await response.json();
        
        // Add to events list and refresh
        events.value.unshift(createdEvent);
        
        // Close modal and reset form
        closeCreateModal();
        
        // Refresh events list
        await fetchEvents(1);
      } catch (err) {
        console.error('Error creating event:', err);
        alert(err.message);
      } finally {
        isSubmitting.value = false;
      }
    };
    
    // Format date for MySQL
    const formatDateForMySQL = (dateString) => {
      const date = new Date(dateString);
      return format(date, 'yyyy-MM-dd HH:mm:ss');
    };
    
    // Add a new category
    const addNewCategory = () => {
      if (newCategory.value.trim()) {
        // Add to categories list
        props.wpData.categories.push(newCategory.value.trim());
        
        // Select it in the form
        newEvent.category = newCategory.value.trim();
        
        // Reset input and hide
        newCategory.value = '';
        showNewCategoryInput.value = false;
      }
    };
    
    // Modal controls
    const openCreateModal = () => {
      showCreateModal.value = true;
      // Set default date to now
      newEvent.date = format(new Date(), "yyyy-MM-dd'T'HH:mm");
    };
    
    const closeCreateModal = () => {
      showCreateModal.value = false;
      // Reset form
      Object.assign(newEvent, {
        event_name: '',
        category: '',
        importance: '',
        note: '',
        date: format(new Date(), "yyyy-MM-dd'T'HH:mm")
      });
      showNewCategoryInput.value = false;
      newCategory.value = '';
    };
    
    // Search debouncing
    let searchTimeout;
    const debounceSearch = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        fetchEvents(1);
      }, 500);
    };
    
    // Translation helper (normally would use a proper i18n library)
    const __ = (text) => {
      return text; // In a real plugin, this would use WordPress's i18n functions
    };
    
    // Lifecycle hooks
    onMounted(() => {
      fetchEvents();
    });
    
    return {
      // State
      events,
      loading,
      error,
      currentPage,
      perPage,
      totalPages,
      totalItems,
      search,
      filters,
      showCreateModal,
      isSubmitting,
      showNewCategoryInput,
      newCategory,
      newEvent,
      
      // Methods
      fetchEvents,
      createEvent,
      openCreateModal,
      closeCreateModal,
      addNewCategory,
      debounceSearch,
      __,
      
      // Computed
      wpData: computed(() => props.wpData)
    };
  }
};
</script>
