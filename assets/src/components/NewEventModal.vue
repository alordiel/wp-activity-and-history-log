<template>
  <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl overflow-hidden">
      <div class="flex justify-between items-center px-6 py-4 border-b">
        <h2 class="text-xl font-bold">Create Manual Event</h2>
        <button @click="closeCreateModal" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"/>
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
                <option v-for="category in categories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
              <button
                  type="button"
                  @click="showNewCategoryInput = !showNewCategoryInput"
                  class="px-3 py-2 text-blue-600 border border-blue-600 rounded hover:bg-blue-600 hover:text-white"
              >
                {{
                  showNewCategoryInput ? "Cancel" :
                      "New"
                }}
              </button>
              <button
                  type="button"
                  v-show="showNewCategoryInput"
                  @click="addNewCategory"
                  class="px-3 py-2 text-green-600 border border-green-600 rounded hover:bg-green-600 hover:text-white"
              >
                {{
                  showNewCategoryInput ? "Add" :
                      "New"
                }}
              </button>
            </div>
            <input
                v-if="showNewCategoryInput"
                v-model="newCategory"
                type="text"
                placeholder="Enter new category"
                class="w-full px-3 py-2 border border-gray-300 rounded mt-2"
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
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {{
              isSubmitting ?
                  (isEditMode ? "Updating..." : "Creating...") :
                  (isEditMode ? "Update" : "Create")
            }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
<script setup>
import {ref, reactive} from 'vue';

const showNewCategoryInput = ref(false);
const newCategory = ref('');

const categories = ref(Array.isArray(wpData.categories) ? wpData.categories : []);
const showCreateModal = ref(false);
const isEditMode = ref(false);
const editingEventId = ref(null);
const isSubmitting = ref(false);
const newEvent = reactive({
  event_name: '',
  category: '',
  importance: '',
  note: '',
  date: formatDateTimeForInput(new Date())
});
const createEvent = async () => {
  isSubmitting.value = true;

  try {
    // Validate form
    if (!newEvent.event_name || !newEvent.category || !newEvent.importance || !newEvent.note || !newEvent.date) {
      return new Error('All fields are required.');
    }

    // Format date for MySQL
    const formattedDate = formatDateForMySQL(newEvent.date);

    let response;

    if (isEditMode.value) {
      // Update existing event
      response = await fetch(`${wpData.apiUrl}/events/${editingEventId.value}`, {
        method: 'PUT',
        headers: {
          'X-WP-Nonce': wpData.nonce,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          ...newEvent,
          date: formattedDate
        })
      });
    } else {
      // Create new event
      response = await fetch(`${wpData.apiUrl}/events`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': wpData.nonce,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          ...newEvent,
          date: formattedDate
        })
      });
    }

    if (!response.ok) {
      const errorData = await response.json();
      return new Error(errorData.message || 'Failed to save event.');
    }

    // Close modal and reset form
    closeCreateModal();

    // Refresh events list
    fetchEvents(1).then(() => isSubmitting.value = false);
  } catch (err) {
    console.error('Error creating event:', err);
    alert(err.message);
    isSubmitting.value = false;
  }
};
// Add a new category
const addNewCategory = () => {
  const createdCategory = newCategory.value.trim();
  // Add to categories list
  categories.value.push(newCategory.value.trim());
  console.log(categories);
  // Select it in the form
  newEvent.category = createdCategory;

  // Reset input and hide
  newCategory.value = '';
  showNewCategoryInput.value = false;
};

const closeCreateModal = () => {
  showCreateModal.value = false;
  isEditMode.value = false;
  editingEventId.value = null;
  // Reset form
  Object.assign(newEvent, {
    event_name: '',
    category: '',
    importance: '',
    note: '',
    date: formatDateTimeForInput(new Date())
  });
  showNewCategoryInput.value = false;
  newCategory.value = '';
};

// Format date for MySQL
function formatDateForMySQL(dateString) {
  const date = new Date(dateString);
  return formatDateTimeForInput(date);
}
</script>
