<template>
<card title="Suppliers">
	<template v-slot:action>
		<create-provider v-if="can.create" @created="fetchData()" button-class="is-outlined is-primary is-inverted" />
	</template>

	<template v-if="providers">
		<data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search" />
					<template v-slot:addon>
						<control-button @click="filterData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
					</template>
				</form-field>
			</data-filter>
		</data-filters>
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Supplier', 'A.K.A', 'Phone Number', 'Email', 'Actions']">
			<template v-if="providers.length">
				<tr v-for="provider in providers">
					<th>{{ provider.name }}</th>
					<td>{{ provider.abbreviation }}</td>
					<td>{{ provider.phoneNumber }}</td>
					<td>{{ provider.email }}</td>
					<td>
						<a v-if="provider.can.view || provider.can.update" class="table-action" @click.prevent="show(provider.id)">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="provider.can.delete" class="table-action" @click.prevent="deleteProvider = provider">
							<i class="fas fa-trash"></i>
						</a>
						<table-actions v-if="provider.can.viewInsuranceRates">
							<div class="dropdown-item" v-if="provider.can.viewInsuranceRates">
								<router-link :to="{ name: 'insuranceRates', params: {provider: provider.id} }" class="table-action">
									View Insurance Rates
								</router-link>
							</div>
						</table-actions>
					</td>
				</tr>
			</template>
			<tr v-else>
				<td>No records found...</td>
			</tr>
		</data-table>
		<paginator v-if="meta.total > 10" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from"
			:to="meta.to" :total="meta.total" />

		<delete-provider v-if="deleteProvider" :provider="deleteProvider" @deleted="deleted" @canceled="deleteProvider = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import CreateProvider from '@dashboard/pages/Providers/Create';
import DataFilter from '@dashboard/components/table/Filter';
import DataFilters from '@dashboard/components/table/Filters';
import DataTable from '@dashboard/components/table/Table';
import DeleteProvider from '@dashboard/pages/Providers/Delete';
import FormField from '@dashboard/components/form/Field';
import PaginationFilter from '@dashboard/components/pagination/Filter';
import Paginator from '@dashboard/components/pagination/Paginator';
import TableActions from '@dashboard/components/table/Actions';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		CreateProvider,
		DataFilter,
		DataFilters,
		DataTable,
		DeleteProvider,
		FormField,
		TableActions,
		PaginationFilter,
		Paginator,
	},
	data() {
		return {
			providers: [],
			meta: {},
			can: {},
			filters: {
				paginate: 10,
				page: 1
			},
			deleteProvider: null
		}
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
			this.$http.get('/providers' + this.query)
				.then(response => {
					this.providers = response.data.data;
					this.can = response.data.can;
					this.meta = response.data.meta;
      		this.setBreadcrumbs();
				});
		},
		filterData(page = '1') {
			this.filters.page = page;

			if (JSON.stringify(this.$route.query) !== JSON.stringify(this.filters)) {
				this.$router.replace({
					query: this.filters
				});

				this.fetchData();
			}
		},
		setBreadcrumbs() {
			this.$store.commit('breadcrumbs', [{
					label: 'Dashboard',
					route: 'home'
				},
				{
					label: 'Suppliers',
					route: 'providers'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'providers.show',
				params: {
					id: id
				}
			});
		},
		deleted() {
			this.deleteProvider = null;
			this.fetchData();
		}
	}
}
</script>
