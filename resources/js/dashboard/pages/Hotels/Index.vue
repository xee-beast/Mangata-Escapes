<template>
<card title="Hotels">
	<template v-slot:action>
		<create-hotel v-if="can.create" button-class="is-outlined is-primary is-inverted" :destinations="destinations" @created="fetchData()" />
	</template>

	<template v-if="hotels">
		<table-filters>
			<template v-slot:left>
				<table-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</table-filter>
			</template>
			<table-filter>
				<control-select v-model="filters.destination" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Destinations' }, ...destinations ]" default-value="" />
			</table-filter>
			<table-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search" />
					<template v-slot:addon>
						<control-button @click="filterData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
					</template>
				</form-field>
			</table-filter>
		</table-filters>
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Hotel', 'Destination', 'Actions']">
			<template v-if="hotels.length">
				<tr v-for="hotel in hotels">
					<th><span :class="{ 'deleted': hotel.deletedAt }">{{ hotel.name }}</span></th>
					<td><span :class="{ 'deleted': hotel.deletedAt }">{{ hotel.destination.name + ', ' + hotel.destination.country.name }}</span></td>
					<td>
						<a v-if="hotel.can.view || hotel.can.update" class="table-action" @click.prevent="show(hotel.id)">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="hotel.can.delete && !hotel.deletedAt" class="table-action" @click.prevent="deleteHotel = hotel">
							<i class="fas fa-trash"></i>
						</a>
						<a v-if="hotel.can.update && hotel.deletedAt" class="table-action" @click.prevent="enableHotel = hotel">
							<i class="fas fa-trash-restore"></i>
						</a>
						<table-actions v-if="hotel.can.viewRooms">
							<div class="dropdown-item" v-if="hotel.can.viewRooms">
								<router-link :to="{ name: 'rooms', params: {hotel: hotel.id} }" class="table-action">
									View Rooms
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

		<delete-hotel v-if="deleteHotel" :hotel="deleteHotel" @deleted="deleted" @canceled="deleteHotel = null" />
		<enable-hotel v-if="enableHotel" :hotel="enableHotel" @enabled="enabled" @canceled="enableHotel = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlSelect from '@dashboard/components/form/controls/Select';
import CreateHotel from '@dashboard/pages/Hotels/Create';
import DataTable from '@dashboard/components/table/Table';
import DeleteHotel from '@dashboard/pages/Hotels/Delete';
import FormField from '@dashboard/components/form/Field';
import PaginationFilter from '@dashboard/components/pagination/Filter';
import Paginator from '@dashboard/components/pagination/Paginator';
import TableActions from '@dashboard/components/table/Actions';
import TableFilter from '@dashboard/components/table/Filter';
import TableFilters from '@dashboard/components/table/Filters';
import EnableHotel from '@dashboard/pages/Hotels/Enable';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		ControlSelect,
		CreateHotel,
		DataTable,
		DeleteHotel,
		FormField,
		PaginationFilter,
		Paginator,
		TableActions,
		TableFilter,
		TableFilters,
		EnableHotel
	},
	data() {
		return {
			hotels: [],
			destinations: [],
			can: {},
			meta: {},
			filters: {
				paginate: 25,
				page: 1
			},
			deleteHotel: null,
			enableHotel: null
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
			this.$http.get('/hotels' + this.query)
				.then(response => {
					this.hotels = response.data.data;
					this.destinations = response.data.destinations;
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
					label: 'Hotels',
					route: 'hotels'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'hotels.show',
				params: {
					id: id
				}
			});
		},
		deleted() {
			this.deleteHotel = null;
			this.fetchData();
		},
		enabled() {
			this.enableHotel = null;
			this.fetchData();
		},
	}
}
</script>
