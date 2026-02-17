<template>
<card v-if="savedInsuranceRate" :title="savedInsuranceRate.name">
	<template v-slot:action v-if="savedInsuranceRate.can.delete">
		<a v-if="savedInsuranceRate.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash"></i></span>
		</a>
		<delete-insurance-rate v-if="showDelete" :insurance-rate="savedInsuranceRate" @deleted="deleted" @canceled="showDelete = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">Insurance Rates</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<form-field label="Name" :errors="insuranceRateErrors.name" :required="true">
			<control-input v-model="insuranceRate.name" :class="{ 'is-danger': (insuranceRateErrors.name || []).length }" :readonly="readonly" />
		</form-field>
		<form-field label="Is travel insurance available?" :errors="insuranceRateErrors.description">
			<control-textarea v-model="insuranceRate.description" :class="{ 'is-danger': (insuranceRateErrors.description || []).length }" :readonly="readonly" />
		</form-field>
		<form-field label="Start Date" :errors="insuranceRateErrors.startDate">
            <date-picker v-model="insuranceRate.startDate"
				:popover="{ visibility: (readonly ? 'hidden' : 'focus') }">
					<template v-slot="{ inputValue, inputEvents }">
						<input
                            :readonly="readonly"
							:class="'input' + ((insuranceRateErrors.startDate || []).length ? ' is-danger' : '')"
							:value="inputValue"
							v-on="inputEvents"
						/>
					</template>
			</date-picker>
		</form-field>
		<form-field label="Link" :errors="insuranceRateErrors.url">
			<control-input v-model="insuranceRate.url" :class="{ 'is-danger': (insuranceRateErrors.url || []).length }" :readonly="readonly" />
		</form-field>
		<form-field label="Calculated By" :errors="insuranceRateErrors.calculatedBy">
			<control-radio v-model="insuranceRate.calculateBy" :options="[{value: 'total', text: 'Booking Total'}, {value: 'nights', text: 'Nights'}]"
				:class="{ 'is-danger': (insuranceRateErrors.calculatedBy || []).length }" :readonly="readonly" />
		</form-field>
		<form-panel label="Rates" class="is-borderless">
			<div class="columns is-mobile is-variable is-1">
				<div class="column is-2"><label class="label">From</label></div>
				<div class="column is-3"><label class="label is-required">To</label></div>
				<div class="column"><label class="label is-required">Rate</label></div>
			</div>
			<div v-for="(rate, index) in insuranceRate.rates" class="columns is-mobile is-variable is-1">
				<div class="column is-2">
					<form-field>
						<control-input :value="index ? insuranceRate.rates[index - 1].to : 0" readonly />
					</form-field>
				</div>
				<div class="column is-3">
					<form-field :errors="insuranceRateErrors['rates.' + index + '.to']">
						<control-input v-model="rate.to" :class="{ 'is-danger': (insuranceRateErrors['rates.' + index + '.to'] || []).length }"
							:readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field :errors="insuranceRateErrors['rates.' + index + '.rate']">
						<control-input v-model="rate.rate" :class="{ 'is-danger': (insuranceRateErrors['rates.' + index + '.rate'] || []).length }"
							:readonly="readonly" />
					</form-field>
				</div>
				<div v-if="!readonly && index" class="column is-narrow">
					<control-button class="is-link is-outlined" @click="insuranceRate.rates.splice(index, 1)">
						<i class="fas fa-minus"></i>
					</control-button>
				</div>
			</div>
			<div style="min-height: 32px;">
				<a v-if="!readonly && insuranceRate.rates[insuranceRate.rates.length - 1].to" @click.prevent="insuranceRate.rates.push({})"
					class="has-text-mauve">
					+ Add another rate
				</a>
			</div>
		</form-panel>
		<control-button v-if="!readonly" @click="showUpdateAlert = true" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save
		</control-button>
		<modal @hide="showUpdateAlert = false" :title="`Update ${insuranceRate.name}`" :is-active="showUpdateAlert">
			<p>
				Are you sure you want to update these rates?
				<br>
				This will affect all bookings using these rates.
			</p>
			<template v-slot:footer>
				<div class="field is-grouped">
					<control-button @click="showUpdateAlert = false">Cancel</control-button>
					<control-button @click="update" class="is-primary">Yes</control-button>
				</div>
			</template>
		</modal>
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlRadio from '@dashboard/components/form/controls/Radio';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';
import DatePicker from 'v-calendar/lib/components/date-picker.umd';
import DeleteInsuranceRate from '@dashboard/pages/Providers/InsuranceRates/Delete';
import FormField from '@dashboard/components/form/Field';
import FormPanel from '@dashboard/components/form/Panel';
import Modal from '@dashboard/components/Modal';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		ControlRadio,
		ControlTextarea,
		DatePicker,
		DeleteInsuranceRate,
		FormField,
		FormPanel,
		Modal,
		Tab,
		Tabs,
	},
	data() {
		return {
			savedInsuranceRate: null,
			insuranceRate: {},
			insuranceRateErrors: {},
			provider: {},
			showUpdateAlert: false,
			showDelete: false,
			tabs: {
				info: true
			},
			isLoading: ''
		}
	},
	created() {
		this.fetchData();
	},
	computed: {
		readonly() {
			return !this.savedInsuranceRate.can.update;
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/providers/' + this.$route.params.provider + '/insurance-rates/' + this.$route.params.id)
				.then(response => {
					this.savedInsuranceRate = response.data.data;
					this.insuranceRate = {
						name: this.savedInsuranceRate.name,
						startDate: this.savedInsuranceRate.startDate ? this.$moment(this.savedInsuranceRate.startDate).toDate() : null,
						description: this.savedInsuranceRate.description,
						url: this.savedInsuranceRate.url,
						calculateBy: this.savedInsuranceRate.calculateBy,
						rates: this.savedInsuranceRate.rates
					};

					this.provider = response.data.provider;

					this.setBreadcrumbs();
				}).catch(error => {
					if (error.response.status === 403) {
						this.$store.commit('error', {
							status: 403,
							message: error.response.statusText
						});
					}
				});
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
				},
				{
					label: this.savedInsuranceRate.name,
					route: 'insuranceRates.show',
					params: {
						id: this.savedInsuranceRate.id
					}
				}
			]);
		},
		setTab(tab) {
			Object.keys(this.tabs).forEach(key => this.tabs[key] = false);
			this.tabs[tab] = true;
		},
		update() {
			this.showUpdateAlert = false;
			this.isLoading = 'update';

			this.insuranceRate.startDate = this.insuranceRate.startDate instanceof Date ? this.insuranceRate.startDate.toDateString() : null;

			let request = this.$http.put('/providers/' + this.$route.params.provider + '/insurance-rates/' + this.$route.params.id, this
					.insuranceRate)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The insurance rates have been updated.'
					});
					this.savedInsuranceRate = response.data.data;
					this.insuranceRateErrors = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.insuranceRateErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'insuranceRates'
			});
		}
	}
}
</script>
