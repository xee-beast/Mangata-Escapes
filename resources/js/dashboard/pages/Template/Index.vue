<template>
<card title="Models">
	<template v-slot:action>
		<create-model v-if="can.create" @created="fetchData()" button-class="is-outlined is-primary is-inverted" />
	</template>

	<template v-if="models">
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
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Model', 'Actions']">
			<template v-if="models.length">
				<tr v-for="model in models">
					<th>{{ model.name }}</th>
					<td>
						<a v-if="model.can.view || model.can.update" class="table-action" @click.prevent="show(model.id)">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="model.can.delete" class="table-action" @click.prevent="deleteModel = model">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
			<tr v-else>
				<td>No records found...</td>
			</tr>
		</data-table>
		<paginator v-if="meta.total > 10" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from"
			:to="meta.to" :total="meta.total" />

		<delete-model v-if="deleteModel" :model="deleteModel" @deleted="deleted" @canceled="deleteModel = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import CreateModel from '@dashboard/pages/Models/Create';
import DataFilter from '@dashboard/components/table/Filter';
import DataFilters from '@dashboard/components/table/Filters';
import DataTable from '@dashboard/components/table/Table';
import DeleteModel from '@dashboard/pages/Models/Delete';
import FormField from '@dashboard/components/form/Field';
import PaginationFilter from '@dashboard/components/pagination/Filter';
import Paginator from '@dashboard/components/pagination/Paginator';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		CreateModel,
		DataFilter,
		DataFilters,
		DataTable,
		DeleteModel,
		FormField,
		PaginationFilter,
		Paginator,
	},
	data() {
		return {
			models: [],
			meta: {},
			can: {},
			filters: {
				paginate: 10,
				page: 1
			},
			deleteModel: null
		}
	},
	created() {
		this.filters = Object.assign({}, this.filters, this.$route.query);

		this.fetchData();

		this.setBreadcrumbs();
	},
	computed: {
		query() {
			return '?' + Object.keys(this.filters).map(key => key + '=' + this.filters[key]).join('&');
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/models' + this.query)
				.then(response => {
					this.models = response.data.data;
					this.can = response.data.can;
					this.meta = response.data.meta;
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
					label: 'Models',
					route: 'models'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'models.show',
				params: {
					id: id
				}
			});
		},
		deleted() {
			this.deleteModel = null;
			this.fetchData();
		}
	}
}
</script>
