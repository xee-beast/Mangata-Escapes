<template>
	<div>
		<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Client</a>
		<modal v-if="render" @hide="show = false" title="New Client" :is-active="show">
			<form-panel label="Client Information" class="is-borderless">
				<div class="columns">
					<div class="column">
						<form-field label="First Name" :errors="clientErrors['firstName']" :required="true">
							<control-input v-model="client.firstName" class="is-capitalized" :class="{ 'is-danger': (clientErrors['firstName'] || []).length }" />
						</form-field>
					</div>
					<div class="column">
						<form-field label="Last Name" :errors="clientErrors['lastName']" :required="true">
							<control-input v-model="client.lastName" class="is-capitalized" :class="{ 'is-danger': (clientErrors['lastName'] || []).length }" />
						</form-field>
					</div>
				</div>
				<form-field label="Email" :errors="clientErrors['email']" :required="true">
					<control-input v-model="client.email" :class="{ 'is-danger': (clientErrors['email'] || []).length }" />
				</form-field>
				<form-field label="Phone Number" :errors="clientErrors['phone']">
					<control-input v-model="client.phone" :class="{ 'is-danger': (clientErrors['phone'] || []).length }" />
				</form-field>
				<form-field>
					<label class="checkbox">
						<input v-model="client.hasPaymentInfo" type="checkbox">
						Add Payment Information
					</label>
				</form-field>
			</form-panel>
			<template v-if="client.hasPaymentInfo">
				<hr>
				<form-panel label="Billing Information" class="is-borderless">
					<form-panel label="Credit Card Information">
						<form-field label="Cardholder Name" :errors="clientErrors['card.name']" :required="true">
							<control-input v-model="client.card.name" class="is-capitalized" :class="{ 'is-danger': (clientErrors['card.name'] || []).length }" />
						</form-field>
						<form-field label="Credit Card Number" :errors="[...(clientErrors['card.number'] || []), ...(clientErrors['card.type'] || [])]" :required="true">
							<control-input v-model="client.card.number" @input="setCardType" :class="{ 'is-danger': (clientErrors['card.number'] || []).length }" />
							<template v-slot:addon>
								<control-button class="is-static" :class="{ 'is-danger is-outlined': (clientErrors['card.type'] || []).length }">
									<i class="is-size-4" :class="creditCards[client.card.type || 'default']"></i>
								</control-button>
							</template>
						</form-field>
						<div class="columns">
							<div class="column is-4">
								<form-field label="Expiration Date" :errors="[...(clientErrors['card.expMonth'] || []), ...(clientErrors['card.expYear'] || [])]" :required="true">
									<control-select v-model="client.card.expMonth" :options="expMonths" first-is-empty :class="{ 'is-danger': (clientErrors['card.expMonth'] || []).length }" />
									<template v-slot:addon>
										<control-select v-model="client.card.expYear" :options="expYears" first-is-empty :class="{ 'is-danger': (clientErrors['card.expYear'] || []).length }" />
									</template>
								</form-field>
							</div>
							<div class="column is-4">
								<form-field label="CVV Code" :errors="clientErrors['card.code']" :required="true">
									<control-input v-model="client.card.code" :class="{ 'is-danger': (clientErrors['card.code'] || []).length }" />
								</form-field>
							</div>
						</div>
					</form-panel>
					<form-panel label="Billing Address">
						<form-field label="Country" :errors="clientErrors['address.country']" :required="true">
							<control-select
								v-model="client.address.country"
								:options="[...countries.map(country => ({value: country.id, text: country.name})), {value: 0, text: 'Other...'}]"
								:class="{ 'is-danger': (clientErrors['address.country'] || []).length }"
								:disabled="client.address.hasOtherCountry"
							/>
						</form-field>
						<form-field v-if="client.address.country == 0" label="Other Country" :errors="clientErrors['address.otherCountry']" :required="true">
							<control-input v-model="client.address.otherCountry" :class="{ 'is-danger': (clientErrors['address.otherCountry'] || []).length }" />
						</form-field>
						<div class="columns">
							<div class="column">
								<form-field v-if="client.address.country != 0" label="State/Province" :errors="clientErrors['address.state']" :required="true">
									<control-select
										v-model="client.address.state"
										:options="selectedCountry ? selectedCountry.states.map(state => ({value: state.id, text: state.name})) : []"
										first-is-empty
										:class="{ 'is-danger': (clientErrors['address.state'] || []).length }"
									/>
								</form-field>
								<form-field v-else label="State/Province" :errors="clientErrors['address.otherState']" :required="true">
									<control-input v-model="client.address.otherState" :class="{ 'is-danger': (clientErrors['address.otherState'] || []).length }" />
								</form-field>
							</div>
							<div class="column">
								<form-field label="City" :errors="clientErrors['address.city']" :required="true">
									<control-input v-model="client.address.city" :class="{ 'is-danger': (clientErrors['address.city'] || []).length }" />
								</form-field>
							</div>
						</div>
						<form-field label="Address Line 1" :errors="clientErrors['address.line1']" :required="true">
							<control-input v-model="client.address.line1" :class="{ 'is-danger': (clientErrors['address.line1'] || []).length }" />
						</form-field>
						<div class="columns">
							<div class="column is-8">
								<form-field label="Address Line 2" :errors="clientErrors['address.line2']">
									<control-input v-model="client.address.line2" :class="{ 'is-danger': (clientErrors['address.line2'] || []).length }" />
								</form-field>
							</div>
							<div class="column is-4">
								<form-field label="Zip/Postal Code" :errors="clientErrors['address.zipCode']" :required="true">
									<control-input v-model="client.address.zipCode" :class="{ 'is-danger': (clientErrors['address.zipCode'] || []).length }" />
								</form-field>
							</div>
						</div>
					</form-panel>
				</form-panel>
			</template>
			<template v-slot:footer>
				<div class="field is-grouped">
					<control-button @click="close" :disabled="isLoading">Cancel</control-button>
					<control-button @click="create" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }" :disabled="isLoading">Submit</control-button>
				</div>
			</template>
		</modal>
	</div>
</template>

<script>
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import FormField from '@dashboard/components/form/Field';
	import FormPanel from '@dashboard/components/form/Panel';
	import Modal from '@dashboard/components/Modal';

	export default {
		components: {
			ControlButton,
			ControlInput,
			ControlSelect,
			FormField,
			FormPanel,
			Modal,
		},
		props: {
			buttonClass: String,
			countries: {
				type: Array,
				required: true
			}
		},
		data() {
			return {
				render: false,
				show: false,
				client: {
					hasPaymentInfo: false,
					card: {},
					address: {}
				},
				clientErrors: {},
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
			selectedCountry() {
				return this.countries.find(country => country.id === this.client.address.country);
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
			},
		},
		methods: {
			create() {
				this.isLoading = true;

				let request = this.$http.post(`individual-bookings/${this.$route.params['id']}/clients`, this.client)
					.then(response => {
						this.close();
						this.$emit('created', response.data.data);

						this.$store.commit('notification', {
							type: 'success',
							message: 'A new client has been created.',
						});
					})
					.catch(error => {
						if (error.response.status == 422) {
							this.clientErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = false;
				});
			},
			close() {
				Object.assign(this.$data, this.$options.data.apply(this));
			},
			setCardType() {
				const number = this.client.card.number.replace(/\D/g, '');
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

				this.client.card.type = type;
			}
		}
	}
</script>
