<template>
	<div>
		<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Booking</a>

		<modal v-if="render" @hide="show = false" title="New Booking" :is-active="show">
			<form-field label="Hotel Assistance" :errors="bookingErrors.hotelAssistance" :required="true">
				<control-radio
					v-model="booking.hotelAssistance"
					:options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
					:class="{ 'is-danger': (bookingErrors.hotelAssistance || []).length }"
				/>
			</form-field>
			<form-field v-if="booking.hotelAssistance" label="Hotel Preferences" :errors="bookingErrors.hotelPreferences" :required="true">
				<control-textarea v-model="booking.hotelPreferences" :class="{ 'is-danger': (bookingErrors.hotelPreferences || []).length }" />
			</form-field>
			<form-field v-else label="Hotel Name" :errors="bookingErrors.hotelName" :required="true">
				<control-input v-model="booking.hotelName" class="is-capitalized" :class="{ 'is-danger': (bookingErrors.hotelName || []).length }" />
			</form-field>
			<form-field label="Room Category" :errors="bookingErrors.roomCategory" :required="true">
				<control-radio
					v-model="booking.roomCategory"
					:options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
					:class="{ 'is-danger': (bookingErrors.roomCategory || []).length }"
				/>
			</form-field>
			<form-field v-if="booking.roomCategory" label="Room Category Name" :errors="bookingErrors.roomCategoryName" :required="true">
				<control-input v-model="booking.roomCategoryName" class="is-capitalized" :class="{ 'is-danger': (bookingErrors.roomCategoryName || []).length }" />
			</form-field>
			<form-field label="Travel Dates" :errors="[...(bookingErrors['dates.start'] || []), ...(bookingErrors['dates.end'] || [])]" :required="true">
				<date-picker
					v-model="bookingDates"
					is-range
					:popover="{ visibility: 'focus' }"
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
			<form-field label="Budget" :errors="bookingErrors.budget">
				<control-input v-model="booking.budget" :class="{ 'is-danger': (bookingErrors.budget || []).length }" />
			</form-field>
			<hr>
			<form-panel label="Contact Information" class="is-borderless">
				<div class="columns">
					<div class="column">
						<form-field label="First Name" :errors="bookingErrors['client.firstName']" :required="true">
							<control-input v-model="booking.client.firstName" class="is-capitalized" :class="{ 'is-danger': (bookingErrors['client.firstName'] || []).length }" />
						</form-field>
					</div>
					<div class="column">
						<form-field label="Last Name" :errors="bookingErrors['client.lastName']" :required="true">
							<control-input v-model="booking.client.lastName" class="is-capitalized" :class="{ 'is-danger': (bookingErrors['client.lastName'] || []).length }" />
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
				<template v-slot:action>
					<control-button class="is-small is-link is-outlined" @click="booking.guests.push({})">
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
								<control-input v-model="guest.firstName" class="is-capitalized" :class="{ 'is-danger': (bookingErrors['guests.' + index + '.firstName'] || []).length }" />
							</form-field>
						</div>
						<div class="column">
							<form-field label="Last Name" :errors="bookingErrors['guests.' + index + '.lastName']" :required="true">
								<control-input v-model="guest.lastName" class="is-capitalized" :class="{ 'is-danger': (bookingErrors['guests.' + index + '.lastName'] || []).length }" />
							</form-field>
						</div>
					</div>
          <p v-if="bookingErrors.duplicate_guests_in_request && bookingErrors.duplicate_guests_in_request.includes(index)" class="help is-danger">This guest is being duplicated.</p>
					<form-field label="Gender" :errors="bookingErrors['guests.' + index + '.gender']" :required="true">
						<control-radio
							v-model="guest.gender"
							:options="[{value: 'M', text: 'Male'}, {value: 'F', text: 'Female'}]"
							:class="{ 'is-danger': (bookingErrors['guests.' + index + '.gender'] || []).length }"
						/>
					</form-field>
					<form-field label="Date of Birth" :errors="bookingErrors['guests.' + index + '.birthDate']" :required="true">
						<date-picker
							v-model="guest.birthDate"
							@input="setBirthDate(guest, $event)"
							:popover="{ visibility: 'focus' }"
						>
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
			<form-field label="Quote Flights" :errors="bookingErrors.transportation" :required="true">
				<control-radio
					v-model="booking.transportation"
					:options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
					:class="{ 'is-danger': (bookingErrors.transportation || []).length }"
				/>
			</form-field>
			<form-field v-if="booking.transportation" label="Departure Gateway" :errors="bookingErrors.departureGateway">
				<control-input v-model="booking.departureGateway" :class="{ 'is-danger': (bookingErrors.departureGateway || []).length }" />
			</form-field>
			<form-field v-if="booking.transportation" label="Flight Preferences" :errors="bookingErrors.flightPreferences">
				<control-textarea v-model="booking.flightPreferences" :class="{ 'is-danger': (bookingErrors.flightPreferences || []).length }" />
			</form-field>
			<form-field v-if="booking.transportation" label="Airline Membership Number" :errors="bookingErrors.airlineMembershipNumber">
				<control-input v-model="booking.airlineMembershipNumber" :class="{ 'is-danger': (bookingErrors.airlineMembershipNumber || []).length }" />
			</form-field>
			<form-field v-if="booking.transportation" label="Known Traveler Number (KTN)" :errors="bookingErrors.knownTravelerNumber">
				<control-input v-model="booking.knownTravelerNumber" :class="{ 'is-danger': (bookingErrors.knownTravelerNumber || []).length }" />
			</form-field>
			<form-field v-if="booking.transportation" label="Message" :errors="bookingErrors.flightMessage">
				<control-textarea v-model="booking.flightMessage" :class="{ 'is-danger': (bookingErrors.flightMessage || []).length }" />
			</form-field>
			<hr>
			<form-field label="Travel Insurance" :errors="bookingErrors.insurance" :required="true">
				<control-radio v-model="booking.insurance" :options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
					:class="{ 'is-danger': (bookingErrors.insurance || []).length }" />
			</form-field>
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
			ControlTextarea,
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
				booking: {
					hotelAssistance: false,
					hotelPreferences: null,
					hotelName: null,
					roomCategory: true,
					roomCategoryName: null,
					dates: {},
					specialRequests: null,
					budget: null,
					client: {},
					guests: [{}],
					transportation: true,
					departureGateway: null,
					flightPreferences: null,
					airlineMembershipNumber: null,
					knownTravelerNumber: null,
					flightMessage: null,
					insurance: null,
				},
				bookingErrors: {},
				isLoading: false,
			}
		},
		computed: {
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

				let request = this.$http.post('/individual-bookings', this.booking)
					.then(response => {
						this.close();
						this.$emit('created', response.data.data);

						this.$store.commit('notification', {
							type: 'success',
							message: 'The new booking has been created.',
						});
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
			setBirthDate(guest, date) {
				guest.birthDate = date instanceof Date ? date.toDateString() : null;
			},
		}
	}
</script>
