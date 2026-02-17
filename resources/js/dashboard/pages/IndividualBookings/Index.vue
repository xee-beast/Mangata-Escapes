<template>
	<card title="Individual Bookings">
		<template v-slot:action>
			<a @click.prevent="showEmailModal = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-envelope"></i></span>
				<span>Send Email</span>
			</a>
			<create-booking v-if="can.create" @created="fetchData()" button-class="is-outlined is-primary is-inverted button-left-margin" />
		</template>
		<data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 25">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-select v-model="filters.agent" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Agents' }, ...agents ]" default-value="" />
				</form-field>
			</data-filter>
			<data-filter>
				<form-field>
					<control-select v-model="filters.provider" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Suppliers' }, ...providers ]" default-value="" />
				</form-field>
			</data-filter>
			<data-filter>
				<form-field>
					<control-select v-model="filters.year" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Years' }, ...years]" default-value="" />
				</form-field>
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
		<data-filters>
			<data-filter>
				<form-field>
					<label class="checkbox">
						<input type="checkbox" :true-value="'true'" :false-value="'false'" v-model="filters.old" @change="filterData()">
						Show Old
					</label>
				</form-field>
			</data-filter>
		</data-filters>
		<template>
			<data-table class="is-size-6" table-class="is-fullwidth" :columns="['#', 'Guests', 'Destination, Accommodation & Dates', 'Reservation', 'Status', 'Balance', 'Balance Due', 'Agent', 'Supplier / ID', 'Special Requests', 'Notes', 'Actions']">
				<template v-if="bookings.length">
					<tr :style="{ color: booking.isPaymentArrangementActive ? '#C7979C' : '', backgroundColor: booking.deletedAt ? '#3C3B3B' : '' }" :class="{'is-booking-deleted': booking.deletedAt }" v-for="(booking, index) in bookings" :key="booking.id">
						<th>
							<div class="has-text-centered">
								{{ booking.order }}
							</div>
						</th>
						<td>
							<ul style="list-style: disc outside; margin-left: 0.5rem;" :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
								<template v-for="client in booking.clients">
									<li v-for="guest in client.guests" :key="guest.id" :class="{ 'deleted': guest.deleted_at }">
										<span style="white-space: nowrap;">
											{{ guest.firstName }} {{ guest.lastName }} {{ printDateOfBirthIfChild(guest.birthDate, booking.checkIn) }} {{ guest.insurance ? '(TI)' : '' }}
											<img v-if="booking.transportation && guest.transportation" :src="guest.transportation" src="data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAgAAAAIACAYAAAD0eNT6AAANvUlEQVR4nO3dXVobuRZAUeBjDgzA8x+SB8AofB/SuU0nGNvgcknaa72mQ1TVdaRNOT/Pp9PpCQBoedl7AQDA4wkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEGvey+Adbwfj6e91wCrezscnvdeA2t4Pp3s2VzPIQ/jEgfcQgDwJQc+zEsQ8BUBwH848GFdgoCPBAAOfQgSAwiAMAc/IAS6BECMQx84Rwy0CIAAhz5wKzGwPgGwMAc/8FNCYF0CYEEOfuDehMB6BMBCHPzA1oTAOgTAAhz8wKMJgfn5x4Am5/AH9mDvmZ83AJMyfMAovA2YkwCYjIMfGJUQmIuPACbi8AdGZo+aizcAEzBUwGy8DRifNwCDc/gDM7J3jc8bgEEZHmAV3gaMyRuAATn8gZXY08YkAAZjUIAV2dvG4yOAQRgOoMJHAmPwBmAADn+gxJ43BgGwM4MAFNn79icAdmQAgDJ74L4EwE48+AD2wj0JgB144AH+ZU/chwB4MA86wN/sjY8nAB7IAw5wnj3ysQTAg3iwAS6zVz6OAHgADzTA9eyZjyEANuZBBridvXN7/irgDXmAx+evJG0zo+Mzo9vxBmAjNhaAn7OXbkcAbMADC3A/9tRtCAAACBIAd6ZUAe7P3np/AuCOPKAA27HH3pcAuBMPJsD27LX3IwAAIEgA3IEiBXgce+59CIAf8iACPJ699+cEAAAECYAfUKAA+7EH/4wA+CYPHsD+7MXfJwAAIEgAfIPiBBiHPfl7BAAABAmAGylNgPHYm28nAG7gAQMYlz36NgIAAIIEwJWUJcD47NXXEwAAECQArqAoAeZhz76OAACAIAFwgZIEmI+9+zIBAABBAuALChJgXvbwrwkAAAgSAAAQJADO8OoIYH728vMEAAAECQAACBIAn/DKCGAd9vTPve69ANiTjQGo8gbgDw4EgPXY2/8mAAAgSAAAQJAA+MArIoB12eP/SwAAQJAAAIAgAQAAQQLgHz4bAlifvf5fAgAAggQAAAQJAAAIEgBPPhMCKLHn/yIAACBIAABAkAAAgCABAABBAgAAggQAAATlA8AfBwHosfcLAABIEgAAECQAACBIAABAkAAAgCABAABBAgAAggQAAAQJAAAIEgAAECQAACBIAABA0OveC6h7Oxye914DwB78gzz78gYAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAICg12v/w/fj8bTFAt4Oh+ctvi4AjGqrM/Xp6fpz9WIAbLlIAOC+fp/bl0LgbAA4+AFgXpdC4NPfA+DwB4A1nDvT/woAhz8ArOWzs/3l0n8AAMzvzzP+5dwPAABr+XjW+3sAACDo5enJd/8AUPH7zPcGAACCXnz3DwAt78fjyRsAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIOjl7XB43nsRAMDjvB0Oz94AAEDQy9PTrxLYeyEAwPZ+n/neAABA0P8DwFsAAFjbx7P+5dwPAADr+POM/+sjABEAAGv57Gz/9PcAiAAAWMO5M/310k94Px5PWy0KANjGpW/mzwbAn19ACADA+K59i38xAG79ggDA10Y4U/09AAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQ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" alt="Transportation" class="transportation-icon" style="height: 16px; width: auto; margin-left: 4px; vertical-align: middle;">
										</span>
										<br>
										{{ printTravelDates(booking, guest) }}
									</li>
								</template>
							</ul>
						</td>
						<td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
							<template v-if="booking.destination">
								{{ booking.destination.name }} <br> <br>
							</template>
							<template v-if="booking.roomArrangements.length > 0">
								<span v-for="(roomArrangement, index) in booking.roomArrangements" :key="roomArrangement.id">
									{{ roomArrangement.room }} - {{ roomArrangement.hotel }}: {{ roomArrangement.bed }} <br>
									{{ $moment.utc(roomArrangement.checkIn).format('MMM DD') }} - {{ $moment.utc(roomArrangement.checkOut).format('MMM DD') }} ({{ $moment.utc(roomArrangement.checkOut).diff($moment.utc(roomArrangement.checkIn), 'days') }} nights)
									<span v-if="index !== booking.roomArrangements.length - 1">
										<br><br>
									</span>
								</span>
							</template>
							<template v-else>
								{{ booking.hotelAssistance ? booking.hotelPreferences : booking.hotelName }} - {{ booking.roomCategory ? booking.roomCategoryName : 'Room Category Unspecified' }} <br>
								{{ $moment.utc(booking.checkIn).format('MMM DD') }} - {{ $moment.utc(booking.checkOut).format('MMM DD') }} ({{ $moment.utc(booking.checkOut).diff($moment.utc(booking.checkIn), 'days') }} nights)
							</template>
						</td>
						<td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
							<ul>
								<li v-for="client in booking.clients" :key="client.id">{{ client.reservationCode }}{{ client.card ? ' : ' + client.card.type.toUpperCase() + ' ' + client.card.lastDigits : '' }}</li>
							</ul>
						</td>
						<td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
							{{ getBookingStatus(booking) }}
						</td>
						<td>
							<div :class="{ 'is-striked': booking.deletedAt, 'has-text-right': true, 'disabled-text': booking.deletedAt }" style="display: inline-block">
								${{ booking.total.toFixed(2) }}
								<br>
								- ${{ booking.totalPayments.toFixed(2) }}
								<hr style="margin: 0">
								${{ (booking.total - booking.totalPayments).toFixed(2) }}
							</div>
						</td>
						<td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
							{{ booking.balanceDueDate ? $moment(booking.balanceDueDate).format('MMMM Do, YYYY') : '-' }}
						</td>
						<td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
							{{ booking.agent ? booking.agent.firstName + ' ' + booking.agent.lastName : '-' }}
						</td>
						<td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
							{{ booking.provider ? booking.provider.abbreviation : '-' }} / {{ booking.providerId ? booking.providerId : '-' }}
						</td>
						<td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
							<a v-if="booking.specialRequests" @click.prevent="showMessage(`Booking #${booking.order} - Special Requests`, booking.specialRequests)">
								<i class="fas fa-eye"></i>
							</a>
							<template v-else>
								-
							</template>
						</td>
						<td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
							<a @click.prevent="showMessage(`Booking #${booking.order} - Notes`, booking.notes, booking.id, booking.can.update)">
								<i v-if="booking.notes" class="fas fa-eye"></i>
								<i v-else class="fas fa-low-vision"></i>
							</a>
						</td>
						<td>
							<a v-if="(booking.can.view || booking.can.update)" class="table-action" :class="{'has-text-danger': booking.pendingChanges}" @click.prevent="show(booking.id)">
								<i class="fas fa-info-circle"></i>
							</a>
							<a v-if="(booking.can.view || booking.can.update)" class="table-action" :class="{'has-text-danger': booking.pendingChanges}" :href="`/individual-bookings/${booking.id}`" target="_blank">
								<i class="fas fa-external-link-alt"></i>
							</a>
							<a v-if="booking.can.confirm" class="table-action" @click.prevent="confirmBooking = {booking: booking}">
								<i class="fas fa-check"></i>
							</a>
							<a v-if="booking.can.delete" class="table-action" @click.prevent="deleteBooking = booking">
								<i class="fas fa-trash"></i>
							</a>
							<template v-else>
								<a v-if="booking.can.restore" class="table-action" @click.prevent="restoreBooking = booking">
									<i class="fas fa-trash-restore"></i>
								</a>
								<a v-if="booking.can.forceDelete" class="table-action" @click.prevent="forceDeleteBooking = booking">
									<i class="fas fa-trash"></i>
								</a>
							</template>
							<table-actions :has-notifications="booking.can.viewPayments && !!booking.pendingPayments">
								<div class="dropdown-item" v-if="booking.can.viewClients">
									<router-link :to="{ name: 'individual-bookings.clients', params: {id: booking.id} }" class="table-action">
										View Clients
									</router-link>
								</div>
								<div class="dropdown-item" v-if="booking.can.viewPayments">
									<router-link :to="{ name: 'individual-bookings.payments', params: {id: booking.id} }" class="table-action">
										View Payments
										<span v-if="booking.pendingPayments" class="notification-counter is-text">
											{{ booking.pendingPayments }}
										</span>
									</router-link>
								</div>
								<template v-if="booking.can.confirm || booking.confirmedAt">
									<div class="dropdown-item">
										<a :href="booking.invoiceUrl" target="_blank" class="table-action">View Invoice</a>
									</div>
									<div class="dropdown-item">
										<a :href="booking.travelDocumentsUrl" target="_blank" class="table-action">View Travel Docs</a>
									</div>
								</template>
							</table-actions>
						</td>
					</tr>
				</template>
				<tr v-else>
					<td>No records found...</td>
				</tr>
			</data-table>
			<paginator v-if="meta.total > 25" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from" :to="meta.to" :total="meta.total" />
			<modal :title="message.title" :is-active="message.show" @hide="hideMessage()">
				<form-field :errors="message.notesErrors" v-if="message.edit && message.bookingId">
					<control-textarea v-model="message.body" :readonly="!message.edit || !message.bookingId" :class="{ 'is-danger': (message.notesErrors || []).length }" />
				</form-field>
				<div v-if="message.edit && message.bookingId" class="buttons mt-2">
					<control-button class="is-primary" :class="{ 'is-loading': isLoading === 'updateNotes' }" @click="updateNotes">Update</control-button>
					<control-button @click="hideMessage()">Cancel</control-button>
				</div>
				<template v-else>
					{{ message.body || '-' }}
				</template>
			</modal>
			<confirm-booking v-if="confirmBooking" v-bind="confirmBooking" @confirmed="confirmed" @canceled="confirmBooking = null" />
			<delete-booking v-if="deleteBooking" :booking="deleteBooking" @deleted="deleted" @canceled="deleteBooking = null" />
			<restore-booking v-if="restoreBooking" :booking="restoreBooking" @restored="restoredBooking" @canceled="restoreBooking = null" />
			<force-delete-booking v-if="forceDeleteBooking" :booking="forceDeleteBooking" @forceDeleted="forceDeleted" @canceled="forceDeleteBooking = null" />
			<modal :is-active="showEmailModal" title="Send message" @hide="closeEmailModal">
				<form-field label="Subject" :errors="emailErrors.subject">
					<control-input v-model="email.subject" class="is-capitalized" :class="{ 'is-danger': (emailErrors.subject || []).length }" />
				</form-field>
				<form-field label="Message" :errors="emailErrors.message">
					<control-textarea v-model="email.message" :class="{ 'is-danger': (emailErrors.message || []).length }" />
				</form-field>
				<template v-slot:footer>
					<div class="field is-grouped">
						<button @click="closeEmailModal" class="button is-dark is-outlined">Close</button>
						<control-button @click="sendBulkEmail" type="submit" class="is-primary" :class="{ 'is-loading': isLoading === 'sendEmail' }">Send</control-button>
					</div>
				</template>
			</modal>
		</template>
	</card>
</template>

<script>
	import Card from '@dashboard/components/Card';
	import CreateBooking from '@dashboard/pages/IndividualBookings/Create';
	import DataTable from '@dashboard/components/table/Table';
	import ConfirmBooking from '@dashboard/pages/IndividualBookings/Confirm';
	import DeleteBooking from '@dashboard/pages/IndividualBookings/Delete';
	import RestoreBooking from '@dashboard/pages/IndividualBookings/Restore';
	import ForceDeleteBooking from '@dashboard/pages/IndividualBookings/ForceDelete';
	import Modal from '@dashboard/components/Modal';
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import DataFilter from '@dashboard/components/table/Filter';
	import DataFilters from '@dashboard/components/table/Filters';
	import FormField from '@dashboard/components/form/Field';
	import PaginationFilter from '@dashboard/components/pagination/Filter';
	import Paginator from '@dashboard/components/pagination/Paginator';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';
	import TableActions from '@dashboard/components/table/Actions';

	export default {
		components: {
			Card,
			CreateBooking,
			DataTable,
			ConfirmBooking,
			DeleteBooking,
			RestoreBooking,
			ForceDeleteBooking,
			Modal,
			ControlButton,
			ControlInput,
			ControlSelect,
			DataFilter,
			DataFilters,
			FormField,
			PaginationFilter,
			Paginator,
			ControlTextarea,
			TableActions,
		},
		data() {
			return {
				bookings: [],
				meta: {},
				can: {},
				filters: {
					paginate: 25,
					page: 1,
					agent: '',
					provider: '',
					year: '',
					search: '',
					old: false,
				},
				message: {
					title: '',
					body: '',
					bookingId: null,
					edit: false,
					show: false,
					notesErrors: [],
				},
				agents: [],
				providers: [],
				years: [],
				confirmBooking: null,
				deleteBooking: null,
				restoreBooking: null,
				forceDeleteBooking: null,
				isLoading: '',
				showEmailModal: false,
				email: {
					subject: '',
					message: ''
				},
				emailErrors: [],
			}
		},
		created() {
			this.filters = Object.assign({}, this.filters, this.$route.query);

			this.fetchData();
		},
		computed: {
			query() {
				return {
					...this.filters
				}
			}
		},
		methods: {
			fetchData() {
				this.$http.get('/individual-bookings', {
						params: this.query
					})
					.then(response => {
						if (response.data.booking_id) {
							this.show(response.data.booking_id);
						} else {
							this.bookings = response.data.data;					
							this.agents = response.data.agents;
							this.providers = response.data.providers;
							this.years = response.data.years;
							this.can = response.data.can;
							this.meta = response.data.meta;

							this.setBreadcrumbs();
						}
					});
			},
			filterData(page = '1') {
				this.$set(this.filters, 'page', page);

				if (JSON.stringify(this.$route.query) !== JSON.stringify(this.filters)) {
					this.$router.replace({
						query: this.filters
					});

					this.fetchData();
				}
			},
			setBreadcrumbs() {
				this.$store.commit('breadcrumbs', [
					{
						label: 'Dashboard',
						route: 'home'
					},
					{
						label: 'Individual Bookings',
						route: 'individual-bookings'
					}
				]);
			},
			show(id) {
				this.$router.push({
					name: 'individual-bookings.show',
					params: {
						id: id
					}
				});
			},
			confirmed() {
				this.confirmBooking = null;

				this.fetchData();
			},
			deleted() {
				this.deleteBooking = null;

				this.fetchData();
			},
			restoredBooking() {
				this.restoreBooking = null;

				this.fetchData();
			},
			forceDeleted() {
				this.forceDeleteBooking = null;

				this.fetchData();
			},
			getBookingStatus(booking) {
				let status = '';

				if (booking.deletedAt) {
					status = status + `Cancelled ${this.$moment(booking.deletedAt).calendar().toLowerCase()}.`;
				} else if (booking.confirmedAt) {
					status = status + `Confirmed ${this.$moment(booking.confirmedAt).calendar().toLowerCase()}.`;
				} else {
					status = status + `Pending since ${this.$moment(booking.createdAt).calendar().toLowerCase()}.`;
				}

				booking.clients.forEach((client) => {
					if (client.acceptedFitQuote) {
						status = status + ` ${client.firstName} ${client.lastName} has accepted their FIT quote.`;
					} else if (client.pendingFitQuote) {
						status = status + ` ${client.firstName} ${client.lastName}'s FIT quote is pending.`;
					} else if (client.discardedFitQuote) {
						if (client.discardedFitQuote.isCancelled) {
							status = status + ` ${client.firstName} ${client.lastName}'s FIT quote was cancelled.`;
						} else {
							status = status + ` ${client.firstName} ${client.lastName}'s FIT quote has expired.`;
						}
					} else {
						status = status + ` ${client.firstName} ${client.lastName} has not received an FIT quote yet.`;
					}
				});	

				return status;
			},
			getAge(birthDate, checkInDate) {
				return this.$moment(checkInDate).diff(birthDate, 'years');
			},
			printDateOfBirthIfChild(birthDate, checkInDate) {
				let age = this.getAge(birthDate, checkInDate);

				return (age < 18) ? ` (${this.$moment(birthDate).format('MM/DD/YYYY')})` : '';
			},
			printTravelDates(booking, guest) {
				let minCheckIn;
				let maxCheckOut;

				if (booking.roomArrangements.length > 0) {
					let roomArrangementDates = booking.roomArrangements.map(room => ({
						checkIn: this.$moment(room.checkIn),
						checkOut: this.$moment(room.checkOut),
					}));

					minCheckIn = this.$moment.min(roomArrangementDates.map(d => d.checkIn).filter(Boolean)).format('MM/DD/YYYY');
					maxCheckOut = this.$moment.max(roomArrangementDates.map(d => d.checkOut).filter(Boolean)).format('MM/DD/YYYY');
				} else {
					minCheckIn = this.$moment(booking.checkIn).format('MM/DD/YYYY');
					maxCheckOut = this.$moment(booking.checkOut).format('MM/DD/YYYY');
				}

				const guestCheckIn = this.$moment(guest.checkIn).format('MM/DD/YYYY');
				const guestCheckOut = this.$moment(guest.checkOut).format('MM/DD/YYYY');

				if (minCheckIn !== guestCheckIn || maxCheckOut !== guestCheckOut) {
					return `Travel Dates (${this.$moment(guestCheckIn, 'MM/DD/YYYY').format('MMM D')} - ${this.$moment(guestCheckOut, 'MM/DD/YYYY').format('MMM D')})`;
				}

				return '';
			},
			showMessage(title, body, bookingId = null, edit = false) {
				this.message.title = title;
				this.message.body = body;
				this.message.bookingId = bookingId;
				this.message.edit = edit;
				this.message.show = true;
			},
			hideMessage() {
				this.message.title = '';
				this.message.body = '';
				this.message.bookingId = null;
				this.message.edit = false;
				this.message.show = false;
				this.message.notesErrors = [];
			},
			updateNotes() {
				this.isLoading = 'updateNotes';

				this.$http.patch(`/individual-bookings/${this.message.bookingId}/update-notes`, {
						notes: this.message.body
					}).then((response) => {
						const booking = this.bookings.find(b => b.id === this.message.bookingId);
						if (booking) booking.notes = response.data.notes;

						this.$store.commit('notification', {
							type: 'success',
							message: 'Notes updated successfully.'
						});

						this.hideMessage();
					})
					.catch(error => {
						if (error.response && error.response.status === 422) {
							this.message.notesErrors = error.response.data.errors.notes;
						}
					})
					.finally(() => {
						this.isLoading = '';
					});
			},
			closeEmailModal() {
				this.showEmailModal = false;
				this.emailErrors = [];
			},
			sendBulkEmail() {
				this.isLoading = 'sendEmail';

				let request = this.$http.post('/send-individual-bookings-bulk-email', {
					...this.email
				}).then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The emails have been sent to all individual bookings.'
					});

					this.emailErrors = [];
					this.email.subject = '';
					this.email.message = '';
					this.showEmailModal = false;
				}).catch(error => {
					if (error.response.status === 422) {
						this.emailErrors = error.response.data.errors;
					}
				});

				request.then(() => {
					this.isLoading = '';
				});
			},
		}
	}
</script>
<style lang="scss">
	.is-booking-deleted {
		color: $alabaster !important;
    td {
      color: $alabaster !important;
       .table-action {
        i {
          color: $alabaster !important;
        }
       }
    }
    th {
      color: $alabaster !important;
    }
	}

	.disabled-text {
			color: #b3b3b3;
	}
	.transportation-icon {
		display: inline-block;
		opacity: 0.7;
	}
</style>
