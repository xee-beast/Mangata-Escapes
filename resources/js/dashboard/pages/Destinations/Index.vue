<template>
<card title="Destinations">
	<template v-slot:action>
		<create-destination v-if="can.create" @created="fetchData()" :countries="countries" :airports="airports" button-class="is-outlined is-primary is-inverted" />
	</template>

	<template v-if="destinations">
		<data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<control-select v-model="filters.country" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Countries' }, ...countries ]" default-value="" />
			</data-filter>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search" />
					<template v-slot:addon>
						<control-button @click="filterData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
					</template>
				</form-field>
			</data-filter>
		</data-filters>
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Destination', 'Country', 'Default Airport', 'Airports', 'Actions']">
			<template v-if="destinations.length">
				<tr v-for="destination in destinations">
					<th>{{ destination.name }}</th>
					<td>{{ destination.country.name }}</td>
					<th>{{ destination.airports[0].airport_code }}</th>
					<td>{{ destination.airports && destination.airports.map(airport => airport.airport_code).join(', ') }}</td>
					<td>
						<a v-if="destination.can.view || destination.can.update" class="table-action" @click.prevent="show(destination.id)">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="destination.can.delete" class="table-action" @click.prevent="deleteDestination = destination">
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

		<delete-destination v-if="deleteDestination" :destination="deleteDestination" @deleted="deleted" @canceled="deleteDestination = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlSelect from '@dashboard/components/form/controls/Select';
import CreateDestination from '@dashboard/pages/Destinations/Create';
import DataFilter from '@dashboard/components/table/Filter';
import DataFilters from '@dashboard/components/table/Filters';
import DataTable from '@dashboard/components/table/Table';
import DeleteDestination from '@dashboard/pages/Destinations/Delete';
import FormField from '@dashboard/components/form/Field';
import PaginationFilter from '@dashboard/components/pagination/Filter';
import Paginator from '@dashboard/components/pagination/Paginator';
import { fn } from 'moment';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		ControlSelect,
		CreateDestination,
		DataFilter,
		DataFilters,
		DataTable,
		DeleteDestination,
		FormField,
		PaginationFilter,
		Paginator,
	},
	data() {
		return {
			destinations: [],
			countries: [],
			airports: [],
			meta: {},
			can: {},
			filters: {
				paginate: 10,
				page: 1
			},
			deleteDestination: null
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
			this.$http.get('/destinations' + this.query)
				.then(response => {
					this.destinations = response.data.data;
					this.countries = response.data.countries.map(country => ({
						value: country.id,
						text: country.name
					}));
					this.airports = response.data.airports.map(airport => ({
						value: airport.airport_code,
						text: airport.airport_code,
					}));
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
					label: 'Destinations',
					route: 'destinations'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'destinations.show',
				params: {
					id: id
				}
			});
		},
		deleted() {
			this.deleteDestination = null;
			this.fetchData();
		}
	}
}
</script>
