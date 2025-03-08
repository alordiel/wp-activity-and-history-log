<template>
  <div class="dashboard-container p-6">
    <!-- Header with Add Button -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Reports Dashboard</h1>
      <button
        @click="openNewDashboardModal"
        class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg flex items-center"
      >
        <span class="mr-2">Add New Dashboard</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Report Name
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date Created
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Entries
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="report in reports" :key="report.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="font-medium text-gray-900">{{ report.name }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-500">{{ formatDate(report.dateCreated) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ report.entries }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <div class="flex space-x-3">
                <button @click="viewReport(report.id)" class="text-blue-600 hover:text-blue-900">
                  View
                </button>
                <button @click="editReport(report.id)" class="text-indigo-600 hover:text-indigo-900">
                  Edit
                </button>
                <button @click="confirmDelete(report.id)" class="text-red-600 hover:text-red-900">
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="reports.length === 0">
            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
              No reports found. Create your first dashboard!
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- New Dashboard Modal -->
    <NewDashboardModal
      v-if="showModal"
      :availableRoles="availableRoles"
      @close="closeNewDashboardModal"
      @save="saveDashboard"
    />
  </div>
</template>
<script>
import { ref, onMounted } from 'vue';
import NewDashboardModal from '../components/NewDashboardModal.vue';
import { useRouter } from 'vue-router';
export default {
  name: 'Dashboard',
  components: {
    NewDashboardModal
  },
  setup() {
    const router = useRouter();
    const reports = ref([]);
    const showModal = ref(false);
    const availableRoles = ref(['Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber']);

    // Load reports data (you would fetch this from your API)
    onMounted(() => {
      // Example data - replace with actual API call
      reports.value = [
        {
          id: 1,
          name: 'Monthly Sales Report',
          dateCreated: new Date('2025-02-15'),
          entries: 243
        },
        {
          id: 2,
          name: 'User Acquisition Dashboard',
          dateCreated: new Date('2025-03-01'),
          entries: 156
        },
        {
          id: 3,
          name: 'Product Performance Overview',
          dateCreated: new Date('2025-03-05'),
          entries: 89
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
      showModal.value = true;
    };

    const closeNewDashboardModal = () => {
      showModal.value = false;
    };

    const saveDashboard = (dashboardData) => {
      // You would send this to your API
      console.log('Saving dashboard:', dashboardData);

      // Add to local reports array for now (for demo purposes)
      const newReport = {
        id: reports.value.length + 1,
        name: dashboardData.name,
        dateCreated: new Date(),
        entries: 0,
        importanceColumn: dashboardData.includeImportance,
        roleVisibility: dashboardData.selectedRoles
      };

      reports.value.push(newReport);
      showModal.value = false;
    };

    const viewReport = (id) => {
      router.push(`/dashboard/${id}`);
    };

    const editReport = (id) => {
      console.log('Edit report:', id);
      // Implement edit logic
    };

    const confirmDelete = (id) => {
      if (confirm('Are you sure you want to delete this report?')) {
        // Filter out the deleted report
        reports.value = reports.value.filter(report => report.id !== id);
        console.log('Deleted report:', id);
      }
    };

    return {
      reports,
      showModal,
      availableRoles,
      formatDate,
      openNewDashboardModal,
      closeNewDashboardModal,
      saveDashboard,
      viewReport,
      editReport,
      confirmDelete
    };
  }
};
</script>