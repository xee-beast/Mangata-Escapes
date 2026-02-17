<template>
	<card v-if="savedClient" :title="`${savedClient.firstName} ${this.savedClient.lastName}`" :booking-status="booking.deletedAt ? 'cancelled' : 'active'">
		<template v-slot:action v-if="savedClient.can.delete || savedClient.can.viewPayments">
			<router-link v-if="savedClient.can.viewPayments" :to="{ name: 'individual-bookings.payments', params: { id: booking.id }}" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-hand-holding-usd"></i></span>
				<span>Payments</span>
				<span v-if="savedClient.pendingPayments" class="notification-counter">
					{{ savedClient.pendingPayments }}
				</span>
			</router-link>
			<a v-if="savedClient.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-trash"></i></span>
			</a>
			<delete-client v-if="showDelete" :client="savedClient" @deleted="deleted" @canceled="showDelete = false" />
		</template>
		<template v-slot:tabs>
			<tabs class="is-boxed">
				<tab @click="setTab('info')" :is-active="tabs.info">Client</tab>
				<tab v-if="!readonly" @click="setTab('card')" :is-active="tabs.card">Card On File</tab>
				<tab @click="setTab('extras')" :is-active="tabs.extras">Rates</tab>
			</tabs>
		</template>
		<template v-if="tabs.info">
			<div class="columns">
				<div class="column">
					<form-field label="First Name" :errors="clientErrors['firstName']" :required="true">
						<control-input v-model="client.firstName" class="is-capitalized" :class="{ 'is-danger': (clientErrors['firstName'] || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Last Name" :errors="clientErrors['lastName']" :required="true">
						<control-input v-model="client.lastName" class="is-capitalized" :class="{ 'is-danger': (clientErrors['lastName'] || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Email" :errors="clientErrors['email']" :required="true">
						<control-input v-model="client.email" :class="{ 'is-danger': (clientErrors['email'] || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Phone Number" :errors="clientErrors['phone']">
						<control-input v-model="client.phone" :class="{ 'is-danger': (clientErrors['phone'] || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
		</template>
		<template v-if="tabs.card">
			<form-panel label="Credit Card Information">
				<form-field label="Cardholder Name" :errors="cardErrors['card.name']" :required="true">
					<control-input v-model="card.card.name" class="is-capitalized" :class="{ 'is-danger': (cardErrors['card.name'] || []).length }" />
				</form-field>
				<form-field label="Credit Card Number" :errors="[...(cardErrors['card.number'] || []), ...(cardErrors['card.type'] || [])]" :required="true">
					<control-input v-model="card.card.number" @input="setCardType" :class="{ 'is-danger': (cardErrors['card.number'] || []).length }" />
					<template v-slot:addon>
						<control-button class="is-static" :class="{ 'is-danger is-outlined': (cardErrors['card.type'] || []).length }">
							<i class="is-size-4" :class="creditCards[card.card.type || 'default']"></i>
						</control-button>
					</template>
				</form-field>
				<div class="columns">
					<div class="column is-4">
						<form-field label="Expiration Date" :errors="[...(cardErrors['card.expMonth'] || []), ...(cardErrors['card.expYear'] || [])]" :required="true">
							<control-select v-model="card.card.expMonth" :options="expMonths" first-is-empty :class="{ 'is-danger': (cardErrors['card.expMonth'] || []).length }" />
							<template v-slot:addon>
								<control-select v-model="card.card.expYear" :options="expYears" first-is-empty :class="{ 'is-danger': (cardErrors['card.expYear'] || []).length }" />
							</template>
						</form-field>
					</div>
					<div class="column is-4">
						<form-field label="CVV Code" :errors="cardErrors['card.code']" :required="true" >
							<control-input v-model="card.card.code" :class="{ 'is-danger': (cardErrors['card.code'] || []).length }" />
						</form-field>
					</div>
				</div>
			</form-panel>
			<form-panel label="Billing Address">
				<form-field label="Country" :errors="cardErrors['address.country']" :required="true">
					<control-select
						v-model="card.address.country"
						:options="[...countries.map(country => ({value: country.id, text: country.name})), {value: 0, text: 'Other...'}]"
						:class="{ 'is-danger': (cardErrors['address.country'] || []).length }"
						:disabled="card.address.hasOtherCountry"
					/>
				</form-field>
				<form-field v-if="card.address.country == 0" :errors="cardErrors['address.otherCountry']">
					<control-input v-model="card.address.otherCountry" :class="{ 'is-danger': (cardErrors['address.otherCountry'] || []).length }" />
				</form-field>
				<div class="columns">
					<div class="column">
						<form-field v-if="card.address.country != 0" label="State/Province" :errors="cardErrors['address.state']" :required="true">
							<control-select
								v-model="card.address.state"
								:options="selectedCountry ? selectedCountry.states.map(state => ({value: state.id, text: state.name})) : []"
								first-is-empty
								:class="{ 'is-danger': (cardErrors['address.state'] || []).length }"
							/>
						</form-field>
						<form-field v-else label="State/Province" :errors="cardErrors['address.otherState']" :required="true">
							<control-input v-model="card.address.otherState" :class="{ 'is-danger': (cardErrors['address.otherState'] || []).length }" />
						</form-field>
					</div>
					<div class="column">
						<form-field label="City" :errors="cardErrors['address.city']" :required="true">
							<control-input v-model="card.address.city" :class="{ 'is-danger': (cardErrors['address.city'] || []).length }" />
						</form-field>
					</div>
				</div>
				<form-field label="Address Line 1" :errors="cardErrors['address.line1']" :required="true">
					<control-input v-model="card.address.line1" :class="{ 'is-danger': (cardErrors['address.line1'] || []).length }" />
				</form-field>
				<div class="columns">
					<div class="column is-8">
						<form-field label="Address Line 2" :errors="cardErrors['address.line2']">
							<control-input v-model="card.address.line2" :class="{ 'is-danger': (cardErrors['address.line2'] || []).length }" />
						</form-field>
					</div>
					<div class="column is-4">
						<form-field label="Zip/Postal Code" :errors="cardErrors['address.zipCode']" :required="true">
							<control-input v-model="card.address.zipCode" :class="{ 'is-danger': (cardErrors['address.zipCode'] || []).length }" />
						</form-field>
					</div>
				</div>
			</form-panel>
			<control-button v-if="!readonly" @click="updateCard" class="is-primary" :class="{ 'is-loading': isLoading === 'updateCard' }">Update Card On File</control-button>
		</template>
		<template v-if="tabs.extras">
			<div class="columns">
				<div class="column">
					<form-field label="Accommodation & Travel Charges" :errors="extrasErrors['fitRate.accommodation']" :required="true">
						<control-input v-model="fitRate.accommodation" :class="{ 'is-danger': (extrasErrors['fitRate.accommodation'] || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Insurance Charges" :errors="extrasErrors['fitRate.insurance']" :required="true">
						<control-input v-model="fitRate.insurance" :class="{ 'is-danger': (extrasErrors['fitRate.insurance'] || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<h2><b>Extras</b></h2>
			<form-panel v-for="(extra, index) in extras" :key="index">
				<template v-slot:action v-if="!readonly">
					<control-button class="is-small is-link is-outlined" @click="extras.splice(index, 1)">
						<i class="fas fa-minus"></i>
					</control-button>
				</template>
				<form-field label="Description" :errors="extrasErrors[`extras.${index}.description`]" :required="true">
					<control-input v-model="extra.description" :class="{ 'is-danger': (extrasErrors[`extras.${index}.description`] || []).length }" :readonly="readonly" />
				</form-field>
				<div class="columns">
					<div class="column">
						<form-field label="Price" :errors="extrasErrors[`extras.${index}.price`]" :required="true">
							<control-input v-model="extra.price" :class="{ 'is-danger': (extrasErrors[`extras.${index}.price`] || []).length }" :readonly="readonly" />
						</form-field>
					</div>
					<div class="column">
						<form-field label="Quantity" :errors="extrasErrors[`extras.${index}.quantity`]" :required="true">
							<control-input v-model="extra.quantity" :class="{ 'is-danger': (extrasErrors[`extras.${index}.quantity`] || []).length }" :readonly="readonly" />
						</form-field>
					</div>
				</div>
			</form-panel>
			<div style="min-height: 32px;">
				<a v-if="!readonly" @click.prevent="extras.push({})"
					class="has-text-mauve">
					+ Add another extra.
				</a>
			</div>
			<control-button v-if="!readonly" @click="syncExtras" class="is-primary" :class="{ 'is-loading': isLoading === 'syncExtras' }">Save</control-button>
		</template>
	</card>
</template>

<script>
	import Card from '@dashboard/components/Card';
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import DeleteClient from '@dashboard/pages/IndividualBookings/Clients/Delete';
	import FormField from '@dashboard/components/form/Field';
	import FormPanel from '@dashboard/components/form/Panel';
	import Tab from '@dashboard/components/tabs/Tab';
	import Tabs from '@dashboard/components/tabs/Tabs';

	export default {
		components: {
			Card,
			ControlButton,
			ControlInput,
			ControlSelect,
			DeleteClient,
			FormField,
			FormPanel,
			Tab,
			Tabs,
		},
		data() {
			return {
				savedClient: null,
				client: {},
				clientErrors: [],
				card: {
					card: {},
					address: {}
				},
				cardErrors: [],
				extras: [],
				fitRate: {
					accommodation: null,
					insurance: null
				},
				extrasErrors: [],
				booking: {},
				countries: [],
				showDelete: false,
				tabs: {
					info: true,
					card: false,
					extras: false
				},
				isLoading: '',
				creditCards: {
					default: 'fas fa-credit-card',
					visa: 'fab fa-cc-visa',
					mastercard: 'fab fa-cc-mastercard',
					amex: 'fab fa-cc-amex',
					discover: 'fab fa-cc-discover'
				}
			}
		},
		created() {
			this.fetchData();
		},
		computed: {
			readonly() {
				return !this.savedClient.can.update;
			},
			selectedCountry() {
				return this.countries.find(country => country.id === this.card.address.country);
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
			fetchData() {
				this.$http.get(`individual-bookings/${this.$route.params['id']}/clients/${this.$route.params['client']}`)
					.then(response => {
						this.savedClient = response.data.data;

						this.client = {
							firstName: this.savedClient.firstName,
							lastName: this.savedClient.lastName,
							email: this.savedClient.client.email,
							phone: this.savedClient.phone
						};

						this.extras = this.savedClient.extras.map(extra => ({
							description: extra.description,
							price: extra.price,
							quantity: extra.quantity
						}));

						this.fitRate = {
							accommodation: this.savedClient.fitRate ? this.savedClient.fitRate.accommodation : null,
							insurance: this.savedClient.fitRate ? this.savedClient.fitRate.insurance : null
						};

						this.booking = response.data.booking;
						this.countries = response.data.countries;

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
						label: 'Individual Bookings',
						route: 'individual-bookings'
					},
					{
						label: '#' + this.booking.order + ' ' + this.booking.reservationLeaderFirstName + ' ' + this.booking.reservationLeaderLastName,
						route: 'individual-bookings.show',
						params: {
							id: this.booking.id
						}
					},
					{
						label: 'Clients',
						route: 'individual-bookings.clients',
						params: {
							id: this.booking.id
						}
					},
					{
						label: `${this.savedClient.firstName} ${this.savedClient.lastName}`,
						route: 'individual-bookings.clients.show',
						params: {
							id: this.booking.id,
							client: this.savedClient.id
						}
					}
				]);
			},
			setTab(tab) {
				Object.keys(this.tabs).forEach(key => this.tabs[key] = false);
				this.tabs[tab] = true;
			},
			update() {
				this.isLoading = 'update';

				let request = this.$http.put(`individual-bookings/${this.$route.params['id']}/clients/${this.$route.params['client']}`, this.client)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The client has been updated.'
						});

						this.savedClient = response.data.data;
						this.clientErrors = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.clientErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			deleted() {
				this.$router.push({
					name: 'individual-bookings.clients',
					params: {
						id: this.booking.id
					}
				});
			},
			updateCard() {
				this.isLoading = 'updateCard';

				let request = this.$http.patch(`individual-bookings/${this.$route.params['id']}/clients/${this.$route.params['client']}/card`, this.card)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The card has been updated.'
						});

						this.cardErrors = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.cardErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			setCardType() {
				const number = this.card.card.number.replace(/\D/g, '');
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

				this.card.card.type = type;
			},
			syncExtras() {
				this.isLoading = 'syncExtras';

				let request = this.$http.patch(`individual-bookings/${this.$route.params['id']}/clients/${this.$route.params['client']}/extras`, { extras: this.extras, fitRate: this.fitRate })
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The extras have been saved.'
						});

						this.extrasErrors = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.extrasErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			}
		}
	}
</script>
