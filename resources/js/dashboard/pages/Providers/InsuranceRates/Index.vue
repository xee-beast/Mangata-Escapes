<template>
<card title="Insurance Rates">
	<template v-slot:action>
		<create-insurance-rate v-if="can.create" @created="fetchData()" button-class="is-outlined is-primary is-inverted" />
	</template>

	<template v-if="insuranceRates">
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Name', 'Start Date', 'Calculated By', 'Rates', 'Actions']">
			<template v-if="insuranceRates.length">
				<tr v-for="insuranceRate in insuranceRates">
					<th>{{ insuranceRate.name }}</th>
					<td>{{ insuranceRate.startDate ? $moment(insuranceRate.startDate).format('MM/DD/YYYY') : 'N/A' }}</td>
					<td>{{ insuranceRate.calculateBy == 'total' ? 'Booking Total' : 'Nights' }}</td>
					<td>{{ insuranceRate.rates.length }} Rates, Starting At ${{ insuranceRate.rates[0].rate }}</td>
					<td>
						<a v-if="insuranceRate.can.view || insuranceRate.can.update" class="table-action" @click.prevent="show(insuranceRate.id)">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="insuranceRate.can.delete" class="table-action" @click.prevent="deleteInsuranceRate = insuranceRate">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
			<tr v-else>
				<td>No records found...</td>
			</tr>
		</data-table>

		<delete-insurance-rate v-if="deleteInsuranceRate" :insuranceRate="deleteInsuranceRate" @deleted="deleted"
			@canceled="deleteInsuranceRate = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import CreateInsuranceRate from '@dashboard/pages/Providers/InsuranceRates/Create';
import DataTable from '@dashboard/components/table/Table';
import DeleteInsuranceRate from '@dashboard/pages/Providers/InsuranceRates/Delete';

export default {
	components: {
		Card,
		CreateInsuranceRate,
		DataTable,
		DeleteInsuranceRate
	},
	data() {
		return {
			insuranceRates: [],
			provider: {},
			can: {},
			deleteInsuranceRate: null
		}
	},
	created() {
		this.fetchData();
	},
	methods: {
		fetchData() {
			this.$http.get('/providers/' + this.$route.params.provider + '/insurance-rates')
				.then(response => {
					this.insuranceRates = response.data.data;
					this.provider = response.data.provider;
					this.can = response.data.can;

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
				},
				{
					label: this.provider.name,
					route: 'providers.show',
					params: {
						id: this.$route.params.provider
					}
				},
				{
					label: 'Insurance Rates',
					route: 'insuranceRates'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'insuranceRates.show',
				params: {
					id: id
				}
			});
		},
		deleted() {
			this.deleteInsuranceRate = null;
			this.fetchData();
		}
	}
}
</script>
