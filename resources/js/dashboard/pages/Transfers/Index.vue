<template>
    <card title="Transfer Providers">
        <template v-if="can.create" v-slot:action>
                <button @click="openCreateModel" type="button" title="Add Transfer Provider" aria-pressed="false" class="button is-outlined is-primary is-inverted">Add Transfer Provider</button>
        </template>
        <data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search Transfer Providers" />
					<template v-slot:addon>
						<control-button @click="filterData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
					</template>
				</form-field>
			</data-filter>
		</data-filters>
        <template v-if="showModal">
            <div class="modal is-active">
                <div class="modal-background" @click="closeModal"></div>
                <div class="modal-card">
                    <header class="modal-card-head">
                        <p class="modal-card-title">Add New Transfer Provider</p>
                        <button class="delete" aria-label="close" @click="closeModal"></button>
                    </header>
                    <section class="modal-card-body">
                        <form>
                            <div class="">
                                <div class="field column m-0">
                                    <label class="label is-required">Name</label>
                                    <div class="control">
                                        <input class="input" v-model="form.name" type="text" />
                                    </div>
                                    <p class="help is-danger" v-if="errors.name">{{ errors.name[0] }}</p>
                                </div>
                                <div class="field column m-0">
                                    <label class="label is-required">Email</label>
                                    <div class="control">
                                        <input class="input" v-model="form.email" type="text" />
                                    </div>
                                    <p class="help is-danger" v-if="errors.email">{{ errors.email[0] }}</p>
                                </div>
                                <div class="field column m-0">
                                    <label class="label is-required">Primary Phone Number</label>
                                    <div class="control">
                                        <input class="input" v-model="form.primaryPhoneNumber" type="text" />
                                    </div>
                                    <p class="help is-danger" v-if="errors.primaryPhoneNumber">{{ errors.primaryPhoneNumber[0] }}</p>
                                </div>
                                <div class="field column m-0">
                                    <label class="label">Secondary Phone Number Label</label>
                                    <div class="control">
                                        <input class="input" v-model="form.secondaryPhoneNumberLabel" type="text" />
                                    </div>
                                    <p class="help is-danger" v-if="errors.secondaryPhoneNumberLabel">{{ errors.secondaryPhoneNumberLabel[0] }}</p>
                                </div>
                                <div class="field column m-0">
                                    <label class="label">Secondary Phone Number Value</label>
                                    <div class="control">
                                        <input class="input" v-model="form.secondaryPhoneNumberValue" type="text" />
                                    </div>
                                    <p class="help is-danger" v-if="errors.secondaryPhoneNumberValue">{{ errors.secondaryPhoneNumberValue[0] }}</p>
                                </div>
                                <div class="field column m-0">
                                    <label class="label">Whatsapp Number</label>
                                    <div class="control">
                                        <input class="input" v-model="form.whatsappNumber" type="text" />
                                    </div>
                                    <p class="help is-danger" v-if="errors.whatsappNumber">{{ errors.whatsappNumber[0] }}</p>
                                </div>

                                <div class="column">
                                    <form-field label="Missed or Changed Flight" :errors="errors.missedOrChangedFlight" :required="true">
                                        <control-editor v-model="form.missedOrChangedFlight" />
                                    </form-field>
                                </div>

                                <div class="column">
                                    <form-field label="Arrival Procedure" :errors="errors.arrivalProcedure" :required="true">
                                        <control-editor v-model="form.arrivalProcedure" />
                                    </form-field>
                                </div>

                                <div class="column">
                                    <form-field label="Departure Procedure" :errors="errors.departureProcedure" :required="true">
                                        <control-editor v-model="form.departureProcedure" />
                                    </form-field>
                                </div>

                                <div class="column">
                                    <form-field label="Display Image" :errors="errors.displayImage">
                                        <image-uploader v-model="form.displayImage" @errors="$set(errors, 'displayImage', $event)" :max-size="2048" is-single />
                                    </form-field>
                                </div>

                                <div class="column">
                                    <form-field label="App Image" :errors="errors.appImage">
                                        <image-uploader v-model="form.appImage" @errors="$set(errors, 'appImage', $event)" :max-size="2048" is-single />
                                    </form-field>
                                </div>

                                <div class="field column m-0">
                                    <label class="label">App Link</label>
                                    <div class="control">
                                        <input class="input" v-model="form.appLink" type="text" />
                                    </div>
                                    <p class="help is-danger" v-if="errors.appLink">{{ errors.appLink[0] }}</p>
                                </div>
                            </div>
                        </form>
                    </section>
                    <footer class="modal-card-foot">
                        <button class="button is-primary" @click="handleSubmit" :class="{ 'is-loading': isLoading }" :disabled="isLoading">Save</button>
                        <button class="button" @click="closeModal" :disabled="isLoading">Cancel</button>
                    </footer>
                </div>
            </div>
        </template>
        <data-table class="is-size-6" table-class="is-fullwidth" :columns="['Name', 'Email', 'Primary Phone Number', 'Actions']">
			<template  v-if="transfers.length">
				<tr v-for="transfer in transfers" :key="transfer.id">
					<th>{{ transfer.name }}</th>
					<td>{{ transfer.email }}</td>
					<td>{{ transfer.primaryPhoneNumber }}</td>
                    <td>
						<a v-if="transfer.can.view || transfer.can.update" class="table-action" @click.prevent="show(transfer.id)" title="Show/Edit Transfer Provider">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="transfer.can.delete" class="table-action" @click.prevent="deleteTransfer=transfer" title="Delete Transfer Provider">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
            <tr v-else>
                <td>No transfer provider found...</td>
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
        <delete-transfer v-if="deleteTransfer" :transfer="deleteTransfer" @deleted="deleted" @canceled="deleteTransfer = null" />
    </card>
</template>
<script>
    import Card from '@dashboard/components/Card';
    import DataTable from '@dashboard/components/table/Table';
    import DeleteTransfer from '@dashboard/pages/Transfers/Delete';
    import PaginationFilter from '@dashboard/components/pagination/Filter';
    import Paginator from '@dashboard/components/pagination/Paginator';
    import DataFilter from '@dashboard/components/table/Filter';
    import DataFilters from '@dashboard/components/table/Filters';
    import FormField from '@dashboard/components/form/Field';
    import ControlInput from '@dashboard/components/form/controls/Input';
    import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlEditor from '@dashboard/components/form/controls/QuillEditor';
    import ImageUploader from '@dashboard/components/file/ImageUploader';

    export default {
        name: 'TransfersComponent',

        components: {
            Card,
            DataTable,
            DeleteTransfer,
            PaginationFilter,
            Paginator,
            DataFilter,
            DataFilters,
            FormField,
            ControlInput,
            ControlButton,
            ControlEditor,
            ImageUploader,
        },

        data() {
            return {
                showModal: false,
                errors: {},
                can: {},
                transfers: [],
                meta: {},
                filters: {
                    paginate: 10,
                    page: 1,
                    search: '',
                },
                form: {
                    name: '',
                    email: '',
                    primaryPhoneNumber: '',
                    secondaryPhoneNumberLabel: '',
                    secondaryPhoneNumberValue: '',
                    whatsappNumber: '',
                    missedOrChangedFlight: '',
                    arrivalProcedure: '',
                    departureProcedure: '',
                    displayImage: null,
                    appImage: null,
                    appLink: '',
                },
                deleteTransfer: null,
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
			      this.$http.get('/transfers' + this.query)
				    .then(response => {
                        this.transfers = response.data.data;
                        this.meta = response.data.meta;
                        this.can = response.data.can;
                        this.setBreadcrumbs();
				    });
		    },

            openCreateModel() {
                this.resetForm();
                this.errors = {};
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
            },

            handleSubmit() {
                const url = '/transfers';
                const method = 'post';

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
                this.isLoading = false;
                this.errors = {};
                const message = 'Transfer Provider created successfully';

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
                    email: '',
                    primaryPhoneNumber: '',
                    secondaryPhoneNumberLabel: '',
                    secondaryPhoneNumberValue: '',
                    whatsappNumber: '',
                    missedOrChangedFlight: '',
                    arrivalProcedure: '',
                    departureProcedure: '',
                    displayImage: null,
                    appImage: null,
                    appLink: '',
                };
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
                        label: 'Transfer Providers',
                        route: 'transfers'
                    }
			    ]);
		    },

            show(id) {
			    this.$router.push({
                    name: 'transfers.show',
                    params: {
                        id: id
                    }
                });
            },

            deleted() {
                this.deleteTransfer = null;
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
