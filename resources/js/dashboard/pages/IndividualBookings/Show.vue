<template>
	<card v-if="savedBooking" :title="'Individual Booking #' + savedBooking.order" :booking-status="savedBooking.deletedAt ? 'cancelled' : 'active'">
		<template v-slot:action>
			<a @click.prevent="undo" class="button is-outlined is-primary is-inverted" :style="!canUndo ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : ''">
				<span class="icon"><i class="fas fa-undo"></i></span>
			</a>
			<a @click.prevent="redo" class="button is-outlined is-primary is-inverted" :style="!canRedo ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : ''">
				<span class="icon"><i class="fas fa-redo"></i></span>
			</a>
			<a v-if="previousBooking" :href="`${$dashboardBase}/individual-bookings/${previousBooking.id}`"	class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-arrow-left"></i></span>
			</a>
			<a v-if="nextBooking" :href="`${$dashboardBase}/individual-bookings/${nextBooking.id}`"	class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-arrow-right"></i></span>
			</a>
      <a :href="savedBooking.invoiceUrl" target="_blank" class="button is-outlined is-primary is-inverted">
        <span class="icon"><i class="fas fa-file-invoice"></i></span>
        <span>View Invoice</span>
      </a>
			<template v-if="!savedBooking.quoteAccepted">
				<template v-if="!readonly">
					<a @click.prevent="showSendFitQuote = true" class="button is-outlined is-primary is-inverted">
						<span class="icon"><i class="fas fa-fax"></i></span>
						<span>Send Quote</span>
					</a>
					<send-fit-quote v-if="showSendFitQuote" :booking="savedBooking" @fitQuoteSent="fitQuoteSent" @canceled="showSendFitQuote = false" />
				</template>
			</template>
			<template v-else>
				<template v-if="!readonly">
					<a @click.prevent="showSendInvoice = true" class="button is-outlined is-primary is-inverted">
						<span class="icon"><i class="fas fa-envelope-open-text"></i></span>
						<span>Send Invoice</span>
					</a>
					<send-invoice v-if="showSendInvoice" :booking="savedBooking" @invoiceSent="invoiceSent" @canceled="showSendInvoice = false" />
				</template>
				<a :href="savedBooking.travelDocumentsUrl" target="_blank" class="button is-outlined is-primary is-inverted">
					<span class="icon"><i class="fas fa-map"></i></span>
					<span>View Travel Docs</span>
				</a>
				<template v-if="!readonly">
					<a @click.prevent="showSendTravelDocuments = true" class="button is-outlined is-primary is-inverted">
						<span class="icon"><i class="fas fa-paper-plane"></i></span>
						<span>Send Travel Docs</span>
					</a>
					<send-travel-documents v-if="showSendTravelDocuments" :booking="savedBooking" @travelDocumentsSent="travelDocumentsSent" @canceled="showSendTravelDocuments = false" />
				</template>
			</template>
			<router-link v-if="savedBooking.can.viewClients" :to="{ name: 'individual-bookings.clients', params: { id: savedBooking.id }}" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-user-tie"></i></span>
				<span>Clients</span>
			</router-link>
			<router-link v-if="savedBooking.can.viewPayments" :to="{ name: 'individual-bookings.payments', params: { id: savedBooking.id }}" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-hand-holding-usd"></i></span>
				<span>Payments</span>
				<span v-if="savedBooking.pendingPayments" class="notification-counter">
					{{ savedBooking.pendingPayments }}
				</span>
			</router-link>
			<template v-if="savedBooking.can.confirm">
				<a @click.prevent="showConfirm = true" class="button is-outlined is-primary is-inverted">
					<span class="icon"><i class="fas fa-check"></i></span>
				</a>
				<confirm-booking v-if="showConfirm" :booking="savedBooking" @confirmed="confirmed" @canceled="showConfirm = false" />
			</template>
			<template v-if="savedBooking.can.delete">
				<a @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
					<span class="icon"><i class="fas fa-trash"></i></span>
				</a>
				<delete-booking v-if="showDelete" :booking="savedBooking" @deleted="deleted" @canceled="showDelete = false" />
			</template>
			<template v-if="savedBooking.can.restore">
				<a @click.prevent="showRestore = true" class="button is-outlined is-primary is-inverted">
					<span class="icon"><i class="fas fa-trash-restore"></i></span>
				</a>
				<restore-booking v-if="showRestore" :booking="savedBooking" @restored="restored" @canceled="showRestore = false" />
			</template>
			<template v-if="savedBooking.can.forceDelete">
				<a @click.prevent="showForceDelete = true" class="button is-outlined is-primary is-inverted">
					<span class="icon"><i class="fas fa-trash"></i></span>
				</a>
				<force-delete-booking v-if="showForceDelete" :booking="savedBooking" @forceDeleted="forceDeleted" @canceled="showForceDelete = false" />
			</template>
		</template>
		<template v-slot:tabs>
			<tabs class="is-boxed">
				<tab @click="setTab('info')" :is-active="tabs.info">Booking</tab>
				<tab @click="setTab('bookingDueDates')" :is-active="tabs.bookingDueDates">Due Dates</tab>
				<tab @click="setTab('roomArrangements')" :is-active="tabs.roomArrangements">Room Arrangements</tab>
				<tab @click="setTab('guests')" :is-active="tabs.guests">Guests</tab>
				<tab v-if="savedBooking.transportation" @click="setTab('flightManifests')" :is-active="tabs.flightManifests">Flight Itineraries</tab>
				<tab v-if="hasFlightDetails" @click="setTab('flightDetails')" :is-active="tabs.flightDetails">Flight Details</tab>
				<tab @click="setTab('paymentArrangements')" :is-active="tabs.paymentArrangements">Payment Arrangements</tab>
				<tab @click="setTab('termsConditions')" :is-active="tabs.termsConditions">Terms & Conditions</tab>
			</tabs>
		</template>
		<template v-if="tabs.info">
			<div class="columns">
				<div class="column">
					<form-field label="Hotel Assistance" :errors="bookingErrors.hotelAssistance" :required="true">
						<control-radio
							v-model="booking.hotelAssistance"
							:options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
							:class="{ 'is-danger': (bookingErrors.hotelAssistance || []).length }"
							:readonly="readonly"
						/>
					</form-field>
				</div>
				<div class="column">
					<form-field v-if="booking.hotelAssistance" label="Hotel Preferences" :errors="bookingErrors.hotelPreferences" :required="true">
						<control-textarea v-model="booking.hotelPreferences" :model-path="'booking.hotelPreferences'" :readonly="readonly" :class="{ 'is-danger': (bookingErrors.hotelPreferences || []).length }" />
					</form-field>
					<form-field v-else label="Hotel Name" :errors="bookingErrors.hotelName" :required="true">
						<control-input v-model="booking.hotelName" class="is-capitalized" :readonly="readonly" :class="{ 'is-danger': (bookingErrors.hotelName || []).length }" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Room Category" :errors="bookingErrors.roomCategory" :required="true">
						<control-radio
							v-model="booking.roomCategory"
							:options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
							:class="{ 'is-danger': (bookingErrors.roomCategory || []).length }"
							:readonly="readonly"
						/>
					</form-field>
				</div>
				<div class="column">
					<form-field v-if="booking.roomCategory" label="Room Category Name" :errors="bookingErrors.roomCategoryName" :required="true">
						<control-input v-model="booking.roomCategoryName" class="is-capitalized" :readonly="readonly" :class="{ 'is-danger': (bookingErrors.roomCategoryName || []).length }" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Travel Dates" :errors="[...(bookingErrors['dates.start'] || []), ...(bookingErrors['dates.end'] || [])]" :required="true">
						<div class="field has-addons">
							<div class="control is-expanded">
								<date-picker
									v-model="booking.dates"
									is-range
									:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
								>
									<template v-slot="{ inputValue, inputEvents }">
										<input
											:readonly="readonly"
											:value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
											v-on="inputEvents.start"
											:class="'input' + ([...(bookingErrors['dates.start'] || []), ...(bookingErrors['dates.end'] || [])].length ? ' is-danger' : '')"
										/>
									</template>
								</date-picker>
							</div>
							<div class="control">
								<button type="button" class="button is-primary" @click="applyDatesToAllGuests(booking.dates)" :disabled="readonly">
									Apply to All Guests
								</button>
							</div>
						</div>
					</form-field>
				</div>
				<div class="column">
					<form-field label="Budget" :errors="bookingErrors.budget">
						<control-input v-model="booking.budget" :class="{ 'is-danger': (bookingErrors.budget || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>			
			<form-field label="Special Requests" :errors="bookingErrors.specialRequests">
				<control-textarea 
					v-model="booking.specialRequests" 
					:class="{ 'is-danger': (bookingErrors.specialRequests || []).length }"
					:readonly="readonly" 
					:model-path="'booking.specialRequests'"
				/>
			</form-field>
			<form-field label="Notes" :errors="bookingErrors.notes">
				<control-textarea 
					v-model="booking.notes" 
					:class="{ 'is-danger': (bookingErrors.notes || []).length }" 
					:readonly="readonly"
					:model-path="'booking.notes'"
				/>
			</form-field>
			<div class="columns">
				<div class="column">
					<form-field label="Booking ID" :errors="bookingErrors.bookingId" :required="true">
						<control-input v-model="booking.bookingId" :class="{ 'is-danger': (bookingErrors.bookingId || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Minimum Deposit" :errors="[...(bookingErrors.deposit || []), ...(bookingErrors.depositType || [])]" :required="true">
						<control-input v-model="booking.deposit" :class="{ 'is-danger': (bookingErrors.deposit || []).length }" :readonly="readonly" />
						<template v-slot:addon>
							<control-select
								v-model="booking.depositType"
								:options="[
									{ value: 'fixed', text: '$' },
									{ value: 'percentage', text: '%' }
								]"
								:class="{ 'is-danger': (bookingErrors.depositType || []).length }"
								:readonly="readonly"
							/>
						</template>
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Transportation" :errors="bookingErrors.transportation" :required="true">
						<control-radio
							v-model="booking.transportation"
							:options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
							:class="{ 'is-danger': (bookingErrors.transportation || []).length }"
							:readonly="readonly"
						/>
					</form-field>
				</div>
				<div class="column" v-if="booking.transportation">
					<form-field label="Departure Gateway" :errors="bookingErrors.departureGateway">
						<control-input v-model="booking.departureGateway" :class="{ 'is-danger': (bookingErrors.departureGateway || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<template v-if="booking.transportation">
				<div class="columns">
					<div class="column">
						<form-field label="Flight Preferences" :errors="bookingErrors.flightPreferences">
							<control-textarea v-model="booking.flightPreferences" :model-path="'booking.flightPreferences'" :class="{ 'is-danger': (bookingErrors.flightPreferences || []).length }" :readonly="readonly" />
						</form-field>
					</div>
				</div>
				<div class="columns">
					<div class="column">
						<form-field label="Airline Membership Number" :errors="bookingErrors.airlineMembershipNumber">
							<control-input v-model="booking.airlineMembershipNumber" :class="{ 'is-danger': (bookingErrors.airlineMembershipNumber || []).length }" :readonly="readonly" />
						</form-field>
					</div>
					<div class="column">
						<form-field label="Known Traveler Number (KTN)" :errors="bookingErrors.knownTravelerNumber">
							<control-input v-model="booking.knownTravelerNumber" :class="{ 'is-danger': (bookingErrors.knownTravelerNumber || []).length }" :readonly="readonly" />
						</form-field>
					</div>
				</div>
				<div class="columns">
					<div class="column">
						<form-field label="Message" :errors="bookingErrors.flightMessage">
							<control-textarea v-model="booking.flightMessage" :model-path="'booking.flightMessage'" :class="{ 'is-danger': (bookingErrors.flightMessage || []).length }" :readonly="readonly" />
						</form-field>
					</div>
				</div>
				<div class="columns">
					<div class="column">
						<form-field label="Transportation Type" :errors="bookingErrors.transportationType">
							<control-select v-model="booking.transportationType" :options="[{value: 'private', text: 'Private'}, {value: 'shared', text: 'Shared'}]" :class="{ 'is-danger': (bookingErrors.transportationType || []).length }" :readonly="readonly" />
						</form-field>
					</div>
					<div class="column">
						<form-field label="Submit Flight Itinerary Before" :errors="bookingErrors.transportationSubmitBefore">
							<date-picker
								v-model="booking.transportationSubmitBefore"
								:max-date="savedBooking.checkIn"
								:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
							>
								<template v-slot="{ inputValue, inputEvents }">
									<input
										:class="'input' + ((bookingErrors.transportationSubmitBefore || []).length ? ' is-danger' : '')"
										:readonly="readonly"
										:value="inputValue"
										v-on="inputEvents"
									/>
								</template>
							</date-picker>
						</form-field>
					</div>
				</div>
				<div class="columns">
					<div class="column">
						<form-field label="Transfer Provider" :errors="bookingErrors.transfer">
							<control-select v-model="booking.transfer" :options="transfers" :class="{ 'is-danger': (bookingErrors.transfer || []).length }"	:readonly="readonly" />
						</form-field>
					</div>
				</div>
			</template>
			<div class="columns">
				<div class="column">
					<form-field label="Destination" :errors="bookingErrors.destination">
						<control-select v-model="destination" :options="destinations" :class="{ 'is-danger': (bookingErrors.destination || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Email" :errors="bookingErrors.email" :required="true">
						<control-input v-model="booking.email" class="is-lowercase" :class="{ 'is-danger': (bookingErrors.email || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Reservation Leader's First Name" :errors="bookingErrors.reservationLeaderFirstName" :required="true">
						<control-input v-model="booking.reservationLeaderFirstName" class="is-capitalized" :class="{ 'is-danger': (bookingErrors.reservationLeaderFirstName || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Reservation Leader's Last Name" :errors="bookingErrors.reservationLeaderLastName" :required="true">
						<control-input v-model="booking.reservationLeaderLastName" class="is-capitalized" :class="{ 'is-danger': (bookingErrors.reservationLeaderLastName || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Travel Agent" :errors="bookingErrors.agent" :required="true">
						<control-select v-model="booking.agent" :options="agents" :class="{ 'is-danger': (bookingErrors.agent || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Supplier" :errors="bookingErrors.provider" :required="true">
						<control-select v-model="booking.provider" :options="providers" :class="{ 'is-danger': (bookingErrors.provider || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Supplier ID" :errors="bookingErrors.providerId">
						<control-input v-model="booking.providerId" :class="{ 'is-danger': (bookingErrors.providerId || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Change Fee Date" :errors="bookingErrors.changeFeeDate">
						<date-picker
							v-model="booking.changeFeeDate"
							:max-date="savedBooking.checkIn"
							:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
						>
							<template v-slot="{ inputValue, inputEvents }">
								<input
									:class="'input' + ((bookingErrors.changeFeeDate || []).length ? ' is-danger' : '')"
									:readonly="readonly"
									:value="inputValue"
									v-on="inputEvents"
								/>
							</template>
						</date-picker>
					</form-field>
				</div>
				<div class="column">
					<form-field label="Change Fee Amount" :errors="[...(bookingErrors.changeFeeAmount || [])]">
						<control-input v-model="booking.changeFeeAmount" :class="{ 'is-danger': (bookingErrors.changeFeeAmount || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Dashboard Message" :errors="bookingErrors.staffMessage">
						<control-textarea v-model="booking.staffMessage" :class="{ 'is-danger': (bookingErrors.staffMessage || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Cover Image (Travel Documents)" :errors="bookingErrors.travelDocsCoverImage">
						<image-uploader
							v-model="booking.travelDocsCoverImage"
							@errors="$set(bookingErrors, 'travelDocsCoverImage', $event)"
							:class="{ 'is-danger': (bookingErrors.travelDocsCoverImage || []).length }"
							:max-size="3072"
							is-single
							:disabled="readonly"
						/>
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Second Image (Travel Documents)" :errors="bookingErrors.travelDocsImageTwo">
						<image-uploader
							v-model="booking.travelDocsImageTwo"
							@errors="$set(bookingErrors, 'travelDocsImageTwo', $event)"
							:class="{ 'is-danger': (bookingErrors.travelDocsImageTwo || []).length }"
							:max-size="3072"
							is-single
							:disabled="readonly"
						/>
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Third Image (Travel Documents)" :errors="bookingErrors.travelDocsImageThree">
						<image-uploader
							v-model="booking.travelDocsImageThree"
							@errors="$set(bookingErrors, 'travelDocsImageThree', $event)"
							:class="{ 'is-danger': (bookingErrors.travelDocsImageThree || []).length }"
							:max-size="3072"
							is-single
							:disabled="readonly"
						/>
					</form-field>
				</div>
			</div>
			<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
		</template>
		<template v-if="tabs.bookingDueDates">
			<form-field label="Cancellation Date" :errors="bookingDueDatesErrors.cancellationDate" :required="true">
				<date-picker
					v-model="bookingDueDates.cancellationDate"
					:max-date="savedBooking.checkIn"
					:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
				>
					<template v-slot="{ inputValue, inputEvents }">
						<input
							:class="'input' + ((bookingDueDatesErrors.cancellationDate || []).length ? ' is-danger' : '')"
							:readonly="readonly"
							:value="inputValue"
							v-on="inputEvents"
						/>
					</template>
				</date-picker>
			</form-field>
			<form-field label="Balance Due Date" :errors="bookingDueDatesErrors.balanceDueDate" :required="true">
				<date-picker
					v-model="bookingDueDates.balanceDueDate"
					:max-date="savedBooking.checkIn"
					:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
				>
					<template v-slot="{ inputValue, inputEvents }">
						<input
							:class="'input' + ((bookingDueDatesErrors.balanceDueDate || []).length ? ' is-danger' : '')"
							:readonly="readonly"
							:value="inputValue"
							v-on="inputEvents"
						/>
					</template>
				</date-picker>
			</form-field>
			<form-panel label="Other Due Dates" class="is-borderless">
				<template v-slot:action>
					<control-button v-if="!readonly" class="is-small is-link is-outlined" @click="bookingDueDates.dueDates.push({date: null, amount: null, type: 'price'})">
						<i class="fas fa-plus"></i>
					</control-button>
				</template>
				<form-panel v-for="(dueDate, index) in bookingDueDates.dueDates" :key="index">
					<template v-if="!readonly" v-slot:action>
						<control-button class="is-small is-link is-outlined" @click="bookingDueDates.dueDates.splice(index, 1)">
							<i class="fas fa-minus"></i>
						</control-button>
					</template>
					<form-field label="Date" :errors="bookingDueDatesErrors['dueDates.' + index + '.date'] || []" :required="true">
						<date-picker
							v-model="dueDate.date"
							:max-date="savedBooking.checkIn"
							:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
						>
							<template v-slot="{ inputValue, inputEvents }">
								<input
									:class="'input' + ((bookingDueDatesErrors['dueDates.' + index + '.date'] || []).length ? ' is-danger' : '')"
									:readonly="readonly"
									:value="inputValue"
									v-on="inputEvents"
								/>
							</template>
						</date-picker>
					</form-field>
					<form-field label="Amount" :errors="[...(bookingDueDatesErrors['dueDates.' + index + '.amount'] || []), ...(bookingDueDatesErrors['dueDates.' + index + '.type'] || [])]" :required="true">
						<control-input v-model="dueDate.amount" :readonly="readonly" :class="{ 'is-danger': (bookingDueDatesErrors['dueDates.' + index + '.amount'] || []).length }" />
						<template v-slot:addon>
							<control-select
								v-model="dueDate.type"
								:options="dueDateTypeOptions"
								:class="{ 'is-danger': (bookingDueDatesErrors['dueDates.' + index + '.type'] || []).length }"
								:readonly="readonly"
							/>
						</template>
					</form-field>
				</form-panel>
			</form-panel>
			<control-button v-if="!readonly" @click="syncBookingDueDates" class="is-primary" :class="{ 'is-loading': isLoading === 'syncBookingDueDates' }">Save</control-button>
		</template>
		<template v-if="tabs.roomArrangements">
			<form-panel label="Room Arrangements" class="is-borderless">
				<template v-slot:action>
					<control-button v-if="!readonly" class="is-small is-link is-outlined" @click="roomArrangements.push({hotel: '', room: '', bed: '', dates: {start: '', end : ''}})">
						<i class="fas fa-plus"></i>
					</control-button>
				</template>
				<form-field :errors="roomArrangementsErrors['roomArrangements']">
					<input type="hidden" />
				</form-field>
				<form-panel v-for="(roomArrangement, index) in roomArrangements" :key="index">
					<template v-if="!readonly" v-slot:action>
						<control-button class="is-small is-link is-outlined" @click="roomArrangements.splice(index, 1)">
							<i class="fas fa-minus"></i>
						</control-button>
					</template>
					<form-field label="Hotel" :errors="roomArrangementsErrors['roomArrangements.' + index + '.hotel']" :required="true">
						<control-input 
							v-model="roomArrangement.hotel" 
							:class="{ 'is-danger': (roomArrangementsErrors['roomArrangements.' + index + '.hotel'] || []).length }" 
							:readonly="readonly" 
						/>
					</form-field>
					<form-field label="Room" :errors="roomArrangementsErrors['roomArrangements.' + index + '.room']" :required="true">
						<control-input 
							v-model="roomArrangement.room"
							:class="{ 'is-danger': (roomArrangementsErrors['roomArrangements.' + index + '.room'] || []).length }" 
							:readonly="readonly"
						/>
					</form-field>
					<form-field label="Bed Type" :errors="roomArrangementsErrors['roomArrangements.' + index + '.bed']" :required="true">
						<control-input 
							v-model="roomArrangement.bed" 
							:class="{ 'is-danger': (roomArrangementsErrors['roomArrangements.' + index + '.bed'] || []).length }" 
							:readonly="readonly"
						/>
					</form-field>
					<form-field label="Dates" :errors="[...(roomArrangementsErrors['roomArrangements.' + index + '.dates.start'] || []), ...(roomArrangementsErrors['roomArrangements.' + index + '.dates.end'] || [])]" :required="true">
						<date-picker 
							is-range
							v-model="roomArrangement.dates" 
							:min-date="$moment(savedBooking.checkIn).toDate()"
							:max-date="$moment(savedBooking.checkOut).toDate()"
							:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
						>
							<template v-slot="{ inputValue, inputEvents }">
								<input 
									:readonly="readonly"
									:value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
									v-on="inputEvents.start"
									:class="{
										'input': true,
										'is-danger': [...(roomArrangementsErrors['roomArrangements.' + index + '.dates.start'] || []), ...(roomArrangementsErrors['roomArrangements.' + index + '.dates.end'] || [])].length,
									}"
								/>
							</template>
						</date-picker>
					</form-field>
				</form-panel>
			</form-panel>
			<control-button v-if="!readonly" @click="updateRoomArrangements" class="is-primary" :class="{ 'is-loading': isLoading === 'updateRoomArrangements' }">Save</control-button>
			<control-button v-if="!readonly" @click="updateWithGuestAndBookingTravelDates" class="is-primary" :class="{ 'is-loading': isLoading === 'updateRoomArrangements' }">Save & Update Guest & Booking Travel Dates</control-button>
		</template>
		<template v-if="tabs.guests">
			<form-panel class="is-borderless">
				<template v-slot:action v-if="!readonly">
					<control-button class="is-small is-link is-outlined" @click="guests.push({ transportation: savedBooking.transportation })">
						<i class="fas fa-plus"></i>
					</control-button>
				</template>
				<form-panel v-for="(guest, index) in guests" :key="index" :label="'Guest ' + (index + 1)" :class="{ 'deleted': guest.deletedAt }">
					<template v-slot:action v-if="!readonly && (guests.length > 1)">
						<control-button class="is-small is-link is-outlined" @click="guest.id ? guest.deletedAt = true : guests.splice(index, 1)" v-if="!guest.deletedAt">
							<i class="fas fa-trash"></i>
						</control-button>
						<control-button class="is-small is-link is-outlined" @click="guest.deletedAt = false" v-if="guest.deletedAt">
							<i class="fas fa-trash-restore"></i>
						</control-button>
						<control-button class="is-small is-link is-outlined" @click="guests.splice(index, 1)" v-if="guest.deletedAt">
							<i class="fas fa-minus"></i>
						</control-button>
					</template>
					<div class="columns">
						<div class="column">
							<form-field label="First Name" :errors="guestsErrors['guests.' + index + '.firstName']" :required="true">
								<control-input
									v-model="guest.firstName"
									class="is-capitalized"
									:class="{ 'is-danger': (guestsErrors['guests.' + index + '.firstName'] || []).length, 'disabled':guest.deletedAt }"
									:readonly="readonly" 
									:disabled="guest.deletedAt ? true : false"
								/>
							</form-field>
              <p v-if="guestsErrors.duplicate_guests_in_request && guestsErrors.duplicate_guests_in_request.includes(index)" class="help is-danger">This guest is being duplicated.</p>
						</div>
						<div class="column">
							<form-field label="Last Name" :errors="guestsErrors['guests.' + index + '.lastName']" :required="true">
								<control-input 
									v-model="guest.lastName"
									class="is-capitalized"
									:class="{ 'is-danger': (guestsErrors['guests.' + index + '.lastName'] || []).length, 'disabled':guest.deletedAt }" 
									:readonly="readonly" 
									:disabled="guest.deletedAt ? true : false"
								/>
							</form-field>
						</div>
					</div>
					<div class="columns">
						<div class="column">
							<form-field label="Date of Birth" :errors="guestsErrors['guests.' + index + '.birthDate']" :required="true">
								<date-picker v-model="guest.birthDate" :max-date="savedBooking.checkIn" :popover="{ visibility: readonly ? 'hidden' : 'focus' }">
									<template v-slot="{ inputValue, inputEvents }">
										<input
											:readonly="readonly"
											:class="{ 
												'input': true, 
												'is-danger': (guestsErrors['guests.' + index + '.birthDate'] || []).length,
												'disabled':guest.deletedAt
											}"
											:value="inputValue"
											v-on="inputEvents" 
											:disabled="guest.deletedAt ? true : false"
										/>
									</template>
								</date-picker>
							</form-field>
						</div>
						<div class="column">
							<form-field label="Gender" :errors="guestsErrors['guests.' + index + '.gender']" :required="true">
								<control-radio
									v-model="guest.gender"
									:options="[{value: 'M', text: 'Male'}, {value: 'F', text: 'Female'}]"
									:class="{ 
										'is-danger': (guestsErrors['guests.' + index + '.gender'] || []).length,
										'disabled':guest.deletedAt
									}"
									:readonly="readonly" 
									:disabled="guest.deletedAt ? true : false"
								/>
							</form-field>
						</div>
					</div>
					<div class="columns">
						<div class="column">
							<form-field label="Travel Dates" :errors="[...(guestsErrors['guests.' + index + '.dates.start'] || []), ...(guestsErrors['guests.' + index + '.dates.end'] || [])]" :required="true">
								<div class="field has-addons">
									<div class="control is-expanded">
										<date-picker 
											v-model="guest.dates"
											is-range
											:popover="{ visibility: readonly ? 'hidden' : 'focus' }"
											:min-date="$moment(savedBooking.checkIn).toDate()"
											:max-date="$moment(savedBooking.checkOut).toDate()"
										>
											<template v-slot="{ inputValue, inputEvents }">
												<input
													:readonly="readonly" 
													:disabled="guest.deletedAt ? true : false"
													:value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
													v-on="inputEvents.start"
													:class="{
														'input': true,
														'is-danger': [...(guestsErrors['guests.' + index + '.dates.start'] || []), ...(guestsErrors['guests.' + index + '.dates.end'] || [])].length,
														'disabled':guest.deletedAt
													}"
												/>
											</template>
										</date-picker>
									</div>
									<div class="control">
										<button type="button" class="button is-primary" @click="applyDatesToAllGuests(guest.dates)" :disabled="readonly || guest.deletedAt">
											Apply to Booking & All Guests
										</button>
									</div>
								</div>
							</form-field>
						</div>
						<div class="column">
							<form-field label="Travel Insurance" :errors="guestsErrors['guests.' + index + '.insurance']">
								<control-radio
									v-model="guest.insurance"
									:options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}, {value: undefined, text: 'Pending'}]"
									:class="{ 
										'is-danger': (guestsErrors['guests.' + index + '.insurance'] || []).length,
										'disabled':guest.deletedAt
									}"
									:readonly="readonly" 
									:disabled="guest.deletedAt ? true : false"
								/>
							</form-field>
						</div>
					</div>
					<div class="columns" v-if="savedBooking.transportation">
						<div class="column">
							<form-field label="Transportation" :errors="guestsErrors['guests.' + index + '.transportation']" :required="true">
								<control-radio
									v-model="guest.transportation"
									:options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
									:class="{ 
										'is-danger': (guestsErrors['guests.' + index + '.transportation'] || []).length,
										'disabled':guest.deletedAt
									}" 
									:readonly="readonly"
									:disabled="guest.deletedAt ? true : false"
								/>
							</form-field>
						</div>
						<div class="column" v-if="guest.transportation">
							<form-field label="Custom Airport" :errors="guestsErrors['guests.' + index + '.customGroupAirport']">
								<control-select
									v-model="guest.customGroupAirport"
									:options="airports"
									:readonly="readonly"
									:disabled="guest.deletedAt ? true : false"
									:class="{ 'is-danger': (guestsErrors['guests.' + index + '.customGroupAirport'] || []).length }"
								/>
							</form-field>
						</div>
						<div class="column" v-if="guest.transportation">
							<form-field label="Transfer Type" :errors="guestsErrors['guests.' + index + '.transportationType']">
								<control-select
									v-model="guest.transportationType"
									:options="transportationTypes.map(transportationType => ({value: transportationType.id, text: transportationType.description}))"
									:class="{
										'is-danger': (guestsErrors['guests.' + index + '.transportationType'] || []).length,
										'disabled':guest.deletedAt 
									}"
									:readonly="readonly"
									:disabled="guest.deletedAt ? true : false"
								/>
							</form-field>
						</div>
						<div class="column" v-if="guest.transportation">
							<form-field label="Departure Pickup Time (Travel Documents)" :errors="guestsErrors['guests.' + index + '.departurePickupTime']">
								<div class="field has-addons">
									<div class="control is-expanded">
										<input
											type="time"
											class="input"
											v-model="guest.departurePickupTime"
											:readonly="readonly"
											:disabled="guest.deletedAt ? true : false"
											:class="{ 'is-danger': (guestsErrors['guests.' + index + '.departurePickupTime'] || []).length }"
										/>
									</div>
									<div class="control">
										<button type="button" class="button is-primary" @click="applyDeparturePickupTimeToAllGuests(guest.departurePickupTime)" :disabled="readonly || guest.deletedAt">
											Apply to All
										</button>
									</div>
								</div>
							</form-field>
						</div>
					</div>
					<form-field label="Invoiced To" :errors="guestsErrors['guests.' + index + '.client']" :required="true">
						<control-select
							v-model="guest.client"
							:options="savedBooking.clients.map(client => ({value: client.id, text: `${client.firstName} ${client.lastName}`}))"
							first-is-empty 
							:class="{ 
								'is-danger': (guestsErrors['guests.' + index + '.client'] || []).length,
								'disabled':guest.deletedAt 
							}" 
							:readonly="readonly" 
							:disabled="guest.deletedAt ? true : false"
						/>
					</form-field>
				</form-panel>
			</form-panel>
			<control-button v-if="!readonly" @click="checkGuestsBeforeUpdate" class="is-primary" :class="{ 'is-loading': isLoading === 'updateGuests' }">Save</control-button>
			<modal title="Transportation Warning" :is-active="showGuestTransportationWarning" @hide="cancelGuestTransportationWarning">
				<p>Please make sure that the transfer provider <span v-html="transferProviderName"></span> has been notified about the guest cancellation.</p>
				<template v-slot:footer>
					<div class="field is-grouped">
						<control-button @click="cancelGuestTransportationWarning">Cancel</control-button>
						<control-button @click="proceedWithGuestUpdate" type="submit" class="is-primary">Continue</control-button>
					</div>
				</template>
			</modal>
			<modal title="Warning!" :is-active="showGuestWarnings">
				<ul style="list-style: inside disc;">
					<li v-for="(warning, index) in guestWarnings" :key="index">{{ warning }}</li>
				</ul>
				<template v-slot:footer>
					<div class="field is-grouped">
						<control-button @click="cancelGuestUpdates">Cancel Updates</control-button>
						<control-button @click="ignoreGuestWarningsAndUpdate" type="submit" class="is-primary">Ignore & Update</control-button>
					</div>
				</template>
			</modal>
		</template>
		<template v-if="tabs.flightManifests">
			<form-panel class="is-borderless">
				<form-panel v-for="(flightManifest, index) in flightManifests" :key="flightManifest.guestId" :label="flightManifest.guestName">
					<template v-if="!readonly" v-slot:action>
						<control-button v-if="flightManifest.set" class="is-small is-link is-outlined" @click="unsetFlightManifest(flightManifest)">
							<i class="fas fa-minus"></i><span style="margin-left: 0.5rem">Unset</span>
						</control-button>
						<control-button v-else class="is-small is-link is-outlined" @click="setFlightManifest(flightManifest)">
							<i class="fas fa-plus"></i><span style="margin-left: 0.5rem">Set</span>
						</control-button>
					</template>
					<div v-if="flightManifest.set">
						<div><b class="is-required">Phone</b></div>
						<div class="columns">
							<div class="column">
								<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.phoneNumber']">
									<control-input
										:readonly="readonly"
										v-model="flightManifest.phoneNumber"
										placeholder="Phone"
										:class="{ 'is-danger': (flightManifestsErrors['flightManifests.' + index + '.phoneNumber'] || []).length }"
									/>
								</form-field>
							</div>
						</div>
						<div v-if="flightManifest.transportationType == 1 || flightManifest.transportationType == 2">
							<div><b class="is-required">Arrival</b></div>
							<div class="columns">
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.arrivalDepartureAirportIata']">
										<control-input
											v-model="flightManifest.arrivalDepartureAirportIata"
											:readonly="readonly"
											placeholder="Departure Airport"
											:class="{ 'is-danger': (flightManifestsErrors['flightManifests.' + index + '.arrivalDepartureAirportIata'] || []).length }"
										/>
									</form-field>
								</div>
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.arrivalDepartureDate']">
										<date-picker
											v-model="flightManifest.arrivalDepartureDate"
											:min-date="$moment(savedBooking.checkIn).toDate()"
											:max-date="$moment(savedBooking.checkOut).toDate()"
											mode="date"
											:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
										>
											<template v-slot="{ inputValue, inputEvents }">
												<input
													:readonly="readonly"
													placeholder="Departure Date"
													:class="'input' + ((flightManifestsErrors['flightManifests.' + index + '.arrivalDepartureDate'] || []).length ? ' is-danger' : '')"
													:value="inputValue"
													v-on="inputEvents"
												/>
											</template>
										</date-picker>
									</form-field>
								</div>
							</div>
							<div class="columns">
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.arrivalAirport']">
										<control-select 
											v-model="flightManifest.arrivalAirport"
											:options="[{value: '', text: 'Arrival Airport', disabled: true}, ...airports]"
											default-value=""
											:readonly="readonly"
											:class="{ 'is-danger': (flightManifestsErrors['flightManifests.' + index + '.arrivalAirport'] || []).length }" 
										/>
									</form-field>
								</div>
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.arrivalAirline']">
										<control-select 
											v-model="flightManifest.arrivalAirline"
											:options="[{value: '', text: 'Arrival Airline', disabled: true}, ...airlines]"
											default-value=""
											:readonly="readonly"
											:class="{ 'is-danger': (flightManifestsErrors['flightManifests.' + index + '.arrivalAirline'] || []).length }" 
										/>
									</form-field>
								</div>
							</div>
							<div class="columns">
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.arrivalNumber']">
										<control-input
											v-model="flightManifest.arrivalNumber"
											placeholder="Arrival Flight Number"
											:readonly="readonly"
											:class="{ 'is-danger': (flightManifestsErrors['flightManifests.' + index + '.arrivalNumber'] || []).length }"
										/>
									</form-field>
								</div>
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.arrivalDateTime']">
										<date-picker
											v-model="flightManifest.arrivalDateTime"
											:min-date="$moment(savedBooking.checkIn).toDate()"
											:max-date="$moment(savedBooking.checkOut).toDate()"
											mode="dateTime"
											is24hr
											@input="updateDate(flightManifest, 'arrivalDateTime')"
											:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
										>
											<template v-slot="{ inputValue, inputEvents }">
												<input
													:readonly="readonly"
													placeholder="Arrival Date & Time"
													:class="'input' + ((flightManifestsErrors['flightManifests.' + index + '.arrivalDateTime'] || []).length ? ' is-danger' : '')"
													:value="inputValue"
													v-on="inputEvents"
												/>
											</template>
										</date-picker>
									</form-field>
								</div>
							</div>
						</div>
						<div v-if="flightManifest.transportationType == 1 || flightManifest.transportationType == 3">
							<div><b class="is-required">Departure</b></div>
							<div class="columns">
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.departureAirport']">
										<control-select 
											v-model="flightManifest.departureAirport"
											:options="[{value: '', text: 'Departure Airport', disabled: true}, ...airports]"
											default-value=""
											:readonly="readonly"
											:class="{ 'is-danger': (flightManifestsErrors['flightManifests.' + index + '.departureAirport'] || []).length }" 
										/>
									</form-field>
								</div>
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.departureDate']">
										<date-picker
											v-model="flightManifest.departureDate"
											:min-date="$moment(savedBooking.checkIn).toDate()"
											:max-date="$moment(savedBooking.checkOut).toDate()"
											mode="date"
											:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
										>
											<template v-slot="{ inputValue, inputEvents }">
												<input
													placeholder="Departure Date"
													:class="'input' + ((flightManifestsErrors['flightManifests.' + index + '.departureDate'] || []).length ? ' is-danger' : '')"
													:value="inputValue"
													v-on="inputEvents"
													:readonly="readonly"
												/>
											</template>
										</date-picker>
									</form-field>
								</div>
							</div>
							<div class="columns">
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.departureAirline']">
										<control-select 
											v-model="flightManifest.departureAirline"
											:options="[{value: '', text: 'Arrival Airline', disabled: true}, ...airlines]"
											default-value=""
											:readonly="readonly"
											:class="{ 'is-danger': (flightManifestsErrors['flightManifests.' + index + '.departureAirline'] || []).length }" 
										/>
									</form-field>
								</div>
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.departureNumber']">
										<control-input
											v-model="flightManifest.departureNumber"
											placeholder="Departure Flight Number"
											:readonly="readonly"
											:class="{ 'is-danger': (flightManifestsErrors['flightManifests.' + index + '.departureNumber'] || []).length }"
										/>
									</form-field>
								</div>
							</div>
							<div class="columns">
								<div class="column">
									<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.departureDateTime']">
										<date-picker
											v-model="flightManifest.departureDateTime"
											:min-date="$moment(savedBooking.checkIn).toDate()"
											:max-date="$moment(savedBooking.checkOut).toDate()"
											mode="dateTime"
											is24hr
											@input="updateDate(flightManifest, 'departureDateTime')"
											:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
										>
											<template v-slot="{ inputValue, inputEvents }">
												<input
													placeholder="Departure Date & Time"
													:readonly="readonly"
													:class="'input' + ((flightManifestsErrors['flightManifests.' + index + '.departureDateTime'] || []).length ? ' is-danger' : '')"
													:value="inputValue"
													v-on="inputEvents"
												/>
											</template>
										</date-picker>
									</form-field>
								</div>
								<div class="column"></div>
							</div>
						</div>
						<div v-if="index != flightManifests.length - 1" class="form-seperator"></div>
					</div>
				</form-panel>
			</form-panel>
			<control-button v-if="!readonly" @click="updateFlightManifests" class="is-primary" :class="{ 'is-loading': isLoading === 'updateFlightManifests' }">Save</control-button>
		</template>
		<template v-if="tabs.flightDetails">
			<form-panel v-for="(item , index) in flightDetailsData" :key="index">
				<h1 class="title is-2" style="margin-bottom: 15px;"><b>{{ item.guest_name }}</b></h1>
				<div v-if="item.arrival" style="margin-bottom: 20px;">
					<p class="subtitle is-4 has-text-weight-bold" style="margin-bottom: 10px; font-size: medium">Arrival</p>
					<div class="columns">
						<div class="column sub-label">
							<form-field label="Flight">
								<control-input v-model="item.arrival.flight_iata" placeholder="Flight" disabled />
							</form-field>
						</div>
						<div class="column sub-label">
							<form-field label="Airport">
								<control-input v-model="item.arrival.airport_iata" placeholder="Airport" disabled />
							</form-field>
						</div>
					</div>
					<div class="columns">
						<div class="column sub-label">
							<form-field label="Scheduled Arrival">
								<control-input v-model="item.arrival.scheduled_arrival" placeholder="Scheduled Arrival" disabled />
							</form-field>
						</div>
					</div>
				</div>
				<div v-if="item.departure">
					<p class="subtitle is-4 has-text-weight-bold" style="margin-bottom: 10px; font-size: medium">Departure</p>
					<div class="columns">
						<div class="column sub-label">
							<form-field label="Flight">
								<control-input v-model="item.departure.flight_iata" placeholder="Flight" disabled />
							</form-field>
						</div>
						<div class="column sub-label">
							<form-field label="Airport">
								<control-input v-model="item.departure.airport_iata" placeholder="Airport" disabled />
							</form-field>
						</div>
					</div>
					<div class="columns">
						<div class="column sub-label">
							<form-field label="Scheduled Departure">
								<control-input v-model="item.departure.scheduled_departure" placeholder="Scheduled Departure" disabled />
							</form-field>
						</div>
					</div>
				</div>
			</form-panel>
		</template>
		<template v-if="tabs.paymentArrangements">
			<form-panel label="Payment Arrangements" class="is-borderless">
				<template v-slot:action>
					<control-button v-if="!readonly" class="is-small is-link is-outlined" @click="paymentArrangements.push({dueDate: '', amount: '', bookingClientId: null})">
						<i class="fas fa-plus"></i>
					</control-button>
				</template>
				<form-panel v-for="(paymentArrangement, index) in paymentArrangements" :key="index">
					<template v-if="!readonly" v-slot:action>
						<control-button class="is-small is-link is-outlined" @click="paymentArrangements.splice(index, 1)">
							<i class="fas fa-minus"></i>
						</control-button>
					</template>
					<form-field label="Due Date" :errors="paymentArrangementsErrors['paymentArrangements.' + index + '.dueDate'] || []" :required="true">
						<date-picker
							v-model="paymentArrangement.dueDate"
							:max-date="savedBooking.checkIn"
							:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
						>
							<template v-slot="{ inputValue, inputEvents }">
								<input
									:class="'input' + ((paymentArrangementsErrors['paymentArrangements.' + index + '.dueDate'] || []).length ? ' is-danger' : '')"
									:value="inputValue"
									v-on="inputEvents"
									:readonly="readonly"
								/>
							</template>
						</date-picker>
					</form-field>
					<form-field label="Amount" :errors="paymentArrangementsErrors['paymentArrangements.' + index + '.amount'] || []" :required="true">
						<control-input v-model="paymentArrangement.amount" :class="{ 'is-danger': (paymentArrangementsErrors['paymentArrangements.' + index + '.amount'] || []).length }" :readonly="readonly" />
					</form-field>
					<form-field label="Client" :errors="paymentArrangementsErrors['paymentArrangements.' + index + '.bookingClientId'] || []" :required="true">
						<control-select
							v-model="paymentArrangement.bookingClientId"
							:options="savedBooking.clients.map(client => ({value: client.id, text: `${client.firstName} ${client.lastName}`}))"
							first-is-empty
							:class="{ 'is-danger': (paymentArrangementsErrors['paymentArrangements.' + index + '.bookingClientId'] || []).length }"
							:readonly="readonly"
						/>
					</form-field>
				</form-panel>
			</form-panel>
			<control-button v-if="!readonly" @click="updatePaymentArrangements" class="is-primary" :class="{ 'is-loading': isLoading === 'updatePaymentArrangements' }">Save</control-button>
		</template>
		<template v-if="tabs.termsConditions">
			<form-field label="Terms & Conditions" :errors="bookingErrors.termsAndConditions">
				<control-editor v-model="booking.termsAndConditions" :class="{ 'is-danger': (bookingErrors.termsAndConditions || []).length }" :readonly="readonly" />
			</form-field>
			<control-button v-if="!readonly" @click="updateTermsConditions" class="is-primary" :class="{ 'is-loading': isLoading === 'updateTermsConditions' }">Save</control-button>
		</template>
	</card>
</template>

<script>
	import Card from '@dashboard/components/Card';
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlRadio from '@dashboard/components/form/controls/Radio';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';
	import DatePicker from 'v-calendar/lib/components/date-picker.umd';
	import ConfirmBooking from '@dashboard/pages/IndividualBookings/Confirm';
	import DeleteBooking from '@dashboard/pages/IndividualBookings/Delete';
	import RestoreBooking from '@dashboard/pages/IndividualBookings/Restore';
	import ForceDeleteBooking from '@dashboard/pages/IndividualBookings/ForceDelete';
	import FormField from '@dashboard/components/form/Field';
	import FormPanel from '@dashboard/components/form/Panel';
	import SendFitQuote from '@dashboard/pages/IndividualBookings/FitQuote/Send';
	import SendInvoice from '@dashboard/pages/IndividualBookings/Invoice/Send';
	import SendTravelDocuments from '@dashboard/pages/IndividualBookings/TravelDocuments/Send';
	import Tab from '@dashboard/components/tabs/Tab';
	import Tabs from '@dashboard/components/tabs/Tabs';
	import Modal from '@dashboard/components/Modal';
	import ControlEditor from '@dashboard/components/form/controls/QuillEditor';
	import ImageUploader from '@dashboard/components/file/ImageUploader';

	export default {
		components: {
			Card,
			ControlButton,
			ControlInput,
			ControlRadio,
			ControlSelect,
			ControlTextarea,
			DatePicker,
			ConfirmBooking,
			DeleteBooking,
			RestoreBooking,
			ForceDeleteBooking,
			FormField,
			FormPanel,
			SendFitQuote,
			SendInvoice,
			SendTravelDocuments,
			Tab,
			Tabs,
			Modal,
			ControlEditor,
			ImageUploader,
		},
		data() {
			return {
				savedBooking: null,
				booking: {},
				bookingErrors: {},
				bookingDueDates: [],
				bookingDueDatesErrors: [],
				roomArrangements: [],
				roomArrangementsErrors: {},
				guests: [],
				guestsErrors: {},
				showGuestWarnings: false,
				ignoreGuestWarnings: false,
				guestWarnings: [],
				showGuestTransportationWarning: false,
				flightManifests: [],
				flightManifestsErrors: {},
				hasFlightDetails: false,
				flightDetailsData:[],
				paymentArrangements: [],
				paymentArrangementsErrors: {},
				airports: [],
				previousBooking: null,
				nextBooking: null,
				transportationTypes: [],
				airlines: [],
				transfers: [],
				destinations: [],
				agents: [],
				providers: [],
				showConfirm: false,
				showDelete: false,
				showRestore: false,
				showForceDelete: false,
				showSendFitQuote: false,
				showSendInvoice: false,
				showSendTravelDocuments: false,
				tabs: {
					info: true,
					bookingDueDates: false,
					roomArrangements: false,
					guests: false,
					flightManifests: false,
					flightDetails: false,
					paymentArrangements: false,
					termsConditions: false,
				},
				isLoading: '',
				history: [],
				historyIndex: -1,
			}
		},
		async created() {
			await this.fetchData();
			await this.fetchFlightDetails();

			const state = JSON.stringify({
				booking: this.booking,
				guests: this.guests
			});

			this.history = this.history.slice(0, this.historyIndex + 1);
			this.history.push(state);
			this.historyIndex++;
		},
		computed: {
			readonly() {
				return !this.savedBooking.can.update;
			},
			canUndo() {
				return this.historyIndex > 0;
			},
			canRedo() {
				return this.historyIndex < this.history.length - 1;
			},
			destination: {
				get() {
					return this.booking.destination;
				},
				set(destination) {
					this.booking.destination = destination;
				}
			},
			dueDateTypeOptions() {
				return [
					{ value: 'price', text: '$' },
					{ value: 'percentage', text: '%' },
				];
			},
			transferProviderName() {
				if (!this.savedBooking.transfer) return '';
				return `<b style="text-transform: lowercase;">${this.savedBooking.transfer.name}</b>`;
			}
		},
		methods: {
			async fetchData() {
				await this.$http.get('/individual-bookings/' + this.$route.params.id)
					.then(response => {
						this.savedBooking = response.data.data;

						this.booking = {
							hotelAssistance: this.savedBooking.hotelAssistance ? true : false,
							hotelPreferences: this.savedBooking.hotelPreferences,
							hotelName: this.savedBooking.hotelName,
							roomCategory: this.savedBooking.roomCategory ? true : false,
							roomCategoryName: this.savedBooking.roomCategoryName,
							dates: {
								start: this.$moment(this.savedBooking.checkIn).toDate(),
								end: this.$moment(this.savedBooking.checkOut).toDate()
							},
							budget: this.savedBooking.budget,
							specialRequests: this.savedBooking.specialRequests,
							notes: this.savedBooking.notes,
							transportation: this.savedBooking.transportation ? true : false,
							departureGateway: this.savedBooking.departureGateway,
							flightPreferences: this.savedBooking.flightPreferences,
							airlineMembershipNumber: this.savedBooking.airlineMembershipNumber,
							knownTravelerNumber: this.savedBooking.knownTravelerNumber,
							flightMessage: this.savedBooking.flightMessage,
							transportationType: this.savedBooking.transportationType,
							transportationSubmitBefore: this.savedBooking.transportationSubmitBefore === null ? null : this.$moment(this.savedBooking.transportationSubmitBefore).toDate(),
							transfer: this.savedBooking.transfer ? this.savedBooking.transfer.id : null,
							destination: this.savedBooking.destination? this.savedBooking.destination.id : null,
							email: this.savedBooking.email,
							reservationLeaderFirstName: this.savedBooking.reservationLeaderFirstName,
							reservationLeaderLastName: this.savedBooking.reservationLeaderLastName,
							deposit: this.savedBooking.deposit,
							depositType: this.savedBooking.depositType,
							agent: this.savedBooking.agent? this.savedBooking.agent.id : null,
							provider: this.savedBooking.provider ? this.savedBooking.provider.id : null,
							providerId: this.savedBooking.providerId,
							changeFeeDate: this.savedBooking.changeFeeDate === null ? null : this.$moment(this.savedBooking.changeFeeDate).toDate(),
							changeFeeAmount: this.savedBooking.changeFeeAmount,
							staffMessage: this.savedBooking.staffMessage,
							termsAndConditions: this.savedBooking.termsAndConditions,
							travelDocsCoverImage: this.savedBooking.travelDocsCoverImage,
							travelDocsImageTwo: this.savedBooking.travelDocsImageTwo,
							travelDocsImageThree: this.savedBooking.travelDocsImageThree,
							bookingId: this.savedBooking.bookingId,
						};

						this.bookingDueDates = {
							balanceDueDate: this.savedBooking.balanceDueDate ? this.$moment(this.savedBooking.balanceDueDate).toDate() : null,
							cancellationDate: this.savedBooking.cancellationDate ? this.$moment(this.savedBooking.cancellationDate).toDate() : null,

							dueDates: this.savedBooking.bookingDueDates.map(dueDate => ({
								date: dueDate.date ? this.$moment(dueDate.date).toDate() : null,
								amount: dueDate.amount,
								type: dueDate.type
							})),
						};

						this.roomArrangements = this.savedBooking.roomArrangements.map(roomArrangement => {
							return {
								hotel: roomArrangement.hotel,
								room: roomArrangement.room,
								bed: roomArrangement.bed,
								dates: {
									start: this.$moment(roomArrangement.checkIn).toDate(),
									end: this.$moment(roomArrangement.checkOut).toDate(),
								},
							};
						});

						this.guests = [];
						this.flightManifests = [];

						this.clients = this.savedBooking.clients.map(client => {
							this.guests = this.guests.concat(client.guests.map(guest => {
								if (guest.transportation && !guest.deleted_at) {
									let flightManifest = {
										guestId: guest.id,
										transportationType: guest.transportation_type,
										guestName: guest.firstName + ' ' + guest.lastName,
										set: guest.flightManifest != null
									};

									if (flightManifest.set) {
										flightManifest.phoneNumber = guest.flightManifest.phoneNumber;

										if (flightManifest.transportationType == 1 || flightManifest.transportationType == 2) {
											flightManifest.arrivalDepartureAirportIata = guest.flightManifest.arrivalDepartureAirportIata;
											flightManifest.arrivalDepartureAirportTimezone = guest.flightManifest.arrivalDepartureAirportTimezone;
											flightManifest.arrivalDepartureDate = this.$moment(guest.flightManifest.arrivalDepartureDate).toDate();
											flightManifest.arrivalAirport = guest.flightManifest.arrivalAirportId;
											flightManifest.arrivalAirline = guest.flightManifest.arrivalAirline;
											flightManifest.arrivalNumber = guest.flightManifest.arrivalNumber;
											flightManifest.arrivalDateTime = guest.flightManifest.arrivalDateTime;
										}

										if (flightManifest.transportationType == 1 || flightManifest.transportationType == 3) {
											flightManifest.departureAirport = guest.flightManifest.departureAirportId;
											flightManifest.departureDate = this.$moment(guest.flightManifest.departureDate).toDate();
											flightManifest.departureAirline = guest.flightManifest.departureAirline;
											flightManifest.departureNumber = guest.flightManifest.departureNumber;
											flightManifest.departureDateTime = guest.flightManifest.departureDateTime;
										}
									}

									this.flightManifests.push(flightManifest);
								}

								return {
									id: guest.id,
									firstName: guest.firstName,
									lastName: guest.lastName,
									gender: guest.gender,
									birthDate: this.$moment(guest.birthDate).toDate(),
									dates: {
										start: this.$moment(guest.checkIn).toDate(),
										end: this.$moment(guest.checkOut).toDate()
									},
									client: client.id,
									insurance: guest.insurance,
									transportation: Boolean(guest.transportation),
									transportationType: guest.transportation_type,
									deletedAt: guest.deleted_at,
									customGroupAirport: guest.customGroupAirport,
									departurePickupTime: guest.departurePickupTime,
								};
							}));

							return {
								id: client.id,
								firstName: client.firstName,
								lastName: client.lastName,
								email: client.client.email,
								phone: client.phone,
							};
						});

						this.guests.sort((previousGuest, guest) => (previousGuest.id - guest.id));
						this.flightManifests.sort((previousFlightManifest, flightManifest) => (previousFlightManifest.guestId - flightManifest.guestId));

						this.paymentArrangements = this.savedBooking.paymentArrangements.map(arrangement => ({
							...arrangement,
							dueDate: this.$moment(arrangement.dueDate).toDate()
						}));

						this.airports = this.savedBooking.destination ? this.savedBooking.destination.airports.map(airport => ({ value: airport.id, text: airport.airport_code })) : response.data.airports.map(airport => ({ value: airport.id, text: airport.airport_code }));
						this.previousBooking = response.data.previousBooking;
						this.nextBooking = response.data.nextBooking;
						this.transportationTypes = response.data.transportationTypes;

						this.airlines = response.data.airlines.map(airline => ({
							value: airline.iata_code,
							text: airline.name
						}));

						this.transfers = response.data.transfers.map(transfer => ({
							value: transfer.id,
							text: transfer.name,
						}));

						this.destinations = response.data.destinations;
						this.agents = response.data.agents;
						this.providers = response.data.providers;

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
				this.$store.commit('breadcrumbs', [
					{
						label: 'Dashboard',
						route: 'home'
					},
					{
						label: 'Individual Bookings',
						route: 'individual-bookings'
					},
					{
						label: '#' + this.savedBooking.order + ' ' + this.savedBooking.reservationLeaderFirstName + ' ' + this.savedBooking.reservationLeaderLastName,
						route: 'individual-bookings.show',
						params: {
							id: this.savedBooking.id
						}
					}
				]);
			},
			setTab(tab) {
				Object.keys(this.tabs).forEach(key => this.tabs[key] = false);
				this.tabs[tab] = true;
			},
			applyDatesToAllGuests(dates) {
				this.$http.patch(`/individual-bookings/${this.$route.params.id}/update-travel-dates`, {
					start: dates.start instanceof Date ? dates.start.toDateString() : dates.start,
					end: dates.end instanceof Date ? dates.end.toDateString() : dates.end
				}).then(() => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'Travel dates have been updated for all guests.'
					});

					this.fetchData();
				});
			},
			update() {
				this.isLoading = 'update';
				this.booking.dates.start = this.booking.dates.start instanceof Date ? this.booking.dates.start.toDateString() : this.booking.dates.start;
				this.booking.dates.end = this.booking.dates.end instanceof Date ? this.booking.dates.end.toDateString() : this.booking.dates.end;
				this.booking.transportationSubmitBefore = this.booking.transportationSubmitBefore instanceof Date ? this.booking.transportationSubmitBefore.toDateString() : this.booking.transportationSubmitBefore;
				this.booking.changeFeeDate = this.booking.changeFeeDate instanceof Date ? this.booking.changeFeeDate.toDateString() : this.booking.changeFeeDate;

				let request = this.$http.put('/individual-bookings/' + this.$route.params.id, this.booking)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The booking has been updated.'
						});

						this.savedBooking = {
							...this.savedBooking,
							...response.data.data,
						};
						
						this.airports = this.savedBooking.destination ? this.savedBooking.destination.airports.map(airport => ({ value: airport.id, text: airport.airport_code })) : this.airports;
						this.bookingErrors = {};

						this.saveState();
					}).catch(error => {
						if (error.response.status === 422) {
							this.bookingErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			syncBookingDueDates() {
				this.isLoading = 'syncBookingDueDates';

				let payload = {
					...this.bookingDueDates,
					balanceDueDate: this.bookingDueDates.balanceDueDate instanceof Date ? this.bookingDueDates.balanceDueDate.toDateString() : this.bookingDueDates.balanceDueDate,
					cancellationDate: this.bookingDueDates.cancellationDate instanceof Date ? this.bookingDueDates.cancellationDate.toDateString() : this.bookingDueDates.cancellationDate,
					dueDates: this.bookingDueDates.dueDates.map(dueDate => ({
						...dueDate,
						date: dueDate.date instanceof Date ? dueDate.date.toDateString() : dueDate.date
					}))
				};

				let request = this.$http.patch('/individual-bookings/' + this.$route.params.id + '/booking-due-dates', payload)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The due dates have been updated.'
						});

						this.bookingDueDatesErrors = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.bookingDueDatesErrors = error.response.data.errors;
						}
					});
	
				request.then(() => {
					this.isLoading = '';
				});
			},
			updateRoomArrangements() {
				this.isLoading = 'updateRoomArrangements';

				this.roomArrangements.forEach(roomArrangement => {
					if (roomArrangement.dates instanceof Object) {
						roomArrangement.dates.start = roomArrangement.dates.start instanceof Date ? roomArrangement.dates.start.toDateString() : roomArrangement.dates.start;
						roomArrangement.dates.end = roomArrangement.dates.end instanceof Date ? roomArrangement.dates.end.toDateString() : roomArrangement.dates.end;
					}
				});

				return this.$http.put('/individual-bookings/' + this.$route.params.id + '/update-room-arrangements', {'roomArrangements': this.roomArrangements})
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The room arrangements have been updated.'
						});

						this.roomArrangementsErrors = {};
					}).catch(error => {
						if (error.response.status === 422) {
							this.roomArrangementsErrors = error.response.data.errors;
						}

						throw error;
					}).finally(() => {
						this.isLoading = '';
					});
			},
			async updateWithGuestAndBookingTravelDates() {
				await this.updateRoomArrangements();

				const allDates = this.roomArrangements.flatMap(arr => [arr?.dates?.start, arr?.dates?.end]).filter(date => date);
				allDates.sort((a, b) => new Date(a) - new Date(b));

				const dates = {
					start: allDates[0] || null,
					end: allDates[allDates.length - 1] || null
				};

				this.applyDatesToAllGuests(dates);
			},
			applyDeparturePickupTimeToAllGuests(departurePickupTime) {
				this.$http.patch(`/individual-bookings/${this.$route.params.id}/update-departure-pickup-time`, {
					departurePickupTime: departurePickupTime
				}).then(() => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'Departure pickup time has been updated for all guests.'
					});

					this.fetchData();
				});
			},
      checkGuestsBeforeUpdate() {
        const checkInDate = this.$moment(this.savedBooking.checkIn);
        const daysUntilCheckIn = checkInDate.diff(this.$moment(), 'days');

        if (daysUntilCheckIn <= 30 && daysUntilCheckIn >= 0 && this.savedBooking.transportation) {
          const originalActiveGuestsWithTransportation = [];

          this.savedBooking.clients.forEach(client => {
            client.guests.forEach(guest => {
              if (guest.transportation && !guest.deleted_at) {
                originalActiveGuestsWithTransportation.push(guest.id);
              }
            });
          });

          const hasNewlySoftDeletedGuestsWithTransportation = this.guests.some(guest => {
            return guest.deletedAt && guest.transportation && originalActiveGuestsWithTransportation.includes(guest.id);
          });

          const currentGuestIds = this.guests.map(g => g.id);

          const hasNewlyHardDeletedGuestsWithTransportation = originalActiveGuestsWithTransportation.some(id => {
            return !currentGuestIds.includes(id);
          });

          if (hasNewlySoftDeletedGuestsWithTransportation || hasNewlyHardDeletedGuestsWithTransportation) {
            this.showGuestTransportationWarning = true;

            return;
          }
        }
        this.updateGuests();
      },
			cancelGuestTransportationWarning() {
				this.showGuestTransportationWarning = false;
			},
			proceedWithGuestUpdate() {
				this.showGuestTransportationWarning = false;
				this.updateGuests();
			},
			updateGuests() {
				this.isLoading = 'updateGuests';
				this.flightManifests = [];

				this.guests.map(guest => {
					if (guest.dates instanceof Object) {
						guest.dates.start = guest.dates.start instanceof Date ? guest.dates.start.toDateString() : (typeof guest.dates.start === 'string' ? guest.dates.start : null);
						guest.dates.end = guest.dates.end instanceof Date ? guest.dates.end.toDateString() : (typeof guest.dates.end === 'string' ? guest.dates.end : null);
					}

					guest.birthDate = guest.birthDate instanceof Date ? guest.birthDate.toDateString() : (typeof guest.birthDate === 'string' ? guest.birthDate : null);

					return guest;
				})

				let request = this.$http.patch('/individual-bookings/' + this.$route.params.id + '/guests', {
					guests: this.guests,
					ignoreGuestWarnings: this.ignoreGuestWarnings,
				}).then(response => {
					if (response.data.warnings && response.data.warnings.length) {
						this.showGuestWarnings = true;
						this.guestWarnings = response.data.warnings;
					} else {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The guests have been updated.'
						});

						this.guestsErrors = [];

						this.savedBooking.clients = this.savedBooking.clients.map(client => ({
							...client,
							guests: response.data.data.filter(guest => {
								return guest.clientId == client.id;
							})
						}));

						this.guests = response.data.data.map(guest => {
							if (guest.transportation && !guest.deleted_at) {
								let flightManifest = {
									guestId: guest.id,
									transportationType: guest.transportation_type,
									guestName: guest.firstName + ' ' + guest.lastName,
									set: guest.flightManifest != null
								};
								
								if (flightManifest.set) {
									flightManifest.phoneNumber = guest.flightManifest.phoneNumber;

									if (flightManifest.transportationType == 1 || flightManifest.transportationType == 2) {
										flightManifest.arrivalDepartureAirportIata = guest.flightManifest.arrivalDepartureAirportIata;
										flightManifest.arrivalDepartureAirportTimezone = guest.flightManifest.arrivalDepartureAirportTimezone;
										flightManifest.arrivalDepartureDate = this.$moment(guest.flightManifest.arrivalDepartureDate).toDate();
										flightManifest.arrivalAirport = guest.flightManifest.arrivalAirportId;
										flightManifest.arrivalAirline = guest.flightManifest.arrivalAirline;
										flightManifest.arrivalNumber = guest.flightManifest.arrivalNumber;
										flightManifest.arrivalDateTime = guest.flightManifest.arrivalDateTime;
									}

									if (flightManifest.transportationType == 1 || flightManifest.transportationType == 3) {
										flightManifest.departureAirport = guest.flightManifest.departureAirportId;
										flightManifest.departureDate = this.$moment(guest.flightManifest.departureDate).toDate();
										flightManifest.departureAirline = guest.flightManifest.departureAirline;
										flightManifest.departureNumber = guest.flightManifest.departureNumber;
										flightManifest.departureDateTime = guest.flightManifest.departureDateTime;
									}
								}

								this.flightManifests.push(flightManifest);
							}

							return {
								id: guest.id,
								firstName: guest.firstName,
								lastName: guest.lastName,
								gender: guest.gender,
								birthDate: this.$moment(guest.birthDate).toDate(),
								dates: {
									start: this.$moment(guest.checkIn).toDate(),
									end: this.$moment(guest.checkOut).toDate()
								},
								client: guest.clientId,
								insurance: guest.insurance,
								transportation: Boolean(guest.transportation),
								transportationType: guest.transportation_type,
								deletedAt: guest.deleted_at,
								customGroupAirport: guest.customGroupAirport,
								departurePickupTime: guest.departurePickupTime,
							}
						});

						this.fetchFlightDetails();
						this.saveState();
					}
				}).catch(error => {
					if (error.response.status === 422) {
						this.guestsErrors = error.response.data.errors;
					}
				});

				request.then(() => {
					this.ignoreGuestWarnings = false;
					this.isLoading = '';
				});
			},
			cancelGuestUpdates() {
				this.showGuestWarnings = false;
				this.ignoreGuestWarnings = false;
				this.guestWarnings = [];
			},
			ignoreGuestWarningsAndUpdate() {
				this.showGuestWarnings = false;
				this.ignoreGuestWarnings = true;
				this.guestWarnings = [];

				this.updateGuests();
			},
			setFlightManifest(flightManifest) {
				let guest = this.guests.find(guest => (guest.id === flightManifest.guestId));
				flightManifest.arrivalDateTime = flightManifest.arrivalDateTime ?? this.$moment(guest.dates.start).format('YYYY-MM-DD HH:mm');
				flightManifest.departureDateTime = flightManifest.departureDateTime ?? this.$moment(guest.dates.end).format('YYYY-MM-DD HH:mm');
				flightManifest.set = true;
			},
			unsetFlightManifest(flightManifest) {
				flightManifest.set = false;
			},
			updateDate(flightManifest, type) {
				const manifest = this.flightManifests.find(manifest => manifest.guestId === flightManifest.guestId);
				
				if (manifest) {
					if (type === 'arrivalDateTime') {
						manifest.arrivalDateTime = this.$moment(flightManifest.arrivalDateTime).format('YYYY-MM-DD HH:mm');
					} else if (type === 'departureDateTime') {
						manifest.departureDateTime = this.$moment(flightManifest.departureDateTime).format('YYYY-MM-DD HH:mm');
					}
				}
			},
			updateFlightManifests() {
				this.isLoading = 'updateFlightManifests';

				let payload = this.flightManifests.map(manifest => ({
					...manifest,
					arrivalDepartureDate: manifest.arrivalDepartureDate instanceof Date ? manifest.arrivalDepartureDate.toDateString() : manifest.arrivalDepartureDate,
					departureDate: manifest.departureDate instanceof Date ? manifest.departureDate.toDateString() : manifest.departureDate
				}));

				let request = this.$http.patch('/individual-bookings/' + this.$route.params.id + '/flight-manifests', {
						flightManifests: payload
					}).then(response => {
						this.flightManifests = [];

						response.data.data.forEach(guest => {
							if (guest.transportation && !guest.deleted_at) {
								let flightManifest = {
									guestId: guest.id,
									transportationType: guest.transportation_type,
									guestName: guest.firstName + ' ' + guest.lastName,
									set: guest.flightManifest != null
								};

								if (flightManifest.set) {
									flightManifest.phoneNumber = guest.flightManifest.phoneNumber;

									if (flightManifest.transportationType == 1 || flightManifest.transportationType == 2) {
										flightManifest.arrivalDepartureAirportIata = guest.flightManifest.arrivalDepartureAirportIata;
										flightManifest.arrivalDepartureAirportTimezone = guest.flightManifest.arrivalDepartureAirportTimezone;
										flightManifest.arrivalDepartureDate = this.$moment(guest.flightManifest.arrivalDepartureDate).toDate();
										flightManifest.arrivalAirport = guest.flightManifest.arrivalAirportId;
										flightManifest.arrivalAirline = guest.flightManifest.arrivalAirline;
										flightManifest.arrivalNumber = guest.flightManifest.arrivalNumber;
										flightManifest.arrivalDateTime = guest.flightManifest.arrivalDateTime;
									}

									if (flightManifest.transportationType == 1 || flightManifest.transportationType == 3) {
										flightManifest.departureAirport = guest.flightManifest.departureAirportId;
										flightManifest.departureDate = this.$moment(guest.flightManifest.departureDate).toDate();
										flightManifest.departureAirline = guest.flightManifest.departureAirline;
										flightManifest.departureNumber = guest.flightManifest.departureNumber;
										flightManifest.departureDateTime = guest.flightManifest.departureDateTime;
									}
								}

								this.flightManifests.push(flightManifest);
							}
						});

						this.$store.commit('notification', {
							type: 'success',
							message: 'The Flight Manifests have been updated.'
						});

						this.flightManifestsErrors = [];

						this.fetchFlightDetails();
					})
					.catch(error => {
						if (error.response.status === 422) {
							this.flightManifestsErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			async fetchFlightDetails() {
				let payload = this.flightManifests.map(manifest => ({
					...manifest,
					arrivalDepartureDate: manifest.arrivalDepartureDate instanceof Date ? manifest.arrivalDepartureDate.toDateString() : manifest.arrivalDepartureDate,
					departureDate: manifest.departureDate instanceof Date ? manifest.departureDate.toDateString() : manifest.departureDate
				}));

				await this.$http.post('/flight-details', payload)
					.then(response => {
						this.flightDetailsData = response.data;

						if (Object.keys(this.flightDetailsData).length > 0) {
							Object.entries(this.flightDetailsData).forEach(([guestId, flightDetails]) => {
								let flightManifest = this.flightManifests.find(flightManifest => flightManifest.guestId == guestId);

								if (flightDetails.arrival) {
									flightManifest.arrivalDateTime = flightDetails.arrival.scheduled_arrival_formatted;
								}

								if (flightDetails.departure) {
									flightManifest.departureDateTime = flightDetails.departure.scheduled_departure_formatted;
								}
							});

							this.hasFlightDetails = true;
						} else {
							this.hasFlightDetails = false;
						}
					})
					.catch(error => {
						if (error.response && error.response.data && error.response.data.error) {
							this.$store.commit('notification', {
								type: 'danger',
								message: error.response.data.error,
							});
						}
					});
			},
			updatePaymentArrangements() {
				this.isLoading = 'updatePaymentArrangements';

				let payload = this.paymentArrangements.map(arrangement => ({
					...arrangement,
					dueDate: arrangement.dueDate instanceof Date ? arrangement.dueDate.toDateString() : arrangement.dueDate
				}));

				let request = this.$http.post('/individual-bookings/' + this.$route.params.id + '/payment-arrangements', {'paymentArrangements': payload})
					.then(response => {
						this.paymentArrangementsErrors = {};

						this.$store.commit('notification', {
							type: 'success',
							message: 'The Payment Arrangements have been updated.'
						});
					}).catch(error => {
						if (error.response.status === 422) {
							this.paymentArrangementsErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			updateTermsConditions() {
				this.isLoading = 'updateTermsConditions';

				let request = this.$http.patch('/individual-bookings/' + this.$route.params.id + '/terms-conditions', {
						termsAndConditions: this.booking.termsAndConditions
					}).then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The terms and conditions have been updated.'
						});

						this.bookingErrors.termsAndConditions = [];
						this.booking.termsAndConditions = response.data.termsAndConditions;
					}).catch(error => {
						if (error.response.status === 422) {
							this.bookingErrors.termsAndConditions = error.response.data.errors.termsAndConditions || [];
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			confirmed() {
				this.fetchData();

				this.showConfirm = false;
			},
			deleted() {
				this.fetchData();

				this.showDelete = false;
			},
			restored() {
				this.fetchData();

				this.showRestore = false;
			},
			forceDeleted() {
				this.$router.push({
					name: 'individual-bookings'
				});
			},
			fitQuoteSent() {
				this.fetchData();

				this.showSendFitQuote = false;
			},
			invoiceSent() {
				this.fetchData();

				this.showSendInvoice = false;
			},
			travelDocumentsSent() {
				this.fetchData();

				this.showSendTravelDocuments = false;
			},
			saveState() {			
				const state = JSON.stringify({
					booking: this.booking,
					guests: this.guests
				});

				const currentState = this.history[this.historyIndex];

				if (currentState === state) {
					return;
				}

				if(this.canUndo) {
					const previousState = this.history[this.historyIndex - 1];

					if(previousState === state) {
						this.undo();
						return;
					}
				}			

				if(this.canRedo) {
					const nextState = this.history[this.historyIndex + 1];

					if(nextState === state) {
						this.redo();
						return;
					}
				}

				this.history.push(state);
				this.historyIndex++;
			},
			undo() {
				if(!this.canUndo) return;

				this.historyIndex--;

				this.loadState();
			},
			redo() {
				if(!this.canRedo) return;
				
				this.historyIndex++;

				this.loadState();

			},
			loadState() {
				const state = JSON.parse(this.history[this.historyIndex]);
				this.booking = state.booking;
				this.guests = state.guests;

				this.$nextTick(() => {
					const elements = this.$el.querySelectorAll('textarea, input');

					elements.forEach(element => {
						if (element.tagName.toLowerCase() === 'textarea') {
							const path = element.getAttribute('data-v-model') || '';

							if (path.startsWith('booking.')) {
								const key = path.replace('booking.', '');
								element.value = this.booking[key] || '';
								element.dispatchEvent(new Event('input', { bubbles: true }));
							}
						}

						else if (element.tagName.toLowerCase() === 'input') {
							element.dispatchEvent(new Event('input', { bubbles: true }));
						}
					});
				});
			},
		}
	}
</script>
