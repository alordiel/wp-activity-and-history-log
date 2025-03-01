<div id="wp-activity-tracker-app" class="wrap">
    <div v-cloak>
        <header class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold"><?php _e('WP Activity Tracker','wp-activity-tracker') ?></h1>
            <button
                    @click="openCreateModal"
                    class="px-4 py-2 font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition"
            >
                <?php _e('Create Manual Event','wp-activity-tracker') ?>
            </button>
        </header>

        <div class="mb-6 bg-gray-100 p-4 rounded flex flex-wrap gap-4">
            <!-- Search & Filter Bar -->
            <div class="flex-1 min-w-[280px]">
                <input
                        v-model="search"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded"
                        placeholder="<?php _e('Search events...','wp-activity-tracker')?>"
                        @input="debounceSearch"
                />
            </div>

            <div class="flex-1 min-w-[200px]">
                <select
                        v-model="filters.category"
                        class="w-full px-3 py-2 border border-gray-300 rounded"
                        @change="fetchEvents(1)"
                >
                    <option value=""><?php _e('All Categories','wp-activity-tracker') ?></option>
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
                    <option value=""><?php _e('All Types','wp-activity-tracker') ?></option>
                    <option value="manual"><?php _e('Manual','wp-activity-tracker') ?></option>
                    <option value="automatic"><?php _e('Automatic','wp-activity-tracker') ?></option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <select
                        v-model="filters.importance"
                        class="w-full px-3 py-2 border border-gray-300 rounded"
                        @change="fetchEvents(1)"
                >
                    <option value=""><?php _e('All Importance','wp-activity-tracker') ?></option>
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
                    <th class="px-4 py-2 text-left border-b"><?php _e('Event','wp-activity-tracker') ?></th>
                    <th class="px-4 py-2 text-left border-b"><?php _e('Category','wp-activity-tracker') ?></th>
                    <th class="px-4 py-2 text-left border-b"><?php _e('User','wp-activity-tracker') ?></th>
                    <th class="px-4 py-2 text-left border-b"><?php _e('Type','wp-activity-tracker') ?></th>
                    <th class="px-4 py-2 text-left border-b"><?php _e('Importance','wp-activity-tracker') ?></th>
                    <th class="px-4 py-2 text-left border-b"><?php _e('Date','wp-activity-tracker') ?></th>
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
                            <span v-else
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
                                    <span
                                            :class="[
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
                </tr>
                </tbody>
                <tbody v-else>
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        <div v-if="loading"><?php _e('Loading events...','wp-activity-tracker') ?></div>
                        <div v-else><?php _e('No events found.','wp-activity-tracker') ?></div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex justify-between items-center mt-6">
            <div class="text-sm text-gray-600">
                <?php _e('Showing','wp-activity-tracker') ?>
                {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, totalItems) }}
                <?php _e('of','wp-activity-tracker') ?> {{ totalItems }} <?php _e('events','wp-activity-tracker') ?>
            </div>
            <div class="flex space-x-2">
                <button
                        @click="fetchEvents(1)"
                        :disabled="currentPage === 1"
                        :class="[
                                'px-3 py-1 rounded',
                                currentPage === 1
                                ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                : 'bg-blue-600 text-white hover:bg-blue-700'
                            ]"
                >
                    <?php _e('First','wp-activity-tracker') ?>
                </button>
                <button
                        @click="fetchEvents(currentPage - 1)"
                        :disabled="currentPage === 1"
                        :class="[
                                'px-3 py-1 rounded',
                                currentPage === 1
                                ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                : 'bg-blue-600 text-white hover:bg-blue-700'
                            ]"
                >
                    <?php _e('Previous','wp-activity-tracker') ?>
                </button>
                <button
                        @click="fetchEvents(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        :class="[
                                'px-3 py-1 rounded',
                                currentPage === totalPages
                                ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                : 'bg-blue-600 text-white hover:bg-blue-700'
                            ]"
                >
                    <?php _e('Next','wp-activity-tracker') ?>
                </button>
                <button
                        @click="fetchEvents(totalPages)"
                        :disabled="currentPage === totalPages"
                        :class="[
                                'px-3 py-1 rounded',
                                currentPage === totalPages
                                ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                : 'bg-blue-600 text-white hover:bg-blue-700'
                            ]"
                >
                    <?php _e('Last','wp-activity-tracker') ?>
                </button>
            </div>
        </div>

        <!-- Create Event Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl overflow-hidden">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h2 class="text-xl font-bold"><?php _e('Create Manual Event','wp-activity-tracker') ?></h2>
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
                                <?php _e('Event Name','wp-activity-tracker') ?> *
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
                                <?php _e('Category','wp-activity-tracker') ?> *
                            </label>
                            <div class="flex space-x-2">
                                <select
                                        id="category"
                                        v-model="newEvent.category"
                                        required
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded"
                                >
                                    <option value=""><?php _e('Select Category','wp-activity-tracker') ?></option>
                                    <option v-for="category in wpData.categories" :key="category" :value="category">
                                        {{ category }}
                                    </option>
                                </select>
                                <button
                                        type="button"
                                        @click="showNewCategoryInput = !showNewCategoryInput"
                                        class="px-3 py-2 text-blue-600 border border-blue-600 rounded hover:bg-blue-600 hover:text-white"
                                >
                                    {{ showNewCategoryInput ? "<?php _e('Cancel','wp-activity-tracker')?>" : "<?php _e('New','wp-activity-tracker') ?>" }}
                                </button>
                            </div>
                            <input
                                    v-if="showNewCategoryInput"
                                    v-model="newCategory"
                                    type="text"
                                    placeholder="<?php _e('Enter new category','wp-activity-tracker')?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded mt-2"
                                    @keyup.enter="addNewCategory"
                            />
                        </div>

                        <div>
                            <label for="importance" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php _e('Importance','wp-activity-tracker') ?> *
                            </label>
                            <select
                                    id="importance"
                                    v-model="newEvent.importance"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded"
                            >
                                <option value=""><?php _e('Select Importance','wp-activity-tracker') ?></option>
                                <option v-for="(label, value) in wpData.importanceOptions" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="note" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php _e('Note','wp-activity-tracker') ?> *
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
                                <?php _e('Date','wp-activity-tracker') ?> *
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
                            <?php _e('Cancel','wp-activity-tracker') ?>
                        </button>
                        <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ isSubmitting ? "<?php _e('Creating...','wp-activity-tracker')?>" : "<?php _e('Create Event','wp-activity-tracker') ?>" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>