<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">New Insurance Rates</a>
	<modal v-if="render" @hide="show = false" title="New Insurance Rates" :is-active="show">
		<form-field label="Name" :errors="insuranceRateErrors.name" :required="true">
			<control-input v-model="insuranceRate.name" :class="{ 'is-danger': (insuranceRateErrors.name || []).length }" />
		</form-field>
		<form-field label="Start Date" :errors="insuranceRateErrors.startDate">
            <date-picker v-model="startDate"
				:popover="{ visibility: 'focus' }">
					<template v-slot="{ inputValue, inputEvents }">
						<input
							:class="'input' + ((insuranceRateErrors.startDate || []).length ? ' is-danger' : '')"
							:value="inputValue"
							v-on="inputEvents"
						/>
					</template>
			</date-picker>
		</form-field>
		<form-field label="Add insurance before [n] days to include CFAR" :errors="insuranceRateErrors.cfar" :required="true">
			<control-input v-model="insuranceRate.cfar" :class="{ 'is-danger': (insuranceRateErrors.cfar || []).length }" />
		</form-field>
		<form-field label="Link" :errors="insuranceRateErrors.url">
			<control-input v-model="insuranceRate.url" :class="{ 'is-danger': (insuranceRateErrors.url || []).length }" />
		</form-field>
		<form-field label="Calculated By" :errors="insuranceRateErrors.calculatedBy">
			<control-radio v-model="insuranceRate.calculateBy" :options="[{value: 'total', text: 'Booking Total'}, {value: 'nights', text: 'Nights'}]"
				default="total" :class="{ 'is-danger': (insuranceRateErrors.calculatedBy || []).length }" />
		</form-field>
		<form-panel label="Rates" class="is-borderless">
			<div class="columns is-mobile">
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
						<control-input v-model="rate.to" :class="{ 'is-danger': (insuranceRateErrors['rates.' + index + '.to'] || []).length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field :errors="insuranceRateErrors['rates.' + index + '.rate']">
						<control-input v-model="rate.rate" :class="{ 'is-danger': (insuranceRateErrors['rates.' + index + '.rate'] || []).length }" />
					</form-field>
				</div>
				<div v-if="index" class="column is-narrow">
					<control-button class="is-link is-outlined" @click="insuranceRate.rates.splice(index, 1)">
						<i class="fas fa-minus"></i>
					</control-button>
				</div>
			</div>
			<div style="min-height: 32px;">
				<a v-if="insuranceRate.rates[insuranceRate.rates.length - 1].to" @click.prevent="insuranceRate.rates.push({})" class="has-text-mauve">
					+ Add another rate
				</a>
			</div>
		</form-panel>
		<template v-slot:footer>
			<div class="field is-grouped">
				<control-button @click="close" :disabled="isLoading">Cancel</control-button>
				<control-button @click="create" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }" :disabled="isLoading">
					Submit
				</control-button>
			</div>
		</template>
	</modal>
</div>
</template>

<script>
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlRadio from '@dashboard/components/form/controls/Radio';
import DatePicker from 'v-calendar/lib/components/date-picker.umd';
import FormField from '@dashboard/components/form/Field';
import FormPanel from '@dashboard/components/form/Panel';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlInput,
		ControlRadio,
		DatePicker,
		FormField,
		FormPanel,
		Modal,
	},
	props: {
		buttonClass: String
	},
	data() {
		return {
			render: false,
			show: false,
			insuranceRate: {
				cfar: 14,
				rates: [{}]
			},
			insuranceRateErrors: {},
			isLoading: false
		}
	},
	computed: {
		startDate: {
			get() {
				return this.insuranceRate.startDate;
			},
			set(date) {
				this.insuranceRate.startDate = date instanceof Date ? date.toDateString() : null;
			}
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/providers/' + this.$route.params.provider + '/insurance-rates', this.insuranceRate)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The new insurance rates have been created.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.insuranceRateErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = false;
			});
		},
		close() {
			Object.assign(this.$data, this.$options.data.apply(this));
		}
	}
}
</script>
