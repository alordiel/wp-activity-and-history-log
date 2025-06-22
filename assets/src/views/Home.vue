<template>
  <div class="dashboard-container p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Reports Dashboard</h1>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Dashboard Cards -->
      <div
          v-for="report in reports"
          :key="report.id"
          class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden"
      >
        <!-- Clickable Content Area -->
        <div
            @click="viewReport(report.id)"
            class="cursor-pointer p-6 flex-1"
        >
          <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
            {{ report.name }}
          </h3>
          <p class="text-gray-600 text-sm mb-4 line-clamp-3">
            {{ report.description }}
          </p>
          <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
            <span>{{ formatDate(report.dateCreated) }}</span>
            <span>{{ report.entries }} entries</span>
          </div>
        </div>

        <!-- Action Footer -->
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex justify-end space-x-2">
          <button
              @click.stop="editReport(report.id)"
              class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 transition-colors duration-150"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit
          </button>
          <button
              @click.stop="confirmDelete(report.id)"
              class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded hover:bg-red-100 transition-colors duration-150"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete
          </button>
        </div>
      </div>

      <!-- Add New Dashboard Card -->
      <div
          @click="openNewDashboardModal"
          class="bg-blue-50 border-2 border-dashed border-blue-200 rounded-lg shadow-sm hover:shadow-md hover:bg-blue-100 transition-all duration-200 cursor-pointer flex items-center justify-center min-h-[200px]"
      >
        <div class="text-center p-6">
          <div class="mx-auto w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-blue-800">Add New Dashboard</h3>
          <p class="text-sm text-blue-600 mt-1">Create a new activity dashboard</p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="reports.length === 0" class="text-center py-12">
      <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No dashboards yet</h3>
      <p class="text-gray-500 mb-4">Get started by creating your first activity dashboard</p>
      <button
          @click="openNewDashboardModal"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-150"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Create Dashboard
      </button>
    </div>

    <!-- New Dashboard Modal -->
    <NewDashboardModal
        v-if="showModal"
        :availableRoles="availableRoles"
        :editMode="!!editingReport"
        :reportData="editingReport"
        @close="closeNewDashboardModal"
        @save="saveDashboard"
    />
  </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import NewDashboardModal from '../components/NewDashboardModal.vue';
import {useRouter} from 'vue-router';

const router = useRouter();
const reports = ref([]);
const showModal = ref(false);
const editingReport = ref(null);
const availableRoles = ref(['Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber']);

// Load reports data (you would fetch this from your API)
onMounted(() => {
  // Example data - replace with actual API call
  // Added description field to existing data
  reports.value = [
    {
      id: 1,
      name: 'Monthly Sales Report',
      description: 'Comprehensive analysis of monthly sales performance, including revenue trends, top-performing products, and customer acquisition metrics.',
      dateCreated: new Date('2025-02-15'),
      entries: 243,
      includeImportance: true,
      selectedRoles: ['Administrator', 'Editor', 'Author'],
    },
    {
      id: 2,
      name: 'User Acquisition Dashboard',
      description: 'Track user registration, engagement rates, and conversion funnel performance across different marketing channels.',
      dateCreated: new Date('2025-03-01'),
      entries: 156,
      includeImportance: false,
      selectedRoles: ['Editor'],
    },
    {
      id: 3,
      name: 'Product Performance Overview',
      description: 'Monitor product usage statistics, feature adoption rates, and user feedback to optimize product development.',
      dateCreated: new Date('2025-03-05'),
      entries: 89,
      includeImportance: true,
      selectedRoles: ['Administrator', 'Author'],
    }
  ];
});

const formatDate = (date) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(date);
};

const openNewDashboardModal = () => {
  editingReport.value = null;
  showModal.value = true;
};

const closeNewDashboardModal = () => {
  showModal.value = false;
};

const saveDashboard = (dashboardData) => {
  if (editingReport.value) {
    // Edit existing report
    const index = reports.value.findIndex(r => r.id === editingReport.value.id);
    if (index !== -1) {
      // Update existing report with new data
      reports.value[index] = {
        ...reports.value[index],
        name: dashboardData.name,
        description: dashboardData.description || reports.value[index].description,
        includeImportance: dashboardData.includeImportance,
        selectedRoles: dashboardData.selectedRoles
      };
    }
  } else {
    // Add new report
    const newReport = {
      id: reports.value.length + 1,
      name: dashboardData.name,
      description: dashboardData.description || 'No description provided',
      dateCreated: new Date(),
      entries: 0,
      includeImportance: dashboardData.includeImportance,
      selectedRoles: dashboardData.selectedRoles
    };

    reports.value.push(newReport);
  }
  showModal.value = false;
};

const viewReport = (id) => {
  router.push(`/dashboard/${id}`);
};

const editReport = (id) => {
  const report = reports.value.find(report => report.id === id);
  if (report) {
    editingReport.value = {...report}; // Clone to avoid direct mutation
    showModal.value = true;
  }
};

const confirmDelete = (id) => {
  if (confirm('Are you sure you want to delete this dashboard?')) {
    // Filter out the deleted report
    reports.value = reports.value.filter(report => report.id !== id);
    console.log('Deleted dashboard:', id);
  }
};
</script>

<style scoped>
/* Utility classes for text truncation */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>