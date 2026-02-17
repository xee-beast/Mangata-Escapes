<template>
    <card title="Faqs">
        <template v-if="can.create" v-slot:action>
                <button @click="openCreateModel" type="button" title="Add Faq" aria-pressed="false" class="button is-outlined is-primary is-inverted">Add Faq</button>
        </template>
        <data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search Faqs" />
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
                    <p class="modal-card-title">{{ isEditMode ? 'Edit Faq' : 'Add New Faq' }}</p>
                    <button class="delete" aria-label="close" @click="closeModal"></button>
                </header>
                <section class="modal-card-body">
                    <form>
                        <div class="">
                            <div class="field column m-0">
                                <label class="label is-required">Title</label>
                                <div class="control">
                                    <input class="input" v-model="form.title" type="text" required />
                                </div>
                                <p class="help is-danger" v-if="errors.title">{{ errors.title[0] }}</p>
                            </div>
                            <div class="field column m-2">
                                <label class="label is-required">Description</label>
                                <div class="control">
                                    <control-editor v-model="form.description" required />                                    
                                </div>
                                <p class="help is-danger" v-if="errors.description">{{ errors.description[0] }}</p>
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
        <data-table class="is-size-6" table-class="is-fullwidth" :columns="['Title', 'Description', 'Actions']">
			<template  v-if="faqs.length">
				<tr v-for="faq in faqs" :key="faq.id">
					<th>{{faq.title}}</th>
					<td v-html="faq.description"></td>
                    <td>
						<a v-if="(faq.can.view || faq.can.update) && faq.type != 'dynamic'" class="table-action" @click.prevent="show(faq)" title="Show/Edit Faq">
							<i class="fas fa-edit"></i>
						</a>
						<a v-if="faq.can.delete && faq.type != 'dynamic'" class="table-action" @click.prevent="deleteFaq = faq" title="Delete Faq">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
            <tr v-else>
                <td>No faqs found...</td>
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
        <delete-faq v-if="deleteFaq" :faq="deleteFaq" @deleted="deleted" @canceled="deleteFaq = null" />
    </card>
</template>
<script>
    import Card from '@dashboard/components/Card';
    import DataTable from '@dashboard/components/table/Table';
    import DeleteFaq from '@dashboard/pages/Faqs/Delete';
    import PaginationFilter from '@dashboard/components/pagination/Filter';
    import Paginator from '@dashboard/components/pagination/Paginator';
    import DataFilter from '@dashboard/components/table/Filter';
    import DataFilters from '@dashboard/components/table/Filters';
    import FormField from '@dashboard/components/form/Field';
    import ControlInput from '@dashboard/components/form/controls/Input';
    import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlEditor from '@dashboard/components/form/controls/QuillEditor';

    export default {
        name: 'FaqsComponent',

        components: {
            Card,
            DataTable,
            DeleteFaq,
            PaginationFilter,
            Paginator,
            DataFilter,
            DataFilters,
            FormField,
            ControlInput,
            ControlButton,
            ControlEditor,
        },

        data() {
            return {
                showModal: false,
                isEditMode: false,
                errors: {},
                can: {},
                faqs: [],
                meta: {},
                filters: {
                    paginate: 10,
                    page: 1,
                    search: '',
                },
                form: {
                    title: '',
                    description: '',
                    type: 'static',
                },
                deleteFaq: null,
                faqId: null,
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
			    this.$http.get('/faqs' + this.query)
				    .then(response => {
                        this.faqs = response.data.data;
                        this.meta = response.data.meta;
                        this.can = response.data.can;
                        this.setBreadcrumbs();
				    });
		    },

            openCreateModel() {
                this.resetForm();
                this.errors = {};
                this.isEditMode = false;
                this.faqId = null;
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
            },

            handleSubmit() {
                const url = this.isEditMode && this.faqId ? 'faqs/' + this.faqId : '/faqs';
                const method = this.isEditMode && this.faqId ? 'put' : 'post';

                this.submitForm(url, method);
            },

            submitForm(url, method) {
                this.isLoading = true;
                this.$http[method](url, this.form)
                    .then(response => {
                        this.onSuccess();
                    })
                    .catch(error => {
                        this.isLoading = false;
                        this.onError(error);
                    });
            },

            onSuccess() {
                this.fetchData();
                this.resetForm();
                this.showModal = false;
                this.faqId = null;
                this.errors = {};
                const message = this.isEditMode ? 'Faq has been updated' : 'Faq created successfully';
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
                    title: '',
                    description: '',
                    type: 'static',
                };
            },

            show(faq) {
                this.errors = {};
                this.isEditMode = true;
                this.showModal = true;
                this.faqId = faq.id;
                this.form.title = faq.title;
                this.form.description = faq.description;
                this.form.type = faq.type;
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
                        label: 'Faqs',
                        route: 'faqs'
                    }
			    ]);
		    },

            deleted() {
                this.deleteFaq = null;
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