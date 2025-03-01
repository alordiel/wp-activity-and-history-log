/**
 * WP Activity Tracker Vue.js application
 *
 * This script handles the frontend functionality for the WP Activity Tracker plugin.
 */
(function () {
    // Wait for DOM content to be loaded
    document.addEventListener('DOMContentLoaded', function () {
        // Create Vue app
        const {createApp, ref, reactive, onMounted, computed} = Vue;

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
                const isEditMode = ref(false);
                const editingEventId = ref(null);
                const isSubmitting = ref(false);
                const isDeleting = ref(false);
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
                    return formatDateTimeForInput(date);
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
                            return  new Error(`API Error: ${response.status}`);
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

                // Create a new event
                const createEvent = async () => {
                    isSubmitting.value = true;

                    try {
                        // Validate form
                        if (!newEvent.event_name || !newEvent.category || !newEvent.importance || !newEvent.note || !newEvent.date) {
                            return  new Error('All fields are required.');
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
                    isDeleting,
                    isEditMode,
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
                    openEditModal,
                    deleteEvent,

                    // Computed
                    wpData
                };
            }
        });

        // Mount the app
        app.mount('#wp-activity-tracker-app');
    });
})();