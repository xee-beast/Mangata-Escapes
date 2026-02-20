<template>
	<card title="Bookings">
		<template v-slot:action>
			<template v-if="!!group && !!group.can && group.can.delete && group.deletedAt">
				<a @click.prevent="showRestore = true" class="button is-outlined is-primary is-inverted">
					<span class="icon"><i class="fas fa-trash-restore"></i></span>
					<span>Restore Group</span>
				</a>
				<restore-group v-if="showRestore" :group="group" @restored="restoredGroup" @canceled="showRestore = false" />
			</template>			
			<create-booking v-if="can.create && !group.deletedAt" @created="fetchData()" :group="group" :countries="countries" button-class="is-outlined is-primary is-inverted button-left-margin" />
			<router-link v-if="group.can && group.can.viewAccomodations" :to="{ name: 'accomodations', params: { group: group.id }}" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-door-open"></i></span>
				<span>Accommodations</span>
			</router-link>
			<a :href="group.bookingsExportUrl" target="_blank" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-file-invoice"></i></span>
				<span>Export Bookings</span>
			</a>
			<a :href="group.flightManifestsExportUrl" target="_blank" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-file-invoice"></i></span>
				<span>Export Flight Manifests</span>
			</a>
			<a v-if="coupleBookingId" :href="`${$dashboardBase}/groups/${group.id}/bookings/${coupleBookingId}`" target="_blank" class="button is-outlined is-primary is-inverted">
        <span class="icon"><i class="fas fa-external-link-alt"></i></span>
        <span>B&G Booking</span>
      </a>
		</template>
		<template v-slot:tabs>
			<tabs class="is-boxed">
				<tab @click="setTab('bookings')" :is-active="tabs.bookings">Bookings</tab>
				<tab @click="setTab('dueDates')" :is-active="tabs.dueDates">Due Dates</tab>
			</tabs>
		</template>
		<template v-if="tabs.bookings">
			<div class="columns is-multiline mb-4">
				<div class="column is-2">
					<div class="stat-card">
						<span class="heading is-size-7 has-text-weight-bold has-text-grey-dark stat-label">TOTAL NIGHTS:</span>
						<span class="title is-2 has-text-weight-bold has-text-black">{{ nights_count }}</span>
					</div>
				</div>
				<div class="column is-2">
					<div class="stat-card">
						<span class="heading is-size-7 has-text-weight-bold has-text-grey-dark stat-label">AVERAGE NIGHTS:</span>
						<span class="title is-2 has-text-weight-bold has-text-black">{{ average_nights }}</span>
					</div>
				</div>
				<div class="column is-2">
					<div class="stat-card">
						<span class="heading is-size-7 has-text-weight-bold has-text-grey-dark stat-label">MOST COMMON NIGHTS:</span>
						<span class="title is-2 has-text-weight-bold has-text-black">{{ most_common_nights }}</span>
					</div>
				</div>
				<div class="column is-2">
					<div class="stat-card">
						<span class="heading is-size-7 has-text-weight-bold has-text-grey-dark stat-label">TOTAL BOOKINGS:</span>
						<span class="title is-2 has-text-weight-bold has-text-black">{{ bookings_count }}</span>
					</div>
				</div>
				<div class="column is-2">
					<div class="stat-card">
						<span class="heading is-size-7 has-text-weight-bold has-text-grey-dark stat-label">TOTAL ADULT PAX:</span>
						<span class="title is-2 has-text-weight-bold has-text-black">{{ total_adults_pax }}</span>
					</div>
				</div>
			</div>
			<div class="columns has-content-end">
				<div class="column is-6">
					<form-field>
						<VSelect
							v-model="filters.room_category"
							@input="filterData()"
							:options="roomCategoryOptions"
							multiple
							:filterable="true"
							label="text"
							:reduce="category => category.value"
							placeholder="Select Room Categories"
							class="is-small"
						/>
					</form-field>
				</div>
			</div>
			<data-table class="is-size-6" table-class="is-fullwidth" :columns="['#', 'Guests', 'Accommodation & Dates', 'Reservation', 'Status', 'Balance', 'Booking ID', 'Special Requests', 'Notes', 'Actions']">
				<template v-if="bookings.length">
					<tr :style="{ color: booking.isPaymentArrangementActive ? '#C7979C' : '', backgroundColor: booking.deletedAt ? '#3C3B3B' : '' }" :class="{'is-booking-deleted': booking.deletedAt }" v-for="(booking, index) in bookings" :key="booking.id">
						<th>
							<div class="has-text-centered">
								<div v-if="group.can.update && (index > 0)" style="margin-bottom: -0.5rem">
									<a class="table-action" @click.prevent="move('up', index)">
										<i class="fas fa-caret-up"></i>
									</a>
								</div>
								<div>
									{{ booking.order }}
								</div>
								<div v-if="group.can.update && (index < bookings.length - 1)" style="margin-top: -0.5rem">
									<a class="table-action" @click.prevent="move('down', index)">
										<i class="fas fa-caret-down"></i>
									</a>
								</div>
							</div>
						</th>
						<td>
							<ul style="list-style: disc outside; margin-left: 0.5rem;" :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
								<template v-for="client in booking.clients">
									<li v-for="guest in client.guests" :key="guest.id" :class="{ 'deleted': guest.deleted_at }">
										<span style="white-space: nowrap;">
											{{ guest.firstName }} {{ guest.lastName }} {{ printDateOfBirthIfChild(guest.birthDate) }} {{ guest.insurance ? '(TI)' : '' }}
											<img v-if="group.transportation && guest.transportation" :src="guest.transportation" src="data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAgAAAAIACAYAAAD0eNT6AAANvUlEQVR4nO3dXVobuRZAUeBjDgzA8x+SB8AofB/SuU0nGNvgcknaa72mQ1TVdaRNOT/Pp9PpCQBoedl7AQDA4wkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEGvey+Adbwfj6e91wCrezscnvdeA2t4Pp3s2VzPIQ/jEgfcQgDwJQc+zEsQ8BUBwH848GFdgoCPBAAOfQgSAwiAMAc/IAS6BECMQx84Rwy0CIAAhz5wKzGwPgGwMAc/8FNCYF0CYEEOfuDehMB6BMBCHPzA1oTAOgTAAhz8wKMJgfn5x4Am5/AH9mDvmZ83AJMyfMAovA2YkwCYjIMfGJUQmIuPACbi8AdGZo+aizcAEzBUwGy8DRifNwCDc/gDM7J3jc8bgEEZHmAV3gaMyRuAATn8gZXY08YkAAZjUIAV2dvG4yOAQRgOoMJHAmPwBmAADn+gxJ43BgGwM4MAFNn79icAdmQAgDJ74L4EwE48+AD2wj0JgB144AH+ZU/chwB4MA86wN/sjY8nAB7IAw5wnj3ysQTAg3iwAS6zVz6OAHgADzTA9eyZjyEANuZBBridvXN7/irgDXmAx+evJG0zo+Mzo9vxBmAjNhaAn7OXbkcAbMADC3A/9tRtCAAACBIAd6ZUAe7P3np/AuCOPKAA27HH3pcAuBMPJsD27LX3IwAAIEgA3IEiBXgce+59CIAf8iACPJ699+cEAAAECYAfUKAA+7EH/4wA+CYPHsD+7MXfJwAAIEgAfIPiBBiHPfl7BAAABAmAGylNgPHYm28nAG7gAQMYlz36NgIAAIIEwJWUJcD47NXXEwAAECQArqAoAeZhz76OAACAIAFwgZIEmI+9+zIBAABBAuALChJgXvbwrwkAAAgSAAAQJADO8OoIYH728vMEAAAECQAACBIAn/DKCGAd9vTPve69ANiTjQGo8gbgDw4EgPXY2/8mAAAgSAAAQJAA+MArIoB12eP/SwAAQJAAAIAgAQAAQQLgHz4bAlifvf5fAgAAggQAAAQJAAAIEgBPPhMCKLHn/yIAACBIAABAkAAAgCABAABBAgAAggQAAATlA8AfBwHosfcLAABIEgAAECQAACBIAABAkAAAgCABAABBAgAAggQAAAQJAAAIEgAAECQAACBIAABA0OveC6h7Oxye914DwB78gzz78gYAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAICg12v/w/fj8bTFAt4Oh+ctvi4AjGqrM/Xp6fpz9WIAbLlIAOC+fp/bl0LgbAA4+AFgXpdC4NPfA+DwB4A1nDvT/woAhz8ArOWzs/3l0n8AAMzvzzP+5dwPAABr+XjW+3sAACDo5enJd/8AUPH7zPcGAACCXnz3DwAt78fjyRsAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIOjl7XB43nsRAMDjvB0Oz94AAEDQy9PTrxLYeyEAwPZ+n/neAABA0P8DwFsAAFjbx7P+5dwPAADr+POM/+sjABEAAGv57Gz/9PcAiAAAWMO5M/310k94Px5PWy0KANjGpW/mzwbAn19ACADA+K59i38xAG79ggDA10Y4U/09AAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABL3uvYC69+PxtPcaAOjxBgAAggQAAAQJAAAIEgAAECQAACBIAABAkAAAgCABAABBAgAAggQAAAQJAAAIEgAAECQAACBIAABAkAAAgCABAABBAgAAggQAAAQJAAAIEgAAECQAACBIAABAkAAAgCABAABBAgAAggQAAAQJAAAIEgAAECQAACBIAABAkAAAgCABAABBr3svgO29HQ7P3/l578fj6d5rAR7L/HOOAFjUd4f+q69hQ4A53Hv+zf6aBMBC7jH013x9mwGMZ8v5FwNrEgAL2PrgP/fr2Qhgf+af7xIAE3v04J/79W0E8Hjmn5/ypwAmtffwfzTSWqBgpJkbaS3cRgBMaMSBG3FNsKIRZ23ENXGZjwAmMvqQeSUI2zH/3Js3AJMYffg/mmmtMIOZZmqmtdYJgAnMOFAzrhlGNOMszbjmIgEwuJkHaea1wwhmnqGZ114hAAAgSAAMbIWCXuEaYA8rzM4K17AyATColQZnpWuBR1hpZla6ltUIgAGtODArXhNsYcVZWfGaViAAACBIAAxm5VJe+drgHlaekZWvbVYCAACCBMBACoVcuEb4jsJsFK5xJgIAAIIEAA/nuwDoMv/jEACDMBTQZf7ZgwAAgCABAABBAmAAxdd/xWuGzxRnoXjNIxIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQJgAO/H42nvNTxa8ZrhM8VZKF7ziAQAAAQJAAAIEgCDKL0SK10r8F/mfxwCAGBnDkX2IAAAIEgADKTwXUDhGuE7CrNRuMaZCAAACBIAg1m5kFe+NriHlWdk5WublQAAgCABMKAVS3nFa4ItrDgrK17TCgTAoFYamJWuBR5hpZlZ6VpWIwAGtsLgrHANsIcVZmeFa1iZAACAIAEwuJkLeua1wwhmnqGZ114hACYw4yDNuGYY0YyzNOOaiwTAJGYaqJnWCjOYaaZmWmvd694L4Hq/B+vtcHjeey2fMfiwHfPPvXkDMKERB23ENcGKRpy1EdfEZQJgUiMN3EhrgYKRZm6ktXAbHwFMbO9XggYf9mP++SkBsIBHbwQGH8Zh/vkuAbCQj4O5xWZg8GFc5p9bCYBF3WMzMPAwJ/PPNQRAgEGGLvPPOf4UAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAILyATDqv60NwHbs/QIAAJIEAAAECYAnr4IASuz5vwgAAAgSAAAQJAD+4ZUQwPrs9f8SAAAQJAA+UIYA67LH/5cA+IMHBGA99va/CQAACBIAn1CKAOuwp39OAJzhgQGYn738PAHwBQ8OwLzs4V8TAAAQJAAuUJAA87F3X/Z8Op32XsM03o9HNwtgYA7+63kDcAMPFsC47NG3EQA38oABjMfefDsfAfyAjwQA9uXg/z5vAH7AgwewH3vwz3gDcCfeBgA8hoP/PgTABsQAwH059O9PAGxMDAB8j0N/WwJgZwJhXzaYNvO3L/O3L78JEACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAGws7fD4XnvNVS593gG9uPe708AAECQAACAIAEwAK/CHs895zfPwuO552MQAAAQJAAAIEgADMIrscdxr/mTZ+Jx3OtxCAAACBIAA1HG23OPOcezsT33eCwCYDAGZDvuLZd4Rrbj3o5HAABAkAAYkFK+P/eUa3lW7s89HZMAGJSBuR/3klt5Zu7HvRyXABiYwfk595Dv8uz8nHs4NgEAAEECYHAK+vvcO37KM/R97t34nk+n095r4Ervx6P/WVew8bAF83cd8zcPbwAmYrAuc4/YimfrMvdoLgJgMgbsPPeGrXnGznNv5uMjgIl5JfmLjYc9mL9fzN+8vAGYmMFzD9iPZ889mJ03AIuofTdi42Ek5o8ZCYAFrboZ2XSYgfljFgJgcbNvRjYdZmb+GJkAAIAgvwkQAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAICg/wEsbr+uWsUXzgAAAABJRU5ErkJggg==" alt="Transportation" class="transportation-icon" style="height: 16px; width: auto; margin-left: 4px; vertical-align: middle;">
										</span>
										<br>
										{{ printTravelDates(booking.rooms, guest) }}
									</li>
								</template>
							</ul>
						</td>
						<td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
							<div v-for="(room, index) in booking.rooms" :key="room.id">
								{{ room.name + ' - ' + room.hotel.name }}: {{ room.pivot.bed }} <br>
								{{ $moment.utc(room.pivot.check_in).format('MMM DD') }} - {{ $moment.utc(room.pivot.check_out).format('MMM DD') }}
								({{ $moment.utc(room.pivot.check_out).diff($moment.utc(room.pivot.check_in), 'days') }} nights) 
								<span v-if="index !== booking.rooms.length - 1">
									<br><br>
								</span>
							</div>
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
							<template v-if="booking.bookingId">
								{{ booking.bookingId }}
							</template>
							<template v-else>
								-
							</template>
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
							<a v-if="!group.deletedAt && (booking.can.view || booking.can.update)" class="table-action" :class="{'has-text-danger': booking.pendingChanges || booking.guestChanges.length > 0}" @click.prevent="show(booking.id)">
								<i class="fas fa-info-circle"></i>
							</a>
							<a v-if="!group.deletedAt && (booking.can.view || booking.can.update)" class="table-action" :class="{'has-text-danger': booking.pendingChanges || booking.guestChanges.length > 0}" :href="`${$dashboardBase}/groups/${group.id}/bookings/${booking.id}`" target="_blank">
								<i class="fas fa-external-link-alt"></i>
							</a>
							<a v-if="!group.deletedAt && booking.can.confirm" class="table-action" @click.prevent="confirmBooking = {booking: booking, provider: group.provider}">
								<i class="fas fa-check"></i>
							</a>
							<a v-if="!group.deletedAt && booking.can.delete" class="table-action" @click.prevent="deleteBooking = booking">
								<i class="fas fa-trash"></i>
							</a>
							<template v-else>
								<a v-if="!group.deletedAt && booking.can.restore" class="table-action" @click.prevent="restoreBooking = booking">
									<i class="fas fa-trash-restore"></i>
								</a>
								<a v-if="!group.deletedAt && booking.can.forceDelete" class="table-action" @click.prevent="forceDeleteBooking = booking">
									<i class="fas fa-trash"></i>
								</a>
							</template>
							<table-actions v-if="!group.deletedAt" :has-notifications="booking.can.viewPayments && !!booking.pendingPayments">
								<div class="dropdown-item" v-if="booking.can.viewClients">
									<router-link :to="{ name: 'clients', params: {group: group.id, booking: booking.id} }" class="table-action">
										View Clients
									</router-link>
								</div>
								<div class="dropdown-item" v-if="booking.can.viewPayments">
									<router-link :to="{ name: 'payments', params: {group: group.id, booking: booking.id} }" class="table-action">
										View Payments
										<span v-if="booking.pendingPayments" class="notification-counter is-text">
											{{ booking.pendingPayments }}
										</span>
									</router-link>
								</div>
								<template v-if="!group.fit || (group.fit && (booking.can.confirm || booking.confirmedAt))">
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
			<delete-booking v-if="deleteBooking" :booking="deleteBooking" :group="group" @deleted="deleted" @canceled="deleteBooking = null" />
			<restore-booking v-if="restoreBooking" :booking="restoreBooking" @restored="restoredBooking" @canceled="restoreBooking = null" />
			<force-delete-booking v-if="forceDeleteBooking" :booking="forceDeleteBooking" :group="group" @forceDeleted="forceDeleted" @canceled="forceDeleteBooking = null" />
		</template>
		<template v-if="tabs.dueDates">
			<form-field label="Cancellation Date" :errors="dueDatesErrors.cancellationDate">
				<date-picker
					v-model="dueDates.cancellationDate"
					:max-date="group.eventDate"
					:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
				>
					<template v-slot="{ inputValue, inputEvents }">
						<input
							:class="'input' + ((dueDatesErrors.cancellationDate || []).length ? ' is-danger' : '')"
							:readonly="readonly"
							:value="inputValue"
							v-on="inputEvents"
						/>
					</template>
				</date-picker>
			</form-field>
			<form-field label="Balance Due Date" :errors="dueDatesErrors.dueDate">
				<date-picker
					v-model="dueDates.dueDate"
					:max-date="group.eventDate"
					:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
				>
					<template v-slot="{ inputValue, inputEvents }">
						<input
							:class="'input' + ((dueDatesErrors.dueDate || []).length ? ' is-danger' : '')"
							:readonly="readonly"
							:value="inputValue"
							v-on="inputEvents"
						/>
					</template>
				</date-picker>
			</form-field>
			<form-panel label="Other Due Dates" class="is-borderless">
				<template v-slot:action>
					<control-button class="is-small is-link is-outlined" @click="dueDates.other.push({type: 'price'})">
						<i class="fas fa-plus"></i>
					</control-button>
				</template>
				<form-panel v-for="(dueDate, index) in dueDates.other" :key="index">
					<template v-slot:action>
						<control-button class="is-small is-link is-outlined" @click="dueDates.other.splice(index, 1)">
							<i class="fas fa-minus"></i>
						</control-button>
					</template>
					<form-field label="Date" :errors="dueDatesErrors['other.' + index + '.date'] || []">
						<date-picker
							v-model="dueDate.date"
							:max-date="group.eventDate"
							:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
						>
							<template v-slot="{ inputValue, inputEvents }">
								<input
									:class="'input' + ((dueDatesErrors['other.' + index + '.date'] || []).length ? ' is-danger' : '')"
									:readonly="readonly"
									:value="inputValue"
									v-on="inputEvents"
								/>
							</template>
						</date-picker>
					</form-field>
					<form-field label="Amount" :errors="[...(dueDatesErrors['other.' + index + '.amount'] || []), ...(dueDatesErrors['other.' + index + '.type'] || [])]">
						<control-input v-model="dueDate.amount" :class="{ 'is-danger': (dueDatesErrors['other.' + index + '.amount'] || []).length }" />
						<template v-slot:addon>
							<control-select v-model="dueDate.type" :options="dueDateTypeOptions" :class="{ 'is-danger': (dueDatesErrors['other.' + index + '.amount'] || []).length }" />
						</template>
					</form-field>
				</form-panel>
			</form-panel>
			<control-button v-if="!readonly && !group.deletedAt" @click="syncDueDates" class="is-primary" :class="{ 'is-loading': isLoading === 'syncDueDates' }">Save</control-button>
		</template>
	</card>
</template>

<script>
	import Card from '@dashboard/components/Card';
	import ConfirmBooking from '@dashboard/pages/Groups/Bookings/Confirm';
	import CreateBooking from '@dashboard/pages/Groups/Bookings/Create';
	import DataTable from '@dashboard/components/table/Table';
	import DeleteBooking from '@dashboard/pages/Groups/Bookings/Delete';
	import ForceDeleteBooking from '@dashboard/pages/Groups/Bookings/ForceDelete';
	import Modal from '@dashboard/components/Modal';
	import RestoreBooking from '@dashboard/pages/Groups/Bookings/Restore';
	import TableActions from '@dashboard/components/table/Actions';
	import Tab from '@dashboard/components/tabs/Tab';
	import Tabs from '@dashboard/components/tabs/Tabs';
	import ControlButton from '@dashboard/components/form/controls/Button';
	import FormPanel from '@dashboard/components/form/Panel';
	import DatePicker from 'v-calendar/lib/components/date-picker.umd';
	import FormField from '@dashboard/components/form/Field';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';
	import RestoreGroup from '@dashboard/pages/Groups/Restore';
	import VSelect from 'vue-select';
	import 'vue-select/dist/vue-select.css';

	export default {
		components: {
			Card,
			ConfirmBooking,
			CreateBooking,
			DataTable,
			DeleteBooking,
			ForceDeleteBooking,
			Modal,
			RestoreBooking,
			TableActions,
			Tab,
			Tabs,
			ControlButton,
			FormPanel,
			DatePicker,
			FormField,
			ControlInput,
			ControlSelect,
			ControlTextarea,
			RestoreGroup,
			VSelect
		},
		data() {
			return {
				bookings: [],
				group: {},
				countries: [],
				can: {},
				filters: {},
				roomCategories: [],
				message: {
					title: '',
					body: '',
					bookingId: null,
					edit: false,
					show: false,
					notesErrors: [],
				},
				confirmBooking: null,
				deleteBooking: null,
				restoreBooking: null,
				forceDeleteBooking: null,
				tabs: {
					bookings: true,
					dueDates: false
				},
				dueDates: [],
				dueDatesErrors: [],
				isLoading: '',
				showRestore: false,
				bookings_count: 0,
				nights_count: 0,
				average_nights: 0,
				most_common_nights: 0,
				total_adults_pax: 0,
			}
		},
		created() {
			const query = { ...this.$route.query };

			if (query.room_category) {
				query.room_category = Array.isArray(query.room_category)
					? query.room_category.map(v => Number(v))
					: [Number(query.room_category)];
			}

			this.filters = Object.assign({}, this.filters, query);

			this.fetchData();
		},
		computed: {
			readonly() {
				return !this.group.can.update;
			},
			query() {
				return '?' + Object.keys(this.filters).map(key => key + '=' + this.filters[key]).join('&');
			},
			couplesPage() {
				return `${process.env.MIX_GROUP_URL}/${this.group.slug}`;
			},
			dueDateTypeOptions() {
				const baseOptions = [
					{ value: 'price', text: '$' },
					{ value: 'percentage', text: '%' }
				];

				if (!this.group.fit) {
					baseOptions.push({ value: 'nights', text: 'Nights' });
				}

				return baseOptions;
			},
			roomCategoryOptions() {
				return this.roomCategories.map(category => ({
					value: category.id,
					text: category.name
				}));
			},
      coupleBookingId(){
        return this.bookings.find(booking => booking.isBgCouple)?.id ?? null; 
      }
		},
		methods: {
			fetchData() {
				this.$http.get('/groups/' + this.$route.params.group + '/bookings' + this.query)
					.then(response => {
						this.group = response.data.group;
						this.bookings = response.data.data;					
						let activeBookings = this.bookings.filter(booking => booking.deletedAt === null);

						this.nights_count = activeBookings.reduce((total, booking) => {
							return total + booking.rooms.reduce((roomTotal, room) => {
								return roomTotal + this.$moment.utc(room.pivot.check_out).diff(this.$moment.utc(room.pivot.check_in), 'days');
							}, 0);
						}, 0);

						let nightCounts = {};
						let maxCount = 0;

						activeBookings.forEach(booking => {
								booking.rooms.forEach(room => {
										let nights = this.$moment.utc(room.pivot.check_out).diff(this.$moment.utc(room.pivot.check_in), 'days');
										nightCounts[nights] = (nightCounts[nights] || 0) + 1;
								});
						});

						for (let nights in nightCounts) {
								if (nightCounts[nights] > maxCount) {
										maxCount = nightCounts[nights];
										this.most_common_nights = parseInt(nights);
								}
						}

						this.bookings_count = activeBookings.length;
						this.average_nights = this.bookings_count > 0 ? (this.nights_count / this.bookings_count).toFixed(2) : '0';
						this.total_adults_pax = response.data.total_adult_pax;
						this.countries = response.data.countries;
						this.can = response.data.can;
						this.roomCategories = response.data.room_categories;

						this.dueDates = {
							dueDate: this.$moment(this.group.dueDate).toDate(),
							cancellationDate: this.$moment(this.group.cancellationDate).toDate(),

							other: this.group.dueDates.map(dueDate => ({
								date: this.$moment(dueDate.date).toDate(),
								amount: dueDate.amount,
								type: dueDate.type
							}))
						};

						this.setBreadcrumbs();
					});
			},
			filterData(page = '1') {
				this.filters.page = page;

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
						label: !this.group.deletedAt ? 'Groups' : 'Deleted Groups',
						route: !this.group.deletedAt ? 'groups' : 'trash'
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
					}
				]);
			},
			setTab(tab) {
				Object.keys(this.tabs).forEach(key => this.tabs[key] = false);
				this.tabs[tab] = true;
			},
			show(id) {
				this.$router.push({
					name: 'bookings.show',
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
			move(direction, index) {
				this.$http.patch('/groups/' + this.$route.params.group + '/bookings/' + this.bookings[index].id + '/move/' + direction)
					.then(() => {
						const targetIndex = direction == 'up' ? (index - 1) : (index + 1);
						const oldOrder = this.bookings[index].order;
						const newOrder = this.bookings[targetIndex].order;
						this.bookings[index].order = newOrder;
						this.bookings[targetIndex].order = oldOrder;
						this.bookings.splice((direction == 'up' ? targetIndex : targetIndex + 1), 0, this.bookings[index]);
						this.bookings.splice((direction == 'up' ? index + 1 : index), 1);
					}).catch(() => {
						this.$store.commit('notification', {
							type: 'danger',
							message: 'Something went wrong when trying to update the order of the bookings.',
						});
					});
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

				if (this.group.fit) {
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
				}

				return status;
			},
			getAge(birthDate) {
				return this.$moment(this.group.eventDate).diff(birthDate, 'years');
			},
			printDateOfBirthIfChild(birthDate) {
				let age = this.getAge(birthDate);

				return (age < 18) ? ` (${this.$moment(birthDate).format('MM/DD/YYYY')})` : '';
			},
			printTravelDates(rooms, guest) {
				const pivotDates = rooms.map(room => ({
					checkIn: this.$moment.utc(room.pivot.check_in),
					checkOut: this.$moment.utc(room.pivot.check_out),
				}));

				const minCheckIn = this.$moment.min(pivotDates.map(d => d.checkIn).filter(Boolean)).format('MM/DD/YYYY');
				const maxCheckOut = this.$moment.max(pivotDates.map(d => d.checkOut).filter(Boolean)).format('MM/DD/YYYY');
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

				this.$http.patch(`/groups/${this.$route.params.group}/bookings/${this.message.bookingId}/update-notes`, {
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
			syncDueDates() {
				this.isLoading = 'syncDueDates';
				this.dueDates.dueDate = this.dueDates.dueDate instanceof Date ? this.dueDates.dueDate.toDateString() : this.dueDates.dueDate;
				this.dueDates.cancellationDate = this.dueDates.cancellationDate instanceof Date ? this.dueDates.cancellationDate.toDateString() : this.dueDates.cancellationDate;

				this.dueDates.other = this.dueDates.other.map(otherDueDate => {
					if (otherDueDate.date instanceof Date) {
						otherDueDate.date = otherDueDate.date.toDateString();
					}

					return otherDueDate;
				});

				let request = this.$http.patch('/groups/' + this.group.id + '/due-dates', this.dueDates)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The due dates have been updated.'
						});

						this.dueDatesErrors = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.dueDatesErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			restoredGroup() {
				window.location.href = '/groups/' + this.group.id;
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
	.transportation-icon {
		display: inline-block;
		opacity: 0.7;
	}

  .stat-card {
		border: 1px solid $dusty-rose;
		padding: 12px;
		margin-bottom: 8px;
		border-radius: 4px;
		background: $dusty-rose;
		color: $charcoal !important;
		display: flex;
		align-items: center;
  }

  .stat-label {
		margin-right: 8px;
  }

  .has-content-end {
    justify-content: end !important;
  }
</style>
