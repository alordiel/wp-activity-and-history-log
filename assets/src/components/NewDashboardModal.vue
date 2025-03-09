<template>
  <div class="fixed inset-0 overflow-y-auto z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div
          class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                Create New Dashboard
              </h3>

              <div class="mt-6 space-y-6">
                <!-- Dashboard Name -->
                <div>
                  <label for="dashboard-name" class="block text-sm font-medium text-gray-700">
                    Dashboard Name
                  </label>
                  <div class="mt-1">
                    <input
                        type="text"
                        id="dashboard-name"
                        v-model="dashboardName"
                        class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        placeholder="Enter dashboard name"
                    />
                  </div>
                </div>

                <!-- Importance Column -->
                <div>
                  <span class="block text-sm font-medium text-gray-700">Include Importance Column</span>
                  <div>
                    <button
                        type="button"
                        @click="toggleIncludeImportance"
                        class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        :class="includeImportance ? 'bg-blue-600' : 'bg-gray-200'"
                    >
                      <span
                          class="pointer-events-none relative inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                          :class="includeImportance ? 'translate-x-5' : 'translate-x-0'"
                      />
                    </button>
                  </div>

                  <!-- Role Visibility -->
                  <div>
                    <span class="block text-sm font-medium text-gray-700">Role Visibility</span>
                    <div class="mt-2 space-y-2">
                      <div v-for="role in availableRoles" :key="role" class="flex items-start">
                        <div class="flex items-center h-5">
                          <input
                              :id="`role-${role.toLowerCase()}`"
                              type="checkbox"
                              :value="role"
                              v-model="selectedRoles"
                              class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                          />
                        </div>
                        <div class="ml-3 text-sm">
                          <label :for="`role-${role.toLowerCase()}`" class="font-medium text-gray-700">
                            {{ role }}
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
                type="button"
                @click="saveDashboard"
                :disabled="!isFormValid"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': !isFormValid }"
            >
              Create Dashboard
            </button>
            <button
                type="button"
                @click="$emit('close')"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {ref, computed, watch} from 'vue';


const props = defineProps(['availableRoles', 'reportData'])

const emits = ['close', 'save'];

const dashboardName = ref('');
const includeImportance = ref(true);
const selectedRoles = ref(['Administrator']);

const isFormValid = computed(() => {
  return dashboardName.value.trim() !== '' && selectedRoles.value.length > 0;
});

// Watch for changes in reportData to update form values
watch(() => props.reportData, (newData) => {
  if (newData) {
    dashboardName.value = newData.name || '';
    includeImportance.value = newData.includeImportance;
    selectedRoles.value = [...(newData.selectedRoles || ['Administrator'])];
  } else {
    // Reset form if not in edit mode
    dashboardName.value = '';
    includeImportance.value = true;
    selectedRoles.value = ['Administrator'];
  }
}, {immediate: true});

const saveDashboard = () => {
  if (!isFormValid.value) return;

  emit('save', {
    name: dashboardName.value,
    includeImportance: includeImportance.value,
    selectedRoles: selectedRoles.value
  });

  // Reset form after saving
  dashboardName.value = '';
  includeImportance.value = true;
  selectedRoles.value = ['Administrator'];
};

const toggleIncludeImportance = () => {
  includeImportance.value = !includeImportance.value;
}

</script>