/**
 * WP Activity Tracker Vue.js application
 *
 * This script handles the frontend functionality for the WP Activity Tracker plugin.
 */
(function() {
    // Wait for DOM content to be loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Create Vue app
        const { createApp, ref, reactive, onMounted, computed } = Vue;

        // Get WordPress data
        const wpData = window.wpActivityTracker || {
            apiUrl: '',
            nonce: '',
            categories: [],
            importanceOptions: {}
        };

        // Create the app
        const app = createApp({
            setup() {
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

                // Format date for MySQL
                function formatDateForMySQL(dateString) {
                    const date = new Date(dateString);
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    const seconds = String(date.getSeconds()).padStart(2, '0');

                    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
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
                        const response = await fetch(`${wpData.apiUrl}/events`, {
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

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || __('Failed to create event.'));
                        }

                        // Close modal and reset form
                        closeCreateModal();

                        // Refresh events list
                        fetchEvents(1);
                    } catch (err) {
                        console.error('Error creating event:', err);
                        alert(err.message);
                    } finally {
                        isSubmitting.value = false;
                    }
                };

                // Add a new category
                const addNewCategory = () => {
                    if (newCategory.value.trim()) {
                        // Add to categories list
                        wpData.categories.push(newCategory.value.trim());

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
                    newEvent.date = formatDateTimeForInput(new Date());
                };

                const closeCreateModal = () => {
                    showCreateModal.value = false;
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
                    wpData
                };
            }
        });

        // Mount the app
        app.mount('#wp-activity-tracker-app');
    });
})();