<template>
    <card title="Event Types">
        <template v-if="can.create" v-slot:action>
                <button @click="openCreateModel" type="button" title="Add Event Type" aria-pressed="false" class="button is-outlined is-primary is-inverted">Add Event Type</button>
        </template>
        <data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search Event Types" />
					<template v-slot:addon>
						<control-button @click="filterData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
					</template>
				</form-field>
			</data-filter>
		</data-filters>
        <div class="modal" :class="{ 'is-active': showModal }">
            <div class="modal-background" @click="closeModal"></div>
            <div class="modal-card">
                <header class="modal-card-head">
                    <p class="modal-card-title">{{ isEditMode ? 'Edit Event Type' : 'Add New Event Type' }}</p>
                    <button class="delete" aria-label="close" @click="closeModal"></button>
                </header>
                <section class="modal-card-body">
                    <form>
                        <div class="">
                            <div class="field column m-0">
                                <label class="label is-required">Name</label>
                                <div class="control">
                                    <input class="input" :readonly="form.is_default == 1" v-model="form.event_name" type="text" required />
                                    <small v-if="form.is_default == 1" class="notification is-primary">
                                        This is a default event type and its name cannot be modified.
                                    </small>
                                </div>
                                <p class="help is-danger" v-if="errors.event_name">{{ errors.event_name[0] }}</p>
                            </div>
                            <div class="field column m-2">
                                <label class="label is-required">Event Color</label>
                                <div class="control">
                                    <input class="input" v-model="form.color" type="color" required />
                                </div>
                                <p class="help is-danger" v-if="errors.color">{{ errors.color[0] }}</p>
                            </div>
                        </div>
                    </form>
                </section>
                <footer class="modal-card-foot">
                    <button class="button is-primary" @click="handleSubmit" :class="{ 'is-loading': isLoading }" :disabled="isLoading">{{isEditMode ? "Update" : "Save"}}</button>
                    <button class="button" @click="closeModal" :disabled="isLoading">Cancel</button>
                </footer>
            </div>
        </div>
        <data-table class="is-size-6" table-class="is-fullwidth" :columns="['Event Name', 'Event Color', 'Default', 'Actions']">
			<template  v-if="events.length">
				<tr v-for="event in events" :key="event.id">
					<th>{{event.name}}</th>
					<td>
                        <div class="color-container">
                            <span :style="{ backgroundColor: event.color }" class="color-circle"></span>
                            {{ event.color }}
                        </div>
                    </td>
					<td>{{event.is_default ? 'Yes' : 'No' }}</td>
                    <td>
						<a v-if="event.can.view || event.can.update" class="table-action" @click.prevent="show(event)" title="Show/Edit Event">
							<i class="fas fa-edit"></i>
						</a>
						<a v-if="event.can.delete && !event.is_default" class="table-action" @click.prevent="deleteEvent = event" title="Delete Event">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
            <tr v-else>
                <td>No event types found...</td>
            </tr>
		</data-table>
        <paginator
            v-if="meta.total > 10" 
            @change="filterData"
            :current-page="meta.current_page" 
            :last-page="meta.last_page" 
            :from="meta.from" 
            :to="meta.to" 
            :total="meta.total"
        />
        <delete-event v-if="deleteEvent" :event="deleteEvent" @deleted="deleted" @canceled="deleteEvent = null" />
    </card>
</template>
<script>
    import Card from '@dashboard/components/Card';
    import DataTable from '@dashboard/components/table/Table';
    import DeleteEvent from '@dashboard/pages/Event/Delete';
    import PaginationFilter from '@dashboard/components/pagination/Filter';
    import Paginator from '@dashboard/components/pagination/Paginator';
    import DataFilter from '@dashboard/components/table/Filter';
    import DataFilters from '@dashboard/components/table/Filters';
    import FormField from '@dashboard/components/form/Field';
    import ControlInput from '@dashboard/components/form/controls/Input';
    import ControlButton from '@dashboard/components/form/controls/Button';

    export default {
        name: 'CalendarComponent',

        components: {
            Card,
            DataTable,
            DeleteEvent,
            PaginationFilter,
            Paginator,
            DataFilter,
            DataFilters,
            FormField,
            ControlInput,
            ControlButton,
        },

        data() {
            return {
                showModal: false,
                isEditMode: false,
                errors: {},
                can: {},
                meta: {},
                events: [],
                filters: {
                    paginate: 10,
                    page: 1,
                    search: '',
                },
                form: {
                    event_name: '',
                    color: '#D1B7B7',
                    is_default: 0,
                },
                deleteEvent: null,
                eventId: null,
                isLoading: false,
            };
        },

        created() {
            this.filters = Object.assign({}, this.filters, this.$route.query);
            this.fetchData();
	    },

        computed: {
            query() {
                return '?' + Object.keys(this.filters).map(key => key + '=' + this.filters[key]).join('&');
            }
	    },

        methods: {
            fetchData() {
			    this.$http.get('/calendar-events' + this.query)
				    .then(response => {
                        this.events = response.data.data;
                        this.meta = response.data.meta;
                        this.can = response.data.can;
                        this.setBreadcrumbs();
				    });
		    },

            openCreateModel() {
                this.resetForm();
                this.errors = {};
                this.isEditMode = false;
                this.eventId = null;
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
            },

            handleSubmit() {
                const url = this.isEditMode && this.eventId ? 'calendar-events/' + this.eventId : '/calendar-events';
                const method = this.isEditMode && this.eventId ? 'put' : 'post';

                this.submitForm(url, method);
            },

            submitForm(url, method) {
                this.isLoading = true;
                this.$http[method](url, this.form)
                    .then(response => {
                        this.onSuccess();
                    })
                    .catch(error => {
                        this.onError(error);
                        this.isLoading = false;
                    });
            },

            onSuccess() {
                this.fetchData();
                this.resetForm();
                this.showModal = false;
                this.eventId = null;
                this.errors = {};
                const message = this.isEditMode ? 'Event Type has been updated' : 'Event Type created successfully';
                this.isEditMode = false;
                this.isLoading = false;
                this.$store.commit('notification', {
                    type: 'success',
                    message: message
                });
            },

            onError(error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors || {};
                }
            },

            resetForm() {
                this.form = {
                    event_name: '',
                    color: '#D1B7B7',
                    is_default: 0,
                };
            },

            show(event) {
                this.errors = {};
                this.isEditMode = true;
                this.showModal = true;
                this.eventId = event.id;
                this.form.event_name = event.name;
                this.form.color = event.color;
                this.form.is_default = event.is_default;
		    },

            filterData(page = '1') {
                this.$set(this.filters, 'page', page);

                if (JSON.stringify(this.$route.query) !== JSON.stringify(this.filters)) {
                    this.$router.replace({
                        query: this.filters
                    });

                    this.fetchData();
                }
		    },

		    setBreadcrumbs() {
			    this.$store.commit('breadcrumbs', [
                    {
                        label: 'Dashboard',
                        route: 'home'
                    },
                    {
                        label: 'Events Types',
                        route: 'event-types'
                    }
			    ]);
		    },

            deleted() {
                this.deleteEvent = null;
                this.fetchData();
		    },
        },
    }
</script>
<style scoped>
    .color-container {
        display: flex;
        align-items: center;
    }

    .color-circle {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        margin-right: 8px;
    }

    .field:not(:last-child) {
        margin-bottom: 0rem;
    }

    .m-2 {
            margin-bottom: 0.75rem;
    }

    .modal-card-body {
        max-height: 70vh;
        overflow-y: auto;
    }
</style>