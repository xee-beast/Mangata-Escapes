<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Booking</a>
	<modal v-if="render" @hide="show = false" title="New Booking" :is-active="show">
		<form-field label="Hotel" :errors="bookingErrors.hotel" :required="true">
			<control-select v-model="booking.hotel" :options="group.hotels.map(hotel => ({value: hotel.id, text: hotel.name}))" first-is-empty
				:class="{ 'is-danger': (bookingErrors.hotel || []).length }" :readonly="group.hotels.length == 1" />
		</form-field>
		<form-field label="Room" :errors="bookingErrors.room" :required="true">
			<control-select v-model="booking.room"
				:options="selectedHotel ? selectedHotel.rooms.filter(room => room.is_active).map(room => ({value: room.id, text: room.name})) : []" first-is-empty
				:class="{ 'is-danger': (bookingErrors.room || []).length }" />
		</form-field>
		<form-field label="Bed Type" :errors="bookingErrors.bed" :required="true">
			<control-select v-model="booking.bed" :options="selectedRoom ? selectedRoom.beds.map(bed => ({value: bed, text: bed})) : []"
				first-is-empty :class="{ 'is-danger': (bookingErrors.bed || []).length }" />
		</form-field>
		<form-field label="Travel Dates" :errors="[...(bookingErrors['dates.start'] || []), ...(bookingErrors['dates.end'] || [])]" :required="true">
			<date-picker v-model="bookingDates" is-range
				:popover="{ visibility: 'focus' }"
				:min-date="$moment(group.eventDate).subtract('10', 'days').toDate()"
				:max-date="$moment(group.eventDate).add('10', 'days').toDate()"
			>
				<template v-slot="{ inputValue, inputEvents }">
					<input
						:value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
						v-on="inputEvents.start"
						:class="'input' + ([...(bookingErrors['dates.start'] || []), ...(bookingErrors['dates.end'] || [])].length ? ' is-danger' : '')"
					/>
				</template>
			</date-picker>
		</form-field>
		<form-field label="Special Requests" :errors="bookingErrors.specialRequests">
			<control-textarea v-model="booking.specialRequests" :class="{ 'is-danger': (bookingErrors.specialRequests || []).length }" />
		</form-field>
		<form-field label="Notes" :errors="bookingErrors.notes">
			<control-textarea v-model="booking.notes" :class="{ 'is-danger': (bookingErrors.notes || []).length }" />
		</form-field>
		<hr>
		<form-panel label="Contact Information" class="is-borderless">
			<div class="columns">
				<div class="column">
					<form-field label="First Name" :errors="bookingErrors['client.firstName']" :required="true">
						<control-input v-model="booking.client.firstName" class="is-capitalized"
							:class="{ 'is-danger': (bookingErrors['client.firstName'] || []).length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Last Name" :errors="bookingErrors['client.lastName']" :required="true">
						<control-input v-model="booking.client.lastName" class="is-capitalized"
							:class="{ 'is-danger': (bookingErrors['client.lastName'] || []).length }" />
					</form-field>
				</div>
			</div>
			<form-field label="Email" :errors="bookingErrors['client.email']" :required="true">
				<control-input v-model="booking.client.email" :class="{ 'is-danger': (bookingErrors['client.email'] || []).length }" />
			</form-field>
			<form-field label="Phone Number" :errors="bookingErrors['client.phone']" :required="true">
				<control-input v-model="booking.client.phone" :class="{ 'is-danger': (bookingErrors['client.phone'] || []).length }" />
			</form-field>
		</form-panel>
		<hr>
		<form-panel label="Guests" class="is-borderless">
			<template v-slot:action v-if="booking.guests.length < (selectedRoom ? selectedRoom.maxOccupants : 1)">
				<control-button class="is-small is-link is-outlined" @click="booking.guests.push({firstName: '', lastName: '', birthDate:'', gender: ''})">
					<i class="fas fa-plus"></i>
				</control-button>
			</template>
			<form-panel v-for="(guest, index) in booking.guests" :key="index" :label="'Guest ' + (index + 1)">
				<template v-slot:action v-if="booking.guests.length > 1">
					<control-button class="is-small is-link is-outlined" @click="booking.guests.splice(index, 1)">
						<i class="fas fa-minus"></i>
					</control-button>
				</template>
				<div class="columns">
					<div class="column">
						<form-field label="First Name" :errors="bookingErrors['guests.' + index + '.firstName']" :required="true">
							<control-input v-model="guest.firstName" class="is-capitalized"
								:class="{ 'is-danger': (bookingErrors['guests.' + index + '.firstName'] || []).length }" />
						</form-field>
            <p v-if="bookingErrors.duplicate_guests_in_request && bookingErrors.duplicate_guests_in_request.includes(index)" class="help is-danger">This guest is being duplicated.</p>
					</div>
					<div class="column">
						<form-field label="Last Name" :errors="bookingErrors['guests.' + index + '.lastName']" :required="true">
							<control-input v-model="guest.lastName" class="is-capitalized"
								:class="{ 'is-danger': (bookingErrors['guests.' + index + '.lastName'] || []).length }" />
						</form-field>
					</div>
				</div>
				<form-field label="Gender" :errors="bookingErrors['guests.' + index + '.gender']" :required="true">
					<control-radio v-model="guest.gender" :options="[{value: 'M', text: 'Male'}, {value: 'F', text: 'Female'}]"
						:class="{ 'is-danger': (bookingErrors['guests.' + index + '.gender'] || []).length }" />
				</form-field>
				<form-field label="Date of Birth" :errors="bookingErrors['guests.' + index + '.birthDate']" :required="true">
					<date-picker v-model="guest.birthDate" @input="setBirthDate(guest, $event)"	:popover="{ visibility: 'focus' }">
						<template v-slot="{ inputValue, inputEvents }">
							<input
								:class="'input' + ((bookingErrors['guests.' + index + '.birthDate'] || []).length ? ' is-danger' : '')"
								:value="inputValue"
								v-on="inputEvents"
							/>
						</template>
					</date-picker>
				</form-field>
			</form-panel>
		</form-panel>
		<hr>
		<form-field label="Travel Insurance" :errors="bookingErrors.insurance" :required="true">
			<control-radio v-model="booking.insurance" :options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
				:class="{ 'is-danger': (bookingErrors.insurance || []).length }" />
		</form-field>
		<form-field v-if="!this.group.fit" label="Payment Amount" :errors="bookingErrors.payment" :required="true">
			<control-input v-model="booking.payment" placeholder="Submit without an amount to view the minimum payment amount."
				:class="{ 'is-danger': (bookingErrors.payment || []).length }" />
		</form-field>
		<hr v-if="!this.group.fit">
		<form-panel label="Billing Information" class="is-borderless" v-if="!this.group.fit">
			<form-panel label="Credit Card Information">
				<form-field label="Cardholder Name" :errors="bookingErrors['card.name']" :required="true">
					<control-input v-model="booking.card.name" class="is-capitalized"
						:class="{ 'is-danger': (bookingErrors['card.name'] || []).length }" />
				</form-field>
				<form-field label="Credit Card Number" :errors="[...(bookingErrors['card.number'] || []), ...(bookingErrors['card.type'] || [])]" :required="true">
					<control-input v-model="booking.card.number" @input="setCardType"
						:class="{ 'is-danger': (bookingErrors['card.number'] || []).length }" />
					<template v-slot:addon>
						<control-button class="is-static" :class="{ 'is-danger is-outlined': (bookingErrors['card.type'] || []).length }">
							<i class="is-size-4" :class="creditCards[booking.card.type || 'default']"></i>
						</control-button>
					</template>
				</form-field>
				<div class="columns">
					<div class="column is-4">
						<form-field label="Expiration Date"
							:errors="[...(bookingErrors['card.expMonth'] || []), ...(bookingErrors['card.expYear'] || [])]" :required="true">
							<control-select v-model="booking.card.expMonth" :options="expMonths" first-is-empty
								:class="{ 'is-danger': (bookingErrors['card.expMonth'] || []).length }" />
							<template v-slot:addon>
								<control-select v-model="booking.card.expYear" :options="expYears" first-is-empty
									:class="{ 'is-danger': (bookingErrors['card.expYear'] || []).length }" />
							</template>
						</form-field>
					</div>
					<div class="column is-4">
						<form-field label="CVV Code" :errors="bookingErrors['card.code']" :required="true">
							<control-input v-model="booking.card.code" :class="{ 'is-danger': (bookingErrors['card.code'] || []).length }" />
						</form-field>
					</div>
				</div>
			</form-panel>
			<form-panel label="Billing Address">
				<form-field label="Country" :errors="bookingErrors['address.country']" :required="true">
					<control-select v-model="booking.address.country"
						:options="[{value: 0, text: 'Other...'}, ...countries.map(country => ({value: country.id, text: country.name}))]"
						:class="{ 'is-danger': (bookingErrors['address.country'] || []).length }" :disabled="booking.address.hasOtherCountry" />
				</form-field>
				<form-field v-if="!booking.address.country" label="Other Country" :errors="bookingErrors['address.otherCountry']" :required="true">
					<control-input v-model="booking.address.otherCountry"
						:class="{ 'is-danger': (bookingErrors['address.otherCountry'] || []).length }" />
				</form-field>
				<div class="columns">
					<div class="column">
						<form-field v-if="booking.address.country" label="State/Province" :errors="bookingErrors['address.state']" :required="true">
							<control-select v-model="booking.address.state"
								:options="selectedCountry ? selectedCountry.states.map(state => ({value: state.id, text: state.name})) : []"
								first-is-empty :class="{ 'is-danger': (bookingErrors['address.state'] || []).length }" />
						</form-field>
						<form-field v-else label="State/Province" :errors="bookingErrors['address.otherState']" :required="true">
							<control-input v-model="booking.address.otherState"
								:class="{ 'is-danger': (bookingErrors['address.otherState'] || []).length }" />
						</form-field>
					</div>
					<div class="column">
						<form-field label="City" :errors="bookingErrors['address.city']" :required="true">
							<control-input v-model="booking.address.city" :class="{ 'is-danger': (bookingErrors['address.city'] || []).length }" />
						</form-field>
					</div>
				</div>
				<form-field label="Address Line 1" :errors="bookingErrors['address.line1']" :required="true">
					<control-input v-model="booking.address.line1" :class="{ 'is-danger': (bookingErrors['address.line1'] || []).length }" />
				</form-field>
				<div class="columns">
					<div class="column is-8">
						<form-field label="Address Line 2" :errors="bookingErrors['address.line2']">
							<control-input v-model="booking.address.line2" :class="{ 'is-danger': (bookingErrors['address.line2'] || []).length }" />
						</form-field>
					</div>
					<div class="column is-4">
						<form-field label="Zip/Postal Code" :errors="bookingErrors['address.zipCode']" :required="true">
							<control-input v-model="booking.address.zipCode"
								:class="{ 'is-danger': (bookingErrors['address.zipCode'] || []).length }" />
						</form-field>
					</div>
				</div>
			</form-panel>
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
  <modal title="Warning!" :is-active="showGuestWarnings" @hide="cancelGuestCreate">
    <ul style="list-style: inside disc;">
      <li v-for="(warning, index) in guestWarnings" :key="index">{{ warning }}</li>
    </ul>
    <template v-slot:footer>
      <div class="field is-grouped">
        <control-button @click="cancelGuestCreate">Cancel Create</control-button>
        <control-button @click="ignoreGuestWarningsAndCreate" type="submit" class="is-primary">Ignore & Create</control-button>
      </div>
    </template>
  </modal>
</div>
</template>

<script>
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlRadio from '@dashboard/components/form/controls/Radio';
import ControlSelect from '@dashboard/components/form/controls/Select';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';
import DatePicker from 'v-calendar/lib/components/date-picker.umd';
import FormField from '@dashboard/components/form/Field';
import FormPanel from '@dashboard/components/form/Panel';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlInput,
		ControlRadio,
		ControlSelect,
		ControlTextarea,
		DatePicker,
		FormField,
		FormPanel,
		Modal,
	},
	props: {
		group: {
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
			booking: {
				hotel: this.group.hotels.length ? (this.group.hotels.length > 1 ? null : this.group.hotels[0].id) : null,
				client: {},
				guests: [{}],
				card: {},
				address: {
					country: 1
				},
				dates: {},
			},
			bookingErrors: {},
			isLoading: false,
			showGuestWarnings: false,
		  ignoreGuestWarnings: false,
			guestWarnings: [],
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
		selectedHotel() {
			return this.group.hotels.find(hotel => hotel.id == this.booking.hotel) || null;
		},
		selectedRoom() {
			return this.selectedHotel ? this.selectedHotel.rooms.find(room => room.id == this.booking.room) : null;
		},
		selectedCountry() {
			return this.countries.find(country => country.id === this.booking.address.country);
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
		bookingDates: {
			get() {
				return this.booking.dates;
			},
			set(dates) {
				if (! dates) {
					this.booking.dates = {};
				}
				this.booking.dates.start = dates.start instanceof Date ? dates.start.toDateString() : null;
				this.booking.dates.end = dates.end instanceof Date ? dates.end.toDateString() : null;
			},
		},
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/groups/' + this.$route.params.group + '/bookings',{ ...this.booking, ignoreGuestWarnings: this.ignoreGuestWarnings,})
				.then(response => {
          if (response.data.warnings && response.data.warnings.length) {
            this.showGuestWarnings = true;
            this.guestWarnings = response.data.warnings;
          }else{
          	this.close();
            this.$emit('created', response.data.data);
            this.$store.commit('notification', {
              type: 'success',
              message: 'The new booking has been created.',
            });
          }
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.bookingErrors = error.response.data.errors;
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
			const number = this.booking.card.number.replace(/\D/g, '');
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
			this.booking.card.type = type;
		},
		setBirthDate(guest, date) {
			guest.birthDate = date instanceof Date ? date.toDateString() : null;
		},
    cancelGuestCreate() {
			this.showGuestWarnings = false;
			this.ignoreGuestWarnings = false;
			this.guestWarnings = [];
		},
		ignoreGuestWarningsAndCreate() {
			this.showGuestWarnings = false;
			this.ignoreGuestWarnings = true;
			this.guestWarnings = [];

			this.create();
		},
	}
}
</script>
