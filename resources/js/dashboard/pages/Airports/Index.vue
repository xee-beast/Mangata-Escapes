<template>
    <card title="Airports">
        <template v-if="can.create" v-slot:action>
                <button @click="openCreateModel" type="button" title="Add Airport" aria-pressed="false" class="button is-outlined is-primary is-inverted">Add Airport</button>
        </template>
        <data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search Airports" />
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
                    <p class="modal-card-title">{{ isEditMode ? 'Edit Airport' : 'Add New Airport' }}</p>
                    <button class="delete" aria-label="close" @click="closeModal"></button>
                </header>
                <section class="modal-card-body">
                    <form>
                        <div class="">
                            <div class="field column m-0">
                                <label class="label is-required">Airport IATA Code</label>
                                <div class="control">
                                    <input class="input" v-model="form.airport_code" type="text" required />
                                </div>
                                <p class="help is-danger" v-if="errors.airport_code">{{ errors.airport_code[0] }}</p>
                            </div>
                            <div class="field column m-2">
                                <label class="label is-required">Timezone</label>
                                <div class="control">
                                    <control-select 
                                        v-model="form.timezone"
                                        required
                                        :options="timezoneOptions"
                                    />
                                </div>
                                <p class="help is-danger" v-if="errors.timezone">{{ errors.timezone[0] }}</p>
                            </div>
                            <div class="field column m-2">
                                <label class="label">Default Transfer Provider</label>
                                <div class="control">
                                    <control-select 
                                        v-model="form.transfer_id"
                                        :options="transferOptions"
                                        first-is-empty
                                    />
                                </div>
                                <p class="help is-danger" v-if="errors.transfer_id">{{ errors.transfer_id[0] }}</p>
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
        <data-table class="is-size-6" table-class="is-fullwidth" :columns="['Airport IATA Code', 'Timezone', 'Default Transfer Provider', 'Actions']">
			<template  v-if="airports.length">
				<tr v-for="airport in airports" :key="airport.id">
					<th>{{airport.airport_code}}</th>
					<td>{{ airport.timezone }}</td>
                    <td>{{ airport.transfer ? airport.transfer.name : '-' }}</td>
                    <td>
						<a v-if="airport.can.view || airport.can.update" class="table-action" @click.prevent="show(airport)" title="Show/Edit Airport">
							<i class="fas fa-edit"></i>
						</a>
						<a v-if="airport.can.delete" class="table-action" @click.prevent="deleteAirport = airport" title="Delete Airport">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
            <tr v-else>
                <td>No airports found...</td>
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
        <delete-airport v-if="deleteAirport" :airport="deleteAirport" @deleted="deleted" @canceled="deleteAirport = null" />
    </card>
</template>
<script>
    import Card from '@dashboard/components/Card';
    import DataTable from '@dashboard/components/table/Table';
    import DeleteAirport from '@dashboard/pages/Airports/Delete';
    import PaginationFilter from '@dashboard/components/pagination/Filter';
    import Paginator from '@dashboard/components/pagination/Paginator';
    import DataFilter from '@dashboard/components/table/Filter';
    import DataFilters from '@dashboard/components/table/Filters';
    import FormField from '@dashboard/components/form/Field';
    import ControlInput from '@dashboard/components/form/controls/Input';
    import ControlButton from '@dashboard/components/form/controls/Button';
    import ControlSelect from '@dashboard/components/form/controls/Select';

    export default {
        name: 'AirportsComponent',

        components: {
            Card,
            DataTable,
            DeleteAirport,
            PaginationFilter,
            Paginator,
            DataFilter,
            DataFilters,
            FormField,
            ControlInput,
            ControlButton,
		    ControlSelect,
        },

        data() {
            return {
                showModal: false,
                isEditMode: false,
                errors: {},
                can: {},
                airports: [],
                meta: {},
                timezoneOptions: [],
                transferOptions: [],
                filters: {
                    paginate: 10,
                    page: 1,
                    search: '',
                },
                form: {
                    airport_code: '',
                    timezone: '',
                    transfer_id: null,
                },
                deleteAirport: null,
                airportId: null,
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
			    this.$http.get('/airports' + this.query)
				    .then(response => {
                        this.airports = response.data.data;
                        this.meta = response.data.meta;
                        this.can = response.data.can;
                        this.timezoneOptions = response.data.timezoneOptions.map(timezone => ({ value: timezone, text: timezone }));
                        this.transferOptions = response.data.transfers.map(transfer => ({ value: transfer.id, text: transfer.name }));
                        this.setBreadcrumbs();
				    });
		    },

            openCreateModel() {
                this.resetForm();
                this.errors = {};
                this.isEditMode = false;
                this.airportId = null;
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
            },

            handleSubmit() {
                const url = this.isEditMode && this.airportId ? 'airports/' + this.airportId : '/airports';
                const method = this.isEditMode && this.airportId ? 'put' : 'post';

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
                this.airportId = null;
                this.errors = {};
                const message = this.isEditMode ? 'Airport has been updated' : 'Airport created successfully';
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
                    airport_code: '',
                    timezone: '',
                    transfer_id: null,
                };
            },

            show(airport) {
                this.errors = {};
                this.isEditMode = true;
                this.showModal = true;
                this.airportId = airport.id;
                this.form.airport_code = airport.airport_code;
                this.form.timezone = airport.timezone;
                this.form.transfer_id = airport.transfer_id;
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
                        label: 'Airports',
                        route: 'airports'
                    }
			    ]);
		    },

            deleted() {
                this.deleteAirport = null;
                this.fetchData();
		    },
        },
    }
</script>
<style scoped>
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