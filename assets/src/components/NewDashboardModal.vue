<template>
  <div class="fixed inset-0 overflow-y-auto z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
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
                  <div class="mt-2 space-x-6 flex">
                    <div class="flex items-center">
                      <input
                        id="importance-yes"
                        name="importance"
                        type="radio"
                        value="yes"
                        v-model="includeImportance"
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300"
                      />
                      <label for="importance-yes" class="ml-2 block text-sm text-gray-700">
                        Yes
                      </label>
                    </div>
                    <div class="flex items-center">
                      <input
                        id="importance-no"
                        name="importance"
                        type="radio"
                        value="no"
                        v-model="includeImportance"
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300"
                      />
                      <label for="importance-no" class="ml-2 block text-sm text-gray-700">
                        No
                      </label>
                    </div>
                  </div>
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
</template>

<script>
import { ref, computed } from 'vue';

export default {
  name: 'NewDashboardModal',
  props: {
    availableRoles: {
      type: Array,
      required: true
    }
  },
  emits: ['close', 'save'],
  setup(props, { emit }) {
    const dashboardName = ref('');
    const includeImportance = ref('no');
    const selectedRoles = ref(['Administrator']);

    const isFormValid = computed(() => {
      return dashboardName.value.trim() !== '' && selectedRoles.value.length > 0;
    });

    const saveDashboard = () => {
      if (!isFormValid.value) return;

      emit('save', {
        name: dashboardName.value,
        includeImportance: includeImportance.value === 'yes',
        selectedRoles: selectedRoles.value
      });

      // Reset form after saving
      dashboardName.value = '';
      includeImportance.value = 'no';
      selectedRoles.value = ['Administrator'];
    };

    return {
      dashboardName,
      includeImportance,
      selectedRoles,
      isFormValid,
      saveDashboard
    };
  }
};
</script>