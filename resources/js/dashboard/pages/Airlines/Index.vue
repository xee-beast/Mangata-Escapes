<template>
    <card title="Airlines">
        <template v-if="can.create" v-slot:action>
                <button @click="openCreateModel" type="button" title="Add Airline" aria-pressed="false" class="button is-outlined is-primary is-inverted">Add Airline</button>
        </template>
        <data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search Airlines" />
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
                    <p class="modal-card-title">{{ isEditMode ? 'Edit Airline' : 'Add New Airline' }}</p>
                    <button class="delete" aria-label="close" @click="closeModal"></button>
                </header>
                <section class="modal-card-body">
                    <form>
                        <div class="">
                            <div class="field column m-0">
                                <label class="label is-required">Airline Name</label>
                                <div class="control">
                                    <input class="input" v-model="form.name" type="text" required />
                                </div>
                                <p class="help is-danger" v-if="errors.name">{{ errors.name[0] }}</p>
                            </div>
                            <div class="field column m-2">
                                <label class="label is-required">IATA Code</label>
                                <div class="control">
                                    <input class="input" v-model="form.iata_code" type="text" required />
                                </div>
                                <p class="help is-danger" v-if="errors.iata_code">{{ errors.iata_code[0] }}</p>
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
        <data-table class="is-size-6" table-class="is-fullwidth" :columns="['Airline Name', 'IATA Code', 'Actions']">
			<template  v-if="airlines.length">
				<tr v-for="airline in airlines" :key="airline.id">
					<th>{{airline.name}}</th>
					<td>{{ airline.iata_code }}</td>
                    <td>
						<a v-if="airline.can.view || airline.can.update" class="table-action" @click.prevent="show(airline)" title="Show/Edit Airline">
							<i class="fas fa-edit"></i>
						</a>
						<a v-if="airline.can.delete" class="table-action" @click.prevent="deleteAirline = airline" title="Delete Airline">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
            <tr v-else>
                <td>No airlines found...</td>
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
        <delete-airline v-if="deleteAirline" :airline="deleteAirline" @deleted="deleted" @canceled="deleteAirline = null" />
    </card>
</template>
<script>
    import Card from '@dashboard/components/Card';
    import DataTable from '@dashboard/components/table/Table';
    import DeleteAirline from '@dashboard/pages/Airlines/Delete';
    import PaginationFilter from '@dashboard/components/pagination/Filter';
    import Paginator from '@dashboard/components/pagination/Paginator';
    import DataFilter from '@dashboard/components/table/Filter';
    import DataFilters from '@dashboard/components/table/Filters';
    import FormField from '@dashboard/components/form/Field';
    import ControlInput from '@dashboard/components/form/controls/Input';
    import ControlButton from '@dashboard/components/form/controls/Button';

    export default {
        name: 'AirlinesComponent',

        components: {
            Card,
            DataTable,
            DeleteAirline,
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
                airlines: [],
                meta: {},
                filters: {
                    paginate: 10,
                    page: 1,
                    search: '',
                },
                form: {
                    name: '',
                    iata_code: '',
                },
                deleteAirline: null,
                airlineId: null,
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
			    this.$http.get('/airlines' + this.query)
				    .then(response => {
                        this.airlines = response.data.data;
                        this.meta = response.data.meta;
                        this.can = response.data.can;
                        this.setBreadcrumbs();
				    });
		    },

            openCreateModel() {
                this.resetForm();
                this.errors = {};
                this.isEditMode = false;
                this.airlineId = null;
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
            },

            handleSubmit() {
                const url = this.isEditMode && this.airlineId ? 'airlines/' + this.airlineId : '/airlines';
                const method = this.isEditMode && this.airlineId ? 'put' : 'post';

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
                this.airlineId = null;
                this.errors = {};
                const message = this.isEditMode ? 'Airline has been updated' : 'Airline created successfully';
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
                    name: '',
                    iata_code: '',
                };
            },

            show(airline) {
                this.errors = {};
                this.isEditMode = true;
                this.showModal = true;
                this.airlineId = airline.id;
                this.form.name = airline.name;
                this.form.iata_code = airline.iata_code;
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
                        label: 'Airlines',
                        route: 'airlines'
                    }
			    ]);
		    },

            deleted() {
                this.deleteAirline = null;
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