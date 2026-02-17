<template>
<card v-if="savedBooking" :title="'Booking #' + savedBooking.order" :booking-status="savedBooking.deletedAt ? 'cancelled' : 'active'" :class="{ 'quote-active': isQuoteMode }">
	<template v-slot:action>
		<a @click.prevent="undo" class="button is-outlined is-primary is-inverted" :style="!canUndo ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : ''">
			<span class="icon"><i class="fas fa-undo"></i></span>
		</a>
		<a @click.prevent="redo" class="button is-outlined is-primary is-inverted" :style="!canRedo ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : ''">
			<span class="icon"><i class="fas fa-redo"></i></span>
		</a>	
		<a v-if="previousBooking" :href="`/groups/${group.id}/bookings/${previousBooking.id}`"	class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-arrow-left"></i></span>
		</a>
		<a v-if="nextBooking" :href="`/groups/${group.id}/bookings/${nextBooking.id}`"	class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-arrow-right"></i></span>
		</a>
    <a @click.prevent="viewInvoice" class="button is-outlined is-primary is-inverted">
      <span class="icon"><i class="fas fa-file-invoice"></i></span>
      <span>View Invoice</span>
    </a>
		<template v-if="group.fit && !savedBooking.quoteAccepted">
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
		<router-link v-if="savedBooking.can.viewClients" :to="{ name: 'clients', params: { group: group.id, booking: savedBooking.id }}" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-user-tie"></i></span>
			<span>Clients</span>
		</router-link>
		<router-link v-if="savedBooking.can.viewPayments" :to="{ name: 'payments', params: { group: group.id, booking: savedBooking.id }}" class="button is-outlined is-primary is-inverted">
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
			<confirm-booking v-if="showConfirm" :booking="savedBooking" :provider="group.provider" @confirmed="confirmed" @canceled="showConfirm = false" />
		</template>
		<template v-if="savedBooking.can.confirmChanges">
			<a @click.prevent="showConfirmChanges = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-exchange-alt"></i></span>
				<span>View Changes</span>
			</a>
			<confirm-changes v-if="showConfirmChanges" :booking="savedBooking" :group="group" @resolvedChanges="resolvedChanges" @canceled="showConfirmChanges = false" />
		</template>
    <template v-if="guestChanges">
        <router-link :to="{ name: 'pending' }" class="button is-outlined is-primary is-inverted">
          <span class="icon"><i class="fas fa-clock"></i></span>
          <span>Guest Changes</span>
        </router-link>
    </template>
    <template v-if="(group.fit && savedBooking.quoteAccepted) || !group.fit">
      <label class="checkbox button is-outlined is-primary is-inverted">
          <div class="mt-3">
            <input type="checkbox" v-model="isQuoteMode" @change="revertOriginalBooking">
            <span>Quote Mode</span>
          </div>
      </label>
    </template>
		<template v-if="savedBooking.can.delete">
			<a @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-trash"></i></span>
			</a>
			<delete-booking v-if="showDelete" :booking="savedBooking" :group="group" @deleted="deleted" @canceled="showDelete = false" />
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
			<force-delete-booking v-if="showForceDelete" :booking="savedBooking" :group="group" @forceDeleted="forceDeleted" @canceled="showForceDelete = false" />
		</template>
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">Booking</tab>
			<tab @click="setTab('guests')" :is-active="tabs.guests">Guests</tab>
      <tab v-if="group.transportation" @click="setTab('flightManifests')" :is-active="tabs.flightManifests">Flight Itineraries</tab>
			<tab v-if="hasFlightDetails" @click="setTab('flightDetails')" :is-active="tabs.flightDetails">Flight Details</tab>
			<tab @click="setTab('attrition')" :is-active="tabs.attrition">Attrition</tab>
			<tab @click="setTab('paymentArrangements')" :is-active="tabs.paymentArrangements">Payment Arrangements</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<form-panel label="Room Arrangements" class="is-borderless">
			<template v-slot:action>
				<control-button v-if="!readonly" class="is-small is-link is-outlined" @click="booking.roomArrangements.push({hotel: '', room: '', bed: '', dates: {start: '', end : ''}})">
					<i class="fas fa-plus"></i>
				</control-button>
			</template>
			<form-field :errors="bookingErrors['roomArrangements']">
				<input type="hidden" />
			</form-field>
			<form-panel v-for="(roomArrangement, index) in booking.roomArrangements" :key="index">
				<template v-if="!readonly" v-slot:action>
					<control-button class="is-small is-link is-outlined" @click="booking.roomArrangements.splice(index, 1)">
						<i class="fas fa-minus"></i>
					</control-button>
				</template>
				<form-field label="Hotel" :errors="bookingErrors['roomArrangements.' + index + '.hotel']" :required="true">
					<control-select 
						v-model="roomArrangement.hotel" 
						:options="Array.isArray(group.hotels) ? group.hotels.map(hotel => ({ value: hotel.id, text: hotel.name })) : []" 
						first-is-empty
						:class="{ 'is-danger': (bookingErrors['roomArrangements.' + index + '.hotel'] || []).length }" 
						:readonly="readonly" 
					/>
				</form-field>
				<form-field label="Room" :errors="bookingErrors['roomArrangements.' + index + '.room']" :required="true">
					<control-select 
						v-model="roomArrangement.room"
						:options="roomArrangement.hotel ? getRoomOptions(roomArrangement.hotel) : []"
						first-is-empty
						:class="{ 'is-danger': (bookingErrors['roomArrangements.' + index + '.room'] || []).length }" 
						:readonly="readonly"
					/>
				</form-field>
				<form-field label="Bed Type" :errors="bookingErrors['roomArrangements.' + index + '.bed']" :required="true">
					<control-select 
						v-model="roomArrangement.bed" 
				    :options="roomArrangement.room ? getBedOptions(roomArrangement.hotel, roomArrangement.room) : []"
						first-is-empty
						:class="{ 'is-danger': (bookingErrors['roomArrangements.' + index + '.bed'] || []).length }" 
						:readonly="readonly"
					/>
				</form-field>
				<form-field label="Dates" :errors="[...(bookingErrors['roomArrangements.' + index + '.dates.start'] || []), ...(bookingErrors['roomArrangements.' + index + '.dates.end'] || [])]" :required="true">
					<date-picker 
						is-range
						v-model="roomArrangement.dates" 
						:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
						:min-date="$moment(group.eventDate).subtract('10', 'days').toDate()"
						:max-date="$moment(group.eventDate).add('10', 'days').toDate()"
					>
						<template v-slot="{ inputValue, inputEvents }">
							<input 
								:readonly="readonly"
								:value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
								v-on="inputEvents.start"
								:class="{
									'input': true,
									'is-danger': [...(bookingErrors['roomArrangements.' + index + '.dates.start'] || []), ...(bookingErrors['roomArrangements.' + index + '.dates.end'] || [])].length,
								}"
							/>
						</template>
					</date-picker>
				</form-field>
			</form-panel>
		</form-panel>
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
    <div v-if="group.fit" class="columns">
    	<div class="column">
				<form-field label="Minimum Deposit" :errors="[...(bookingErrors.deposit || []), ...(bookingErrors.depositType || [])]">
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
				<form-field label="Booking ID" :errors="bookingErrors.bookingId">
					<control-input v-model="booking.bookingId" :class="{ 'is-danger': (bookingErrors.bookingId || []).length }" :readonly="readonly" />
				</form-field>
			</div>
      <div class="column">
        <form-field label=" ">
					<label class="checkbox">
            <input type="checkbox" v-model="booking.isBgCouple" :disabled="readonly" :class="{ 'is-danger': (bookingErrors.isBgCouple || []).length }"/>
            Is Bride & Groom Booking?
          </label>
        </form-field>
      </div>
		</div>
		<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }" :disabled="isQuoteMode">Save</control-button>
		<control-button v-if="!readonly" @click="updateWithGuestTravelDates" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">{{ isQuoteMode ? 'Update Guest Travel Dates' : 'Save & Update Guest Travel Dates' }}</control-button>
	</template>
	<template v-if="tabs.guests">
		<form-panel class="is-borderless">
			<template v-slot:action v-if="!readonly">
				<control-button class="is-small is-link is-outlined" @click="guests.push({ transportation: group.transportation, customGroupAirport: group.defaultAirport ? group.defaultAirport.id : null })">
					<i class="fas fa-plus"></i>
				</control-button>
			</template>
			<form-panel v-for="(guest, index) in guests" :key="index" :label="'Guest ' + (index + 1)" :class="{ 'deleted': guest.deleted_at }">
				<template v-slot:action>
          <template v-if="!readonly && guest.id && guestInOtherBookings[guest.id]">
            <control-button v-for="anotherBooking in guestInOtherBookings[guest.id]" :key="anotherBooking.id" class="is-small is-link is-outlined mr-5" @click="redirectBooking(anotherBooking.id)">
              R{{ anotherBooking.order }}
            </control-button>
          </template>
          <template v-if="!readonly && (guests.length > 1)">
            <control-button class="is-small is-link is-outlined" @click="guest.id ? guest.deleted_at = true : guests.splice(index, 1)" v-if="!guest.deleted_at">
              <i class="fas fa-trash"></i>
            </control-button>
            <control-button class="is-small is-link is-outlined" @click="guest.deleted_at = false" v-if="guest.deleted_at">
              <i class="fas fa-trash-restore"></i>
            </control-button>
            <control-button class="is-small is-link is-outlined" @click="guests.splice(index, 1)" v-if="guest.deleted_at">
              <i class="fas fa-minus"></i>
            </control-button>
          </template>
				</template>
				<div class="columns">
					<div class="column">
						<form-field label="First Name" :errors="guestsErrors['guests.' + index + '.firstName']" :required="true">
							<control-input
								v-model="guest.firstName"
								class="is-capitalized"
								:class="{ 'is-danger': (guestsErrors['guests.' + index + '.firstName'] || []).length, 'disabled':guest.deleted_at }"
								:readonly="readonly" 
								:disabled="guest.deleted_at"
							/>
						</form-field>
            <p v-if="guestsErrors.duplicate_guests_in_request && guestsErrors.duplicate_guests_in_request.includes(index)" class="help is-danger">This guest is being duplicated.</p>
					</div>
					<div class="column">
						<form-field label="Last Name" :errors="guestsErrors['guests.' + index + '.lastName']" :required="true">
							<control-input 
								v-model="guest.lastName"
								class="is-capitalized"
								:class="{ 'is-danger': (guestsErrors['guests.' + index + '.lastName'] || []).length, 'disabled':guest.deleted_at }" 
								:readonly="readonly" 
								:disabled="guest.deleted_at"
							/>
						</form-field>
					</div>
				</div>
				<div class="columns">
					<div class="column">
						<form-field label="Date of Birth" :errors="guestsErrors['guests.' + index + '.birthDate']" :required="true">
							<date-picker v-model="guest.birthDate" :popover="{ visibility: readonly ? 'hidden' : 'focus' }">
								<template v-slot="{ inputValue, inputEvents }">
									<input
										:readonly="readonly"
										:class="{ 
											'input': true, 
											'is-danger': (guestsErrors['guests.' + index + '.birthDate'] || []).length,
											'disabled':guest.deleted_at
										}"
										:value="inputValue"
										v-on="inputEvents" 
										:disabled="guest.deleted_at"
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
									'disabled':guest.deleted_at
								}"
								:readonly="readonly" 
								:disabled="guest.deleted_at"
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
										:min-date="$moment(group.eventDate).subtract('10', 'days').toDate()"
										:max-date="$moment(group.eventDate).add('10', 'days').toDate()"
									>
										<template v-slot="{ inputValue, inputEvents }">
											<input
												:readonly="readonly" 
												:disabled="guest.deleted_at"
												:value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
												v-on="inputEvents.start"
												:class="{
													'input': true,
													'is-danger': [...(guestsErrors['guests.' + index + '.dates.start'] || []), ...(guestsErrors['guests.' + index + '.dates.end'] || [])].length,
													'disabled':guest.deleted_at
												}"
											/>
										</template>
									</date-picker>
								</div>
								<div class="control">
									<button type="button" class="button is-primary" @click="applyDatesToAllGuests(guest.dates)" :disabled="readonly || guest.deleted_at">
										Apply to All
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
									'disabled':guest.deleted_at
								}"
								:readonly="readonly" 
								:disabled="guest.deleted_at"
							/>
						</form-field>
					</div>
				</div>
				<div class="columns" v-if="group.transportation">
					<div class="column">
						<form-field label="Transportation" :errors="guestsErrors['guests.' + index + '.transportation']" :required="true">
							<control-radio
								v-model="guest.transportation"
								:options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
								:class="{ 
									'is-danger': (guestsErrors['guests.' + index + '.transportation'] || []).length,
									'disabled':guest.deleted_at
								}" 
								:readonly="readonly" 
								:disabled="guest.deleted_at" />
						</form-field>
					</div>
					<div class="column" v-if="guest.transportation">
						<form-field label="Custom Airport" :errors="guestsErrors['guests.' + index + '.customGroupAirport']">
							<control-select
								v-model="guest.customGroupAirport"
								:options="customGroupAirports"
								:readonly="readonly"
								:disabled="guest.deleted_at"
								:class="{ 'is-danger': (guestsErrors['guests.' + index + '.customGroupAirport'] || []).length }"
							/>
						</form-field>
					</div>
					<div class="column" v-if="guest.transportation">
						<form-field label="Transfer Type" :errors="guestsErrors['guests.' + index + '.transportation_type']" v-if="guest.transportation == true">
							<control-select
								v-model="guest.transportation_type"
								:options="transportationTypes.map(transportationType => ({value: transportationType.id, text: transportationType.description}))"
								:class="{ 
									'is-danger': (guestsErrors['guests.' + index + '.transportation_type'] || []).length,
									'disabled':guest.deleted_at 
								}" 
								:readonly="readonly" 
								:disabled="guest.deleted_at" 
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
										:disabled="guest.deleted_at"
										:class="{ 'is-danger': (guestsErrors['guests.' + index + '.departurePickupTime'] || []).length }"
									/>
								</div>
								<div class="control">
									<button type="button" class="button is-primary" @click="applyDeparturePickupTimeToAllGuests(guest.departurePickupTime)" :disabled="readonly || guest.deleted_at || isQuoteMode">
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
							'disabled':guest.deleted_at 
						}" 
						:readonly="readonly" 
						:disabled="guest.deleted_at"
					/>
				</form-field>
			</form-panel>
		</form-panel>
		<control-button v-if="!readonly" @click="checkGuestsBeforeUpdate" class="is-primary" :class="{ 'is-loading': isLoading === 'updateGuests' }" :disabled="isQuoteMode">
			Save
		</control-button>
		<modal title="Transportation Warning" :is-active="showGuestTransportationWarning" @hide="cancelGuestTransportationWarning">
			<p>Please make sure that the transfer provider(s) <span v-html="transferProviderName"></span> has been notified about the guest cancellation.</p>
			<template v-slot:footer>
				<div class="field is-grouped">
					<control-button @click="cancelGuestTransportationWarning">Cancel</control-button>
					<control-button @click="proceedWithGuestUpdate" type="submit" class="is-primary">Continue</control-button>
				</div>
			</template>
		</modal>
		<modal title="Warning!" :is-active="showGuestWarnings" @hide="cancelGuestUpdates">
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
									v-model="flightManifest.phoneNumber"
									placeholder="Phone"
									:readonly="readonly"
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
										placeholder="Departure Airport"
										:readonly="readonly"
										:class="{ 'is-danger': (flightManifestsErrors['flightManifests.' + index + '.arrivalDepartureAirportIata'] || []).length }"
									/>
								</form-field>
							</div>
							<div class="column">
								<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.arrivalDepartureDate']">
									<date-picker
										v-model="flightManifest.arrivalDepartureDate"
										:min-date="$moment(group.eventDate).subtract('10', 'days').toDate()"
										:max-date="$moment(group.eventDate).add('10', 'days').toDate()"
										mode="date"
										:popover="{ visibility: readonly ? 'hidden' : 'focus' }"
									>
										<template v-slot="{ inputValue, inputEvents }">
											<input
												placeholder="Departure Date"
												:class="'input' + ((flightManifestsErrors['flightManifests.' + index + '.arrivalDepartureDate'] || []).length ? ' is-danger' : '')"
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
								<form-field :errors="flightManifestsErrors['flightManifests.' + index + '.arrivalAirport']">
									<control-select 
										v-model="flightManifest.arrivalAirport"
										:options="[{value: '', text: 'Arrival Airport', disabled: true}, ...groupAirports]"
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
										:min-date="$moment(group.eventDate).subtract('10', 'days').toDate()"
										:max-date="$moment(group.eventDate).add('10', 'days').toDate()"
										mode="dateTime"
										is24hr
										@input="updateDate(flightManifest, 'arrivalDateTime')"
										:popover="{ visibility: readonly ? 'hidden' : 'focus' }"
									>
										<template v-slot="{ inputValue, inputEvents }">
											<input
												placeholder="Arrival Date & Time"
												:class="'input' + ((flightManifestsErrors['flightManifests.' + index + '.arrivalDateTime'] || []).length ? ' is-danger' : '')"
												:value="inputValue"
												v-on="inputEvents"
												:readonly="readonly"
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
										:options="[{value: '', text: 'Departure Airport', disabled: true}, ...groupAirports]"
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
										:min-date="$moment(group.eventDate).subtract('10', 'days').toDate()"
										:max-date="$moment(group.eventDate).add('10', 'days').toDate()"
										mode="date"
										:popover="{ visibility: readonly ? 'hidden' : 'focus' }"
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
										:min-date="$moment(group.eventDate).subtract('10', 'days').toDate()"
										:max-date="$moment(group.eventDate).add('10', 'days').toDate()"
										mode="dateTime"
										is24hr
										@input="updateDate(flightManifest, 'departureDateTime')"
										:popover="{ visibility: readonly ? 'hidden' : 'focus' }"
									>
										<template v-slot="{ inputValue, inputEvents }">
											<input
												placeholder="Departure Date & Time"
												:class="'input' + ((flightManifestsErrors['flightManifests.' + index + '.departureDateTime'] || []).length ? ' is-danger' : '')"
												:value="inputValue"
												v-on="inputEvents"
												:readonly="readonly"
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
    <control-button v-if="!readonly" @click="updateFlightManifests" class="is-primary" :class="{ 'is-loading': isLoading === 'updateFlightManifests' }">
      Save
    </control-button>
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
	<template v-if="tabs.attrition">
			<img v-if="group.attritionImage && group.attritionImage.storagePath" :src="group.attritionImage.storagePath" class="image is-128x128" />
			<form-field label="Attrition Chart" :errors="groupErrors.attritionImage">
				<image-uploader v-model="group.attritionImage" @errors="$set(groupErrors, 'attritionImage', $event)" :class="{ 'is-danger': (groupErrors.attritionImage || []).length }" :max-size="1024" is-single :disabled="readonly" />
      </form-field>
			<form-panel label="Attrition Due Dates" class="is-borderless">
        <template v-if="!readonly" v-slot:action>
					<control-button class="is-small is-link is-outlined" @click="attritionDueDates.push({ date: '' })">
						<i class="fas fa-plus"></i>
					</control-button>
        </template>
        <div v-for="(dueDate, index) in attritionDueDates" :key="index" class="field is-horizontal is-borderless">
					<div class="field-body">
						<div class="field is-expanded">
							<div class="control">
								<div class="is-flex is-align-items-center">
									<date-picker
										v-model="dueDate.date"
										:max-date="$moment(group.eventDate).toDate()"
										:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
									>
										<template v-slot="{ inputValue, inputEvents }">
											<input
												:class="'input' + ((groupErrors['attrition.' + index + '.date'] || []).length ? ' is-danger' : '')"
												:readonly="readonly"
												:value="inputValue"
												v-on="inputEvents"
											/>
										</template>
									</date-picker>
									<control-button v-if="!readonly" class="is-small is-link is-outlined" style="margin-left: 10px;" @click="attritionDueDates.splice(index, 1)">
										<i class="fas fa-minus"></i>
									</control-button>
								</div>
							</div>
						</div>
					</div>
        </div>
    	</form-panel>
			<control-button v-if="!readonly" @click="syncAttrition" class="is-primary" :class="{ 'is-loading': isLoading === 'syncAttrition' }">Save</control-button>
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
						:max-date="$moment(group.eventDate).toDate()"
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
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ConfirmBooking from '@dashboard/pages/Groups/Bookings/Confirm';
import ConfirmChanges from './ConfirmChanges.vue';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlRadio from '@dashboard/components/form/controls/Radio';
import ControlSelect from '@dashboard/components/form/controls/Select';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';
import DatePicker from 'v-calendar/lib/components/date-picker.umd';
import DeleteBooking from '@dashboard/pages/Groups/Bookings/Delete';
import ForceDeleteBooking from '@dashboard/pages/Groups/Bookings/ForceDelete';
import FormField from '@dashboard/components/form/Field';
import FormPanel from '@dashboard/components/form/Panel';
import RestoreBooking from '@dashboard/pages/Groups/Bookings/Restore';
import SendInvoice from '@dashboard/pages/Groups/Bookings/Invoice/Send';
import SendTravelDocuments from '@dashboard/pages/Groups/Bookings/TravelDocuments/Send';
import SendFitQuote from '@dashboard/pages/Groups/Bookings/FitQuote/Send';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';
import ImageUploader from '@dashboard/components/file/ImageUploader';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		Card,
		ConfirmBooking,
		ConfirmChanges,
		ControlButton,
		ControlInput,
		ControlRadio,
		ControlSelect,
		ControlTextarea,
		DatePicker,
		DeleteBooking,
		ForceDeleteBooking,
		FormField,
		FormPanel,
		RestoreBooking,
		SendInvoice,
		SendTravelDocuments,
		SendFitQuote,
		Tab,
		Tabs,
		ImageUploader,
		Modal,
	},
	data() {
		return {
			savedBooking: null,
			previousBooking: null,
			nextBooking: null,
			booking: {
				roomArrangements: [],
			},
			bookingErrors: {
				roomArrangements: [],
			},
			guests: [],
			guestsErrors: {},
			flightManifests: [],
			airlines: [],
			flightManifestsErrors: {},
			group: {},
			groupErrors: {},
			showConfirm: false,
			showConfirmChanges: false,
			showDelete: false,
			showRestore: false,
			showForceDelete: false,
			showSendInvoice: false,
			showSendTravelDocuments: false,
			showSendFitQuote: false,
			hasFlightDetails: false,
			flightDetailsData:[],
			tabs: {
				info: true,
				guests: false,
				flightManifests: false,
				attrition: false,
				flightDetails: false,
				paymentArrangements: false,
			},
			isLoading: '',
			transportationTypes: [],
			customGroupAirports: [],
			groupAirports: [],
			paymentArrangements: [],
			paymentArrangementsErrors: {},
			history: [],
			historyIndex: -1,
			showGuestWarnings: false,
			ignoreGuestWarnings: false,
			guestWarnings: [],
			showGuestTransportationWarning: false,
     	isQuoteMode: false,
      guestInOtherBookings: [],
			attritionDueDates: [],
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
		getRoomOptions() {
			return (hotelId) => {
				if (!Array.isArray(this.group.hotels)) return [];

				const selectedHotel = this.group.hotels.find(hotel => hotel.id === hotelId);

				return selectedHotel?.rooms
					? selectedHotel.rooms.map(room => ({ value: room.id, text: room.name }))
					: [];
			};
		},
		getBedOptions() {
			return (hotelId, roomId) => {
				const selectedHotel = this.group.hotels.find(hotel => hotel.id === hotelId);

				if (selectedHotel) {
					const selectedRoom = selectedHotel.rooms.find(room => room.id === roomId);

					return selectedRoom?.beds
						? selectedRoom.beds.map(bed => ({ value: bed, text: bed }))
						: [];
				}

				return [];
			};
		},
		canUndo() {
			return this.historyIndex > 0;
		},
		canRedo() {
			return this.historyIndex < this.history.length - 1;
		},
    guestChanges() {
        return this.savedBooking.guestChanges.length > 0;
    },
		transferProviderName() {
      if (!this.group?.airports) return '';

      const providers = this.group.airports.filter(airport => airport.transferProvider)
        .map(airport => `<b style="text-transform: lowercase;">${airport.transferProvider.name}</b>`)
        .filter((name, index, self) => self.indexOf(name) === index);

      if (providers.length === 0) return '';

      const head = providers.slice(0, -1).join(', ');
      const last = providers[providers.length - 1];

      return head ? `${head} and ${last}` : last;
		}
	},
	methods: {
		async fetchData() {
			await this.$http.get('/groups/' + this.$route.params.group + '/bookings/' + this.$route.params.id)
				.then(response => {
					this.airlines = response.data.airlines.map(airline => ({
							value: airline.iata_code,
							text: airline.name
					}));
					
					this.savedBooking = response.data.data;
					this.previousBooking = response.data.previousBooking;
					this.nextBooking = response.data.nextBooking;
          this.guestInOtherBookings = response.data.guestInOtherBookings;

					this.paymentArrangements = this.savedBooking.paymentArrangements.map(arrangement => ({
						...arrangement,
						dueDate: this.$moment(arrangement.dueDate).toDate()
					}));

					var mappedRooms = this.savedBooking.rooms.map(room => {
						return {
							hotel: room.hotel.id,
							room: room.pivot.room_block_id,
							bed: room.pivot.bed,
							dates: {
								start: room.pivot.check_in,
								end: room.pivot.check_out,
							},
						};
					});

					this.booking = {
						roomArrangements: mappedRooms,
						specialRequests: this.savedBooking.specialRequests,
						notes: this.savedBooking.notes,
						deposit: this.savedBooking.deposit,
						depositType: this.savedBooking.depositType,
						bookingId: this.savedBooking.bookingId,
						isBgCouple: this.savedBooking.isBgCouple,
					};

					this.guests = [];
					this.flightManifests = [];

					this.clients = this.savedBooking.clients.map(client => {
						this.guests = this.guests.concat(client.guests.map(guest => {
							if(guest.transportation && !guest.deleted_at) {
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
								transportation_type: guest.transportation_type,
								deleted_at: !!guest.deleted_at,
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
					this.group = response.data.group;
					this.transportationTypes = response.data.transportationTypes;
					this.customGroupAirports = response.data.group.airports.map(groupAirport => ({ value: groupAirport.id, text: groupAirport.originAirport.airport_code }));
					this.groupAirports = response.data.group.airports.map(groupAirport => ({ value: groupAirport.originAirport.id, text: groupAirport.originAirport.airport_code }));
          this.attritionDueDates = response.data.group.groupAttritionDueDates.map(dueDate => ({
							date: this.$moment(dueDate.date).toDate(),
						}));

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
		setBreadcrumbs() {
			this.$store.commit('breadcrumbs', [
				{
					label: 'Dashboard',
					route: 'home'
				},
				{
					label: 'Groups',
					route: 'groups'
				},
				{
					label: this.group.brideLastName + ' & ' + this.group.groomLastName,
					route: 'groups.show',
					params: {
						id: this.group.id
					}
				},
				{
					label: 'Bookings',
					route: 'bookings'
				},
				{
					label: '#' + this.savedBooking.order,
					route: 'bookings.show',
					params: {
						group: this.$route.params.group,
						id: this.savedBooking.id
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

			this.booking.roomArrangements.forEach(arrangement => {
				if (arrangement.dates instanceof Object) {
					arrangement.dates.start = arrangement.dates.start instanceof Date ? arrangement.dates.start.toDateString() : arrangement.dates.start;
					arrangement.dates.end = arrangement.dates.end instanceof Date ? arrangement.dates.end.toDateString() : arrangement.dates.end;
				}
			});

			return this.$http.put('/groups/' + this.$route.params.group + '/bookings/' + this.$route.params.id, this.booking)
        .then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The booking has been updated.'
					});

					this.savedBooking = {
						...this.savedBooking,
						...response.data.data,
					};

					this.bookingErrors = {};

					this.saveState();
				}).catch(error => {
					if (error.response.status === 422) {
						this.bookingErrors = error.response.data.errors;
					}

					throw error;
				}).finally(() => {
					this.isLoading = '';
				});
		},
		async updateWithGuestTravelDates() {
      if (!this.isQuoteMode){
        await this.update();
      }

			const arrangements = this.booking.roomArrangements;
			const allDates = arrangements.flatMap(arr => [arr?.dates?.start, arr?.dates?.end]).filter(date => date);
			allDates.sort((a, b) => new Date(a) - new Date(b));

			const dates = {
				start: allDates[0] || null,
				end: allDates[allDates.length - 1] || null
			};

			this.applyDatesToAllGuests(dates);
		},
		applyDatesToAllGuests(dates) {
      if (this.isQuoteMode) {
        this.guests.forEach(guest => {
          if (!guest.deleted_at) {
            guest.dates = {
              start: dates.start instanceof Date ? dates.start.toDateString() : dates.start,
              end: dates.end instanceof Date ? dates.end.toDateString() : dates.end,
            };
          }
        });
			}else{
        this.$http.patch(`/groups/${this.$route.params.group}/bookings/${this.$route.params.id}/update-travel-dates`, {
          start: dates.start instanceof Date ? dates.start.toDateString() : dates.start,
          end: dates.end instanceof Date ? dates.end.toDateString() : dates.end,
        }).then(() => {
          this.$store.commit('notification', {
            type: 'success',
            message: 'Travel dates have been updated for all guests.'
          });

          this.fetchData();
        });
      }
		},
		applyDeparturePickupTimeToAllGuests(departurePickupTime) {
			this.$http.patch(`/groups/${this.$route.params.group}/bookings/${this.$route.params.id}/update-departure-pickup-time`, {
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
      const eventDate = this.$moment(this.group.eventDate);
      const daysUntilEvent = eventDate.diff(this.$moment(), 'days');

      if (daysUntilEvent <= 30 && daysUntilEvent >= 0 && this.group.transportation) {
        const originalActiveGuestsWithTransportation = [];

        this.savedBooking.clients.forEach(client => {
          client.guests.forEach(guest => {
            if (guest.transportation && !guest.deleted_at) {
              originalActiveGuestsWithTransportation.push(guest.id);
            }
          });
        });

        const hasNewlySoftDeletedGuestsWithTransportation = this.guests.some(guest => {
          return guest.deleted_at == true && guest.transportation && originalActiveGuestsWithTransportation.includes(guest.id);
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

			let request = this.$http.patch('/groups/' + this.$route.params.group + '/bookings/' + this.$route.params.id + '/guests', {
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
						if(guest.transportation && !guest.deleted_at) {
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
							transportation_type: guest.transportation_type,
							deleted_at: !!guest.deleted_at,
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
		updateFlightManifests() {
			this.isLoading = 'updateFlightManifests';

			let payload = this.flightManifests.map(manifest => ({
				...manifest,
				arrivalDepartureDate: manifest.arrivalDepartureDate instanceof Date ? manifest.arrivalDepartureDate.toDateString() : manifest.arrivalDepartureDate,
				departureDate: manifest.departureDate instanceof Date ? manifest.departureDate.toDateString() : manifest.departureDate
			}));

			let request = this.$http.patch('/groups/' + this.$route.params.group + '/bookings/' + this.$route.params.id + '/flight-manifests', {
					flightManifests: payload
				}).then(response => {
					this.flightManifests = [];

					response.data.data.forEach(guest => {
						if(guest.transportation && !guest.deleted_at) {
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
		setFlightManifest(flightManifest) {
			let guest = this.guests.find(guest => (guest.id === flightManifest.guestId));
			flightManifest.arrivalDateTime = flightManifest.arrivalDateTime ?? this.$moment(guest.dates.start).format('YYYY-MM-DD HH:mm');
			flightManifest.departureDateTime = flightManifest.departureDateTime ?? this.$moment(guest.dates.end).format('YYYY-MM-DD HH:mm');
			flightManifest.set = true;
		},
		unsetFlightManifest(flightManifest) {
			flightManifest.set = false;
		},
		confirmed() {
			this.fetchData();

			this.showConfirm = false;
		},
		resolvedChanges() {
			this.showConfirmChanges = false;

			this.fetchData();
		},
		deleted() {
			this.fetchData();

			this.showDelete = false;
		},
		restored() {
			this.fetchData();

			this.showRestore = false;
		},
		invoiceSent() {
			this.fetchData();

			this.showSendInvoice = false;
		},
		fitQuoteSent() {
			this.fetchData();

			this.showSendFitQuote = false;
		},
		travelDocumentsSent() {
			this.fetchData();

			this.showSendTravelDocuments = false;
		},
		forceDeleted() {
			this.$router.push({
				name: 'bookings'
			});
		},
    hasDuplicateDates() {
      const dates = this.attritionDueDates.map(dueDate => dueDate.date);
      return new Set(dates).size !== dates.length;
    },
		syncAttrition() {
      this.attritionDueDates.forEach(attritionDueDate => {
        attritionDueDate.date = attritionDueDate.date instanceof Date ? attritionDueDate.date.toDateString() : attritionDueDate.date;
      });

      if (this.hasDuplicateDates()) {
        this.$store.commit('notification', {
          type: 'danger',
          message: 'Duplicate attrition due dates are not allowed.'
        });

        return;
      }

			this.isLoading = 'syncAttrition';

			let request = this.$http.patch('/groups/' + this.$route.params.group + '/attrition', {
						attritionImage: this.group.attritionImage,
						attritionDueDates: this.attritionDueDates,
				}).then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The attrition have been updated.'
					});

					this.groupErrors.attritionImage = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.groupErrors.attritionImage = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		updatePaymentArrangements() {
			this.isLoading = 'updatePaymentArrangements';
			
			let payload = this.paymentArrangements.map(arrangement => ({
				...arrangement,
				dueDate: arrangement.dueDate instanceof Date ? arrangement.dueDate.toDateString() : arrangement.dueDate
			}));

			let request = this.$http.post('/groups/' + this.$route.params.group + '/bookings/' + this.$route.params.id + '/payment-arrangements', {'paymentArrangements': payload})
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
		viewInvoice() {
      if (this.isQuoteMode) {

        this.booking.roomArrangements.forEach(arrangement => {
          if (arrangement.dates instanceof Object) {
            arrangement.dates.start = arrangement.dates.start instanceof Date ? arrangement.dates.start.toDateString() : arrangement.dates.start;
            arrangement.dates.end = arrangement.dates.end instanceof Date ? arrangement.dates.end.toDateString() : arrangement.dates.end;
          }
        });

        this.guests.map(guest => {
          if (guest.dates instanceof Object) {
            guest.dates.start = guest.dates.start instanceof Date ? guest.dates.start.toDateString() : (typeof guest.dates.start === 'string' ? guest.dates.start : null);
            guest.dates.end = guest.dates.end instanceof Date ? guest.dates.end.toDateString() : (typeof guest.dates.end === 'string' ? guest.dates.end : null);
          }

          guest.birthDate = guest.birthDate instanceof Date ? guest.birthDate.toDateString() : (typeof guest.birthDate === 'string' ? guest.birthDate : null);

          return guest;
        });

        const previewData = {
          booking: this.booking,
          guests: this.guests,
        };

        const url = `${this.savedBooking.invoiceUrl}?preview=1&data=${encodeURIComponent(JSON.stringify(previewData))}`;
        window.open(url, '_blank');
      } else {
        window.open(this.savedBooking.invoiceUrl, '_blank');
      }
    },
    revertOriginalBooking(){
      if(!this.isQuoteMode) this.fetchData();
    },
    redirectBooking(bookingId){
      window.open(`${bookingId}`, '_blank');
    }
	}
}
</script>
<style lang="scss">
  .card {
    &.quote-active {
      .card-header {
        background-color: #d5b9ae !important;
      }
    }
  }
  .mt-3{
    margin-top: 3px;
  }
  .mr-5{
    margin-right: 5px;
  }
</style>
