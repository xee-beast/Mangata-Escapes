<template>
	<card title="Brands">
		<template v-if="can.create" v-slot:action>
			<button @click="openCreateModel" type="button" title="Add Brand" aria-pressed="false" class="button is-outlined is-primary is-inverted">Add Brand</button>
		</template>
		<data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search Brands" />
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
					<p class="modal-card-title">{{ isEditMode ? 'Edit Brand' : 'Add New Brand' }}</p>
					<button class="delete" aria-label="close" @click="closeModal"></button>
				</header>
				<section class="modal-card-body">
					<form>
						<div class="">
							<div class="field column m-0">
								<label class="label">Name <span style="color: red;">*</span></label>
								<div class="control">
									<input class="input" v-model="form.name" type="text" required />
								</div>
								<p class="help is-danger" v-if="errors.name">{{ errors.name[0] }}</p>
							</div>
							<div class="field column m-2">
								<label class="label">Concessions (Write in Numbered List) <span style="color: red;">*</span></label>
								<div class="control">
									<textarea class="textarea" v-model="form.concessions" rows="8" required></textarea>
								</div>
								<p class="help is-danger" v-if="errors.concessions">{{ errors.concessions[0] }}</p>
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
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Name', 'Concessions', 'Actions']">
			<template v-if="brands.length">
				<tr v-for="brand in brands" :key="brand.id">
					<th>{{brand.name}}</th>
					<td style="white-space: pre-line;">{{ brand.concessions }}</td>
					<td>
						<a v-if="brand.can.view || brand.can.update" class="table-action" @click.prevent="show(brand)" title="Show/Edit Brand">
							<i class="fas fa-edit"></i>
						</a>
						<a v-if="brand.can.delete" class="table-action" @click.prevent="deleteBrand = brand" title="Delete Brand">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
			<tr v-else>
				<td>No brands found...</td>
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
		<delete-brand v-if="deleteBrand" :brand="deleteBrand" @deleted="deleted" @canceled="deleteBrand = null" />
	</card>
</template>
<script>
	import Card from '@dashboard/components/Card';
	import DataTable from '@dashboard/components/table/Table';
	import DeleteBrand from '@dashboard/pages/Brands/Delete';
	import PaginationFilter from '@dashboard/components/pagination/Filter';
	import Paginator from '@dashboard/components/pagination/Paginator';
	import DataFilter from '@dashboard/components/table/Filter';
	import DataFilters from '@dashboard/components/table/Filters';
	import FormField from '@dashboard/components/form/Field';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlButton from '@dashboard/components/form/controls/Button';

	export default {
		name: 'BrandsComponent',

		components: {
			Card,
			DataTable,
			DeleteBrand,
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
				brands: [],
				meta: {},
				filters: {
					paginate: 10,
					page: 1,
					search: '',
				},
				form: {
					name: '',
					concessions: '',
				},
				deleteBrand: null,
				brandId: null,
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
				this.$http.get('/brands' + this.query)
					.then(response => {
						this.brands = response.data.data;
						this.meta = response.data.meta;
						this.can = response.data.can;

						this.setBreadcrumbs();
					});
				},

				openCreateModel() {
					this.resetForm();

					this.errors = {};
					this.isEditMode = false;
					this.brandId = null;
					this.showModal = true;
				},

				closeModal() {
					this.showModal = false;
				},

				handleSubmit() {
					const url = this.isEditMode && this.brandId ? 'brands/' + this.brandId : '/brands';
					const method = this.isEditMode && this.brandId ? 'put' : 'post';

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
						});
				},

				onSuccess() {
					this.fetchData();
					this.resetForm();

					this.showModal = false;
					this.brandId = null;
					this.errors = {};
					const message = this.isEditMode ? 'Brand updated successfully' : 'Brand created successfully';
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

					this.isLoading = false;
				},

				resetForm() {
					this.form = {
						name: '',
						concessions: '',
					};
				},

				show(brand) {
					this.errors = {};
					this.isEditMode = true;
					this.showModal = true;
					this.brandId = brand.id;
					this.form.name = brand.name;
					this.form.concessions = brand.concessions;
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
							label: 'Brands',
							route: 'brands'
						}
					]);
				},

				deleted() {
					this.deleteBrand = null;
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