<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">New Payment</a>
	<modal v-if="render" @hide="show = false" title="New Payment" :is-active="show">
		<form-field label="Client" :errors="paymentErrors.client" :required="true">
			<control-select v-model="payment.client" @input="setCardOnFile"
				:options="booking.clients.map(client => ({value: client.id, text: client.firstName + ' ' + client.lastName}))"
				:class="{ 'is-danger': (paymentErrors.client || []).length }" />
		</form-field>
		<form-field label="Amount" :errors="paymentErrors.amount" :required="true">
			<control-input v-model="payment.amount" :class="{ 'is-danger': (paymentErrors.amount || []).length }" />
		</form-field>
		<form-field label="Notes" :errors="paymentErrors.notes">
			<control-textarea v-model="payment.notes" :class="{ 'is-danger': (paymentErrors.notes || []).length }" />
		</form-field>

		<template v-if="selectedClient">
			<hr>

			<form-panel label="Billing Information" class="is-borderless">
				<form-field v-if="selectedClient && selectedClient.client && selectedClient.client.cards" :errors="[...(paymentErrors['useExistingCard'] || [])]">
					<label class="checkbox">
						<input v-model="payment.useExistingCard" type="checkbox">
						Use Existing Card
					</label>
				</form-field>
				<form-field v-if="selectedClient && selectedClient.client && selectedClient.client.cards && payment.useExistingCard" :errors="[...(paymentErrors['existingCard'] || [])]">
					<div class="field" v-for="(card, index) in selectedClient.client.cards" :key="card.id">
						<label class="radio">
							<input v-model="payment.existingCard" type="radio" :value="card.id">
							Use <span class="is-capitalized">{{ card.type }}</span> ending in {{ card.lastDigits }}. {{ (selectedClient.card && selectedClient.card.id == card.id) ? '(Default)' : '' }}
						</label>
					</div>
				</form-field>

				<template v-if="!payment.useExistingCard">
					<form-panel label="Credit Card Information">
						<form-field label="Cardholder Name" :errors="paymentErrors['card.name']" :required="true">
							<control-input v-model="payment.card.name" class="is-capitalized"
								:class="{ 'is-danger': (paymentErrors['card.name'] || []).length }" />
						</form-field>
						<form-field label="Credit Card Number"
							:errors="[...(paymentErrors['card.number'] || []), ...(paymentErrors['card.type'] || [])]" :required="true">
							<control-input v-model="payment.card.number" @input="setCardType"
								:class="{ 'is-danger': (paymentErrors['card.number'] || []).length }" />
							<template v-slot:addon>
								<control-button class="is-static" :class="{ 'is-danger is-outlined': (paymentErrors['card.type'] || []).length }">
									<i class="is-size-4" :class="creditCards[payment.card.type || 'default']"></i>
								</control-button>
							</template>
						</form-field>
						<div class="columns">
							<div class="column is-4">
								<form-field label="Expiration Date"
									:errors="[...(paymentErrors['card.expMonth'] || []), ...(paymentErrors['card.expYear'] || [])]" :required="true">
									<control-select v-model="payment.card.expMonth" :options="expMonths" first-is-empty
										:class="{ 'is-danger': (paymentErrors['card.expMonth'] || []).length }" />
									<template v-slot:addon>
										<control-select v-model="payment.card.expYear" :options="expYears" first-is-empty
											:class="{ 'is-danger': (paymentErrors['card.expYear'] || []).length }" />
									</template>
								</form-field>
							</div>
							<div class="column is-4">
								<form-field label="CVV Code" :errors="paymentErrors['card.code']" :required="true">
									<control-input v-model="payment.card.code" :class="{ 'is-danger': (paymentErrors['card.code'] || []).length }" />
								</form-field>
							</div>
						</div>
					</form-panel>
					<form-panel label="Billing Address">
						<form-field label="Country" :errors="paymentErrors['address.country']" :required="true">
							<control-select v-model="payment.address.country"
								:options="[{value: 0, text: 'Other...'}, ...countries.map(country => ({value: country.id, text: country.name}))]"
								:class="{ 'is-danger': (paymentErrors['address.country'] || []).length }"
								:disabled="payment.address.hasOtherCountry" />
						</form-field>
						<form-field v-if="!payment.address.country" :errors="paymentErrors['address.otherCountry']">
							<control-input v-model="payment.address.otherCountry"
								:class="{ 'is-danger': (paymentErrors['address.otherCountry'] || []).length }" />
						</form-field>
						<div class="columns">
							<div class="column">
								<form-field v-if="payment.address.country" label="State/Province" :errors="paymentErrors['address.state']" :required="true">
									<control-select v-model="payment.address.state"
										:options="selectedCountry ? selectedCountry.states.map(state => ({value: state.id, text: state.name})) : []"
										first-is-empty :class="{ 'is-danger': (paymentErrors['address.state'] || []).length }" />
								</form-field>
								<form-field v-else label="State/Province" :errors="paymentErrors['address.otherState']" :required="true">
									<control-input v-model="payment.address.otherState"
										:class="{ 'is-danger': (paymentErrors['address.otherState'] || []).length }" />
								</form-field>

							</div>
							<div class="column">
								<form-field label="City" :errors="paymentErrors['address.city']" :required="true">
									<control-input v-model="payment.address.city"
										:class="{ 'is-danger': (paymentErrors['address.city'] || []).length }" />
								</form-field>
							</div>
						</div>
						<form-field label="Address Line 1" :errors="paymentErrors['address.line1']" :required="true">
							<control-input v-model="payment.address.line1" :class="{ 'is-danger': (paymentErrors['address.line1'] || []).length }" />
						</form-field>
						<div class="columns">
							<div class="column is-8">
								<form-field label="Address Line 2" :errors="paymentErrors['address.line2']">
									<control-input v-model="payment.address.line2"
										:class="{ 'is-danger': (paymentErrors['address.line2'] || []).length }" />
								</form-field>
							</div>
							<div class="column is-4">
								<form-field label="Zip/Postal Code" :errors="paymentErrors['address.zipCode']" :required="true">
									<control-input v-model="payment.address.zipCode"
										:class="{ 'is-danger': (paymentErrors['address.zipCode'] || []).length }" />
								</form-field>
							</div>
						</div>
					</form-panel>
				</template>
			</form-panel>
		</template>

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
import ControlSelect from '@dashboard/components/form/controls/Select';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';
import FormField from '@dashboard/components/form/Field';
import FormPanel from '@dashboard/components/form/Panel';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlInput,
		ControlSelect,
		ControlTextarea,
		FormField,
		FormPanel,
		Modal,
	},
	props: {
		booking: {
			type: Object,
			required: true
		},
		countries: {
			type: Array,
			required: true
		},
		buttonClass: String
	},
	data() {
		return {
			render: false,
			show: false,
			payment: {
				useExistingCard: true,
				existingCard: null,
				card: {},
				address: {
					country: 1
				}
			},
			paymentErrors: {},
			isLoading: false,
			creditCards: {
				default: 'fas fa-credit-card',
				visa: 'fab fa-cc-visa',
				mastercard: 'fab fa-cc-mastercard',
				amex: 'fab fa-cc-amex',
				discover: 'fab fa-cc-discover'
			}
		}
	},
	computed: {
		selectedClient() {
			return this.booking.clients.find(client => client.id === this.payment.client);
		},
		selectedCountry() {
			return this.countries.find(country => country.id === this.payment.address.country);
		},
		expMonths() {
			let months = [];
			for (var i = 1; i <= 12; i++) {
				months.push({
					value: i < 10 ? '0' + i : '' + i,
					text: i < 10 ? '0' + i : '' + i
				});
			}
			return months;
		},
		expYears() {
			let years = [];
			for (var i = 0; i < 10; i++) {
				const year = this.$moment().add(i, 'years').year().toString();
				years.push({
					value: year,
					text: year
				});
			}
			return years;
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post(`/groups/${ this.$route.params.group }/bookings/${ this.$route.params.booking }/payments`, this.payment)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The new payment has been created.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.paymentErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = false;
			});
		},
		close() {
			Object.assign(this.$data, this.$options.data.apply(this));
		},
		setCardOnFile() {
			if (this.selectedClient != null && this.selectedClient.card != null) {
				this.$set(this.payment, 'existingCard', this.selectedClient.card.id);
			} else {
				this.$set(this.payment, 'existingCard', null);
			}
		},
		setCardType() {
			const number = this.payment.card.number.replace(/\D/g, '');

			let type = 'default';
			if (number.substr(0, 1) == 4) {
				type = 'visa';
			} else if (
				(number.substr(0, 2) >= 51 && number.substr(0, 2) <= 55) ||
				(number.substr(0, 4) >= 2221 && number.substr(0, 4) <= 2720)
			) {
				type = 'mastercard';
			} else if (
				number.substr(0, 2) == 34 ||
				number.substr(0, 2) == 37
			) {
				type = 'amex';
			} else if (
				number.substr(0, 2) == 64 ||
				number.substr(0, 2) == 65 ||
				number.substr(0, 4) == 6011 ||
				(number.substr(0, 6) >= 622126 && number.substr(0, 6) <= 622925) ||
				(number.substr(0, 6) >= 624000 && number.substr(0, 6) <= 626999) ||
				(number.substr(0, 6) >= 628200 && number.substr(0, 6) <= 628899)
			) {
				type = 'discover';
			}
			this.payment.card.type = type;
		}
	}
}
</script>
