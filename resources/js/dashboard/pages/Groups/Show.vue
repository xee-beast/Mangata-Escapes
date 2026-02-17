<template>
	<card v-if="savedGroup">
		<template v-slot:action v-if="savedGroup.can.viewAccomodations || savedGroup.can.viewBookings || savedGroup.can.delete">
			<a v-if="!savedGroup.deletedAt" @click.prevent="showEmailModal = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-envelope"></i></span>
				<span>Send Email</span>
			</a>
			<a :href="savedGroup.bookingsExportUrl" target="_blank" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-file-invoice"></i></span>
				<span>Export Bookings</span>
			</a>
			<a :href="savedGroup.flightManifestsExportUrl" target="_blank" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-file-invoice"></i></span>
				<span>Export Flight Manifests</span>
			</a>
			<a v-if="!savedGroup.deletedAt" @click.prevent="showReviewModal = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-file-excel"></i></span>
				<span>Review Group</span>
			</a>
			<router-link v-if="savedGroup.can.viewAccomodations" :to="{ name: 'accomodations', params: { group: savedGroup.id }}" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-door-open"></i></span>
				<span>Accommodations</span>
			</router-link>
			<router-link v-if="savedGroup.can.viewBookings" :to="{ name: 'bookings', params: { group: savedGroup.id }}" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-book-open"></i></span>
				<span>Bookings</span>
				<span v-if="savedGroup.pendingBookings" class="notification-counter">
					{{ savedGroup.pendingBookings }}
				</span>
			</router-link>
			<template v-if="savedGroup.can.delete && !savedGroup.deletedAt">
				<a @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
					<span class="icon"><i class="fas fa-trash"></i></span>
				</a>
				<delete-group v-if="showDelete" :group="savedGroup" @deleted="deleted" @canceled="showDelete = false" />
			</template>
			<template v-if="savedGroup.can.delete && savedGroup.deletedAt">
				<a @click.prevent="showRestore = true" class="button is-outlined is-primary is-inverted">
					<span class="icon"><i class="fas fa-trash-restore"></i></span>
					<span>Restore Group</span>
				</a>
				<restore-group v-if="showRestore" :group="savedGroup" @restored="restored" @canceled="showRestore = false" />
			</template>
		</template>
		<template v-slot:tabs>
			<tabs class="is-boxed">
				<tab @click="setTab('info')" :is-active="tabs.info">Group</tab>
				<tab @click="setTab('dueDates')" :is-active="tabs.dueDates">Due Dates</tab>
				<tab v-if="false" @click="setTab('pastBride')" :is-active="tabs.pastBride">Past Bride</tab>
				<tab @click="setTab('attrition')" :is-active="tabs.attrition">Attrition</tab>
				<tab @click="setTab('faqs')" :is-active="tabs.faqs">FAQs</tab>
				<tab @click="setTab('termsConditions')" :is-active="tabs.termsConditions">Terms & Conditions</tab>
			</tabs>
		</template>
		<template v-if="tabs.info">
			<div class="columns">
				<div class="column">
					<form-field label="Destination" :errors="groupErrors.destination" :required="true">
						<control-select v-model="destination" :options="destinations" :class="{ 'is-danger': (groupErrors.destination || []).length }" :readonly="readonly || savedGroup.hasBookings" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Wedding Location" :errors="groupErrors.weddingLocation" :required="true">
						<control-radio v-model="group.weddingLocation" :readonly="readonly" :options="[{text: 'Resort', value: 'resort'}, {text: 'Venue', value: 'venue'}]" />
					</form-field>
				</div>
			</div>
			<form-field v-if="group.weddingLocation == 'venue'" label="Venue Name" :errors="groupErrors.venueName" :required="true">
				<control-input v-model="group.venueName" :readonly="readonly" :class="{ 'is-danger': (groupErrors.venueName || []).length }" />
			</form-field>
			<form-field label="Event Date" :errors="groupErrors.eventDate" :required="true">
				<date-picker v-model="group.eventDate" :popover="{ visibility: (readonly ? 'hidden' : 'focus') }">
					<template v-slot="{ inputValue, inputEvents }">
						<input
							:class="'input' + ((groupErrors.eventDate || []).length ? ' is-danger' : '')"
							:readonly="readonly"
							:value="inputValue"
							v-on="inputEvents"
						/>
					</template>
				</date-picker>
			</form-field>
			<div class="columns">
				<div class="column">
					<form-field label="Bride's First Name" :errors="groupErrors.brideFirstName" :required="true">
						<control-input v-model="group.brideFirstName" class="is-capitalized" :class="{ 'is-danger': (groupErrors.brideFirstName || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Bride's Last Name" :errors="groupErrors.brideLastName" :required="true">
						<control-input v-model="group.brideLastName" class="is-capitalized" :class="{ 'is-danger': (groupErrors.brideLastName || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Groom's First Name" :errors="groupErrors.groomFirstName" :required="true">
						<control-input v-model="group.groomFirstName" class="is-capitalized" :class="{ 'is-danger': (groupErrors.groomFirstName || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Groom's Last Name" :errors="groupErrors.groomLastName" :required="true">
						<control-input v-model="group.groomLastName" class="is-capitalized" :class="{ 'is-danger': (groupErrors.groomLastName || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<form-field label="Email" :errors="groupErrors.email" :required="true">
				<control-input v-model="group.email" class="is-lowercase" :class="{ 'is-danger': (groupErrors.email || []).length }" :readonly="readonly" />
			</form-field>
      <form-field label="Secondary Email" :errors="groupErrors.secondaryEmail">
        <control-input v-model="group.secondaryEmail" class="is-lowercase" :class="{ 'is-danger': (groupErrors.secondaryEmail || []).length }" :readonly="readonly" />
      </form-field>
			<form-field label="Group Leader Password" :errors="groupErrors.password" :required="true">
				<div class="field has-addons">
					<div class="control is-expanded">
						<control-input v-model="group.password" :class="{ 'is-danger': (groupErrors.password || []).length }" :readonly="readonly" />
					</div>
					<div class="control">
						<control-button v-if="!readonly" class="button is-primary" @click="groupLeaderEmail = true">Email Group Leader Credentials</control-button>
					</div>
				</div>
			</form-field>
			<div class="columns">
				<div class="column">
					<form-field label="Slug" :errors="groupErrors.slug" :required="true">
						<control-input v-model="group.slug" :class="{ 'is-danger': (groupErrors.slug || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Show Couple Site" :errors="groupErrors.isActive">
						<control-radio v-model="group.isActive" :readonly="readonly" :options="[{text: 'Yes', value: true}, {text: 'No', value: false}]" />
					</form-field>
				</div>
			</div>
			<form-field label="Couples Site Password" :errors="groupErrors.couplesSitePassword">
				<div class="field has-addons">
					<div class="control is-expanded">
						<control-input v-model="group.couplesSitePassword" :class="{ 'is-danger': (groupErrors.couplesSitePassword || []).length }" :readonly="readonly" />
					</div>
					<div class="control">
						<control-button v-if="!readonly" class="button is-primary" @click="couplesSitePasswordEmail = true">Email Couples Site Password</control-button>
					</div>
				</div>
			</form-field>
			<form-field label="Image" :errors="groupErrors.image">
				<image-uploader v-model="group.image" @errors="$set(groupErrors, 'image', $event)" :class="{ 'is-danger': (groupErrors.image || []).length }" :max-size="1024" is-single :disabled="readonly" />
			</form-field>
			<form-field label="Couple's Message" :errors="groupErrors.message">
				<control-textarea v-model="group.message" :class="{ 'is-danger': (groupErrors.message || []).length }" :readonly="readonly" />
			</form-field>
			<form-field label="Travel Agent" :errors="groupErrors.agent" :required="true">
				<control-select v-model="group.agent" :options="agents" :class="{ 'is-danger': (groupErrors.agent || []).length }" :readonly="readonly" />
			</form-field>
			<form-field label="Supplier" :errors="groupErrors.provider" :required="true">
				<control-select v-model="group.provider" :options="providers" :class="{ 'is-danger': (groupErrors.provider || []).length }" :readonly="readonly" />
			</form-field>
			<form-field label="Supplier ID" :errors="groupErrors.providerId" :required="true">
				<control-input v-model="group.providerId" :class="{ 'is-danger': (groupErrors.providerId || []).length }" :readonly="readonly" />
			</form-field>
			<div class="columns">
				<div class="column">
					<form-field label="Fallback Insurance Rate" :errors="groupErrors.insuranceRate" :required="true">
						<control-select v-model="group.insuranceRate" :options="insuranceRates.filter(rate => rate.provider == (group.provider || 0))" first-is-empty :class="{ 'is-danger': (groupErrors.insuranceRate || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Force fallback insurance rate for this group?" :errors="groupErrors.useFallbackInsurance" :required="true">
						<control-radio v-model="group.useFallbackInsurance" :options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]" default="total" :class="{ 'is-danger': (groupErrors.useFallbackInsurance || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Transportation" :errors="groupErrors.transportation" :required="true">
						<control-radio v-model="group.transportation"  :options="[{text: 'Yes', value: true}, {text: 'No', value: false}]" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<template v-if="group.transportation">
				<div v-if="isLoading === 'fetchingDefaultRates'" style="position: relative; min-height: 200px;">
					<loader />
				</div>
				<form-field v-else-if="airportOptions.length > 0" label="Airports" class="is-borderless">
					<div v-for="(airport, index) in group.airports" class="is-mobile is-variable is-1 group-airport-section">
						<div class="columns">
							<div class="column">
								<form-field label="Airport" :errors="groupErrors['airports.' + index + '.airport']" :required="true">
									<control-select :readonly="readonly" v-model="airport.airport" :options="airportOptions" first-is-empty :class="{ 'is-danger': (groupErrors['airports.' + index + '.airport'] || []).length }"/>
								</form-field>
							</div>
							<div class="column">
								<form-field label="Transfer Provider" :errors="groupErrors['airports.' + index + '.transfer']">
									<control-select :readonly="readonly" v-model="airport.transfer" :options="transfers" first-is-empty :class="{ 'is-danger': (groupErrors['airports.' + index + '.transfer'] || []).length }" />
								</form-field>
							</div>
						</div>
						<div class="columns">
							<div class="column">
								<form-field label="Transportation Rate" :errors="groupErrors['airports.' + index + '.transportationRate']" :required="true">
									<control-input :readonly="readonly" v-model="airport.transportationRate" :class="{ 'is-danger': (groupErrors['airports.' + index + '.transportationRate'] || []).length }" />
								</form-field>
							</div>
							<div class="column">
								<form-field label="Single Transp. Rate" :errors="groupErrors['airports.' + index + '.singleTransportationRate']" :required="true">
									<control-input :readonly="readonly" v-model="airport.singleTransportationRate" :class="{ 'is-danger': (groupErrors['airports.' + index + '.singleTransportationRate'] || []).length }" />
								</form-field>
							</div>
							<div class="column">
								<form-field label="One Way Transp. Rate" :errors="groupErrors['airports.' + index + '.oneWayTransportationRate']" :required="true">
									<control-input :readonly="readonly" v-model="airport.oneWayTransportationRate" :class="{ 'is-danger': (groupErrors['airports.' + index + '.oneWayTransportationRate'] || []).length }" />
								</form-field>
							</div>
							<div v-if="index && !readonly" class="column is-narrow align-self-last-baseline">
								<control-button class="is-link is-outlined" @click="group.airports.splice(index, 1)">
									<i class="fas fa-minus"></i>
								</control-button>
							</div>
						</div>
						<div class="columns">
							<div class="column">
								<form-field :errors="groupErrors['airports.' + index + '.default']">
									<label class="checkbox" :class="{ 'is-danger': (groupErrors['airports.' + index + '.default'] || []).length }">
										<input :disabled="readonly" type="checkbox" v-model="airport.default">
										Default
									</label>
								</form-field>
							</div>
						</div>
					</div>
					<div style="min-height: 32px;" v-if="!readonly">
						<a v-if="group.airports[group.airports.length - 1].oneWayTransportationRate" @click.prevent="group.airports.push({airport: 0, default: 0})" class="has-text-mauve">
							+ Add another airport
						</a>
					</div>
				</form-field>
				<div class="columns">
					<div class="column">
						<form-field label="Transportation Type" :errors="groupErrors.transportationType" :required="true">
							<control-select v-model="group.transportationType" :options="[{value: 'private', text: 'Private'}, {value: 'shared', text: 'Shared'}]" :class="{ 'is-danger': (groupErrors.transportationType || []).length }" :readonly="readonly" />
						</form-field>
					</div>
					<div class="column">
						<form-field label="Submit Flight Itinerary Before" :errors="groupErrors.transportationSubmitBefore" :required="true">
							<date-picker
								v-model="group.transportationSubmitBefore"
								:max-date="savedGroup.eventDate"
								:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
							>
								<template v-slot="{ inputValue, inputEvents }">
									<input
										:class="'input' + ((groupErrors.transportationSubmitBefore || []).length ? ' is-danger' : '')"
										:readonly="readonly"
										:value="inputValue"
										v-on="inputEvents"
									/>
								</template>
							</date-picker>
						</form-field>
					</div>
				</div>
			</template>
			<div class="columns">
				<div class="column">
					<form-field label="Minimum Nights" :errors="groupErrors.minNights" :required="true">
						<control-input v-model="group.minNights" type="number" :class="{ 'is-danger': (groupErrors.minNights || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Minimum Deposit" :errors="[...(groupErrors.deposit || []), ...(groupErrors.depositType || [])]" :required="true">
						<control-input v-model="group.deposit" :class="{ 'is-danger': (groupErrors.deposit || []).length }" :readonly="readonly" />
						<template v-slot:addon>
							<control-select
								v-model="group.depositType"
								:options="depositTypeOptions"
								:class="{ 'is-danger': (groupErrors.depositType || []).length }" :readonly="readonly"
							/>
						</template>
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Change Fee Date" :errors="groupErrors.changeFeeDate" :required="true">
						<date-picker
							v-model="group.changeFeeDate"
							:max-date="savedGroup.eventDate"
							:popover="{ visibility: (readonly ? 'hidden' : 'focus') }"
						>
							<template v-slot="{ inputValue, inputEvents }">
								<input
									:class="'input' + ((groupErrors.changeFeeDate || []).length ? ' is-danger' : '')"
									:readonly="readonly"
									:value="inputValue"
									v-on="inputEvents"
								/>
							</template>
						</date-picker>
					</form-field>
				</div>
				<div class="column">
					<form-field label="Change Fee Amount" :errors="[...(groupErrors.changeFeeAmount || [])]" :required="true">
						<control-input v-model="group.changeFeeAmount" :class="{ 'is-danger': (groupErrors.changeFeeAmount || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<form-field label="Notes" :errors="groupErrors.notes">
				<control-textarea v-model="group.notes" :class="{ 'is-danger': (groupErrors.notes || []).length }" :readonly="readonly" />
			</form-field>
			<form-field label="Banner Message" :errors="groupErrors.bannerMessage">
				<control-textarea v-model="group.bannerMessage" :class="{ 'is-danger': (groupErrors.bannerMessage || []).length }" :readonly="readonly" />
			</form-field>
			<form-field label="Dashboard Message" :errors="groupErrors.staffMessage">
				<control-textarea v-model="group.staffMessage" :class="{ 'is-danger': (groupErrors.staffMessage || []).length }" :readonly="readonly" />
			</form-field>
			<div class="columns">
				<div class="column">
					<form-field :errors="groupErrors.disableInvoiceSplitting">
						<label class="checkbox" :class="{ 'is-danger': (groupErrors.disableInvoiceSplitting || []).length }">
							<input :disabled="readonly" type="checkbox" v-model="group.disableInvoiceSplitting">
							Disable Invoice Splitting
						</label>
					</form-field>
				</div>
				<div class="column">
					<form-field :errors="groupErrors.disableNotifications">
						<label class="checkbox" :class="{ 'is-danger': (groupErrors.disableNotifications || []).length }">
							<input :disabled="readonly" type="checkbox" v-model="group.disableNotifications">
							Disable Group Notifications
						</label>
					</form-field>
				</div>
				<div class="column">
					<form-field v-if="!savedGroup.deletedAt">
						<control-switch 
							:value="savedGroup.acceptsNewBookings"
							@input="toggleBookingAcceptance"
							:title="savedGroup.acceptsNewBookings ? 'Click to stop accepting new bookings' : 'Click to start accepting new bookings'"
						>
							{{ savedGroup.acceptsNewBookings ? 'Accepting New Bookings' : 'Not Accepting New Bookings' }}
						</control-switch>
					</form-field>
				</div>
			</div>
			<control-button v-if="!readonly && !savedGroup.deletedAt" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
			<a v-if="savedGroup.can.delete && savedGroup.deletedAt" @click.prevent="showRestore = true" class="button is-primary">
				<span class="icon"><i class="fas fa-trash-restore"></i></span>
				<span>Restore Group</span>
			</a>
		</template>
		<template v-if="tabs.dueDates">
			<form-field label="Cancellation Date" :errors="dueDatesErrors.cancellationDate" :required="true">
				<date-picker
					v-model="dueDates.cancellationDate"
					:max-date="savedGroup.eventDate"
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
			<form-field label="Balance Due Date" :errors="dueDatesErrors.dueDate" :required="true">
				<date-picker
					v-model="dueDates.dueDate"
					:max-date="savedGroup.eventDate"
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
					<control-button v-if="!readonly" class="is-small is-link is-outlined" @click="dueDates.other.push({type: 'price'})">
						<i class="fas fa-plus"></i>
					</control-button>
				</template>
				<form-panel v-for="(dueDate, index) in dueDates.other" :key="index">
					<template v-if="!readonly" v-slot:action>
						<control-button class="is-small is-link is-outlined" @click="dueDates.other.splice(index, 1)">
							<i class="fas fa-minus"></i>
						</control-button>
					</template>
					<form-field label="Date" :errors="dueDatesErrors['other.' + index + '.date'] || []" :required="true">
						<date-picker
							v-model="dueDate.date"
							:max-date="savedGroup.eventDate"
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
					<form-field label="Amount" :errors="[...(dueDatesErrors['other.' + index + '.amount'] || []), ...(dueDatesErrors['other.' + index + '.type'] || [])]" :required="true">
						<control-input v-model="dueDate.amount" :readonly="readonly" :class="{ 'is-danger': (dueDatesErrors['other.' + index + '.amount'] || []).length }" />
						<template v-slot:addon>
							<control-select
								v-model="dueDate.type"
								:options="dueDateTypeOptions"
								:class="{ 'is-danger': (dueDatesErrors['other.' + index + '.amount'] || []).length }"
								:readonly="readonly"
							/>
						</template>
					</form-field>
				</form-panel>
			</form-panel>
			<control-button v-if="!readonly && !savedGroup.deletedAt" @click="syncDueDates" class="is-primary" :class="{ 'is-loading': isLoading === 'syncDueDates' }">Save</control-button>
			<a v-if="savedGroup.can.delete && savedGroup.deletedAt" @click.prevent="showRestore = true" class="button is-primary">
				<span class="icon"><i class="fas fa-trash-restore"></i></span>
				<span>Restore Group</span>
			</a>
		</template>
		<template v-if="tabs.pastBride">
			<form-field label="Message" :errors="pastBrideErrors.message">
				<control-textarea v-model="pastBride.message" :class="{ 'is-danger': (pastBrideErrors.message || []).length }" :readonly="readonly" />
			</form-field>
			<form-field>
				<label class="checkbox">
					<input v-model="pastBride.show" :disabled="readonly" type="checkbox">
					Display On Site.
				</label>
			</form-field>
			<control-button v-if="!readonly && !savedGroup.deletedAt" @click="updatePastBride" class="is-primary" :class="{ 'is-loading': isLoading === 'updatePastBride' }">Save</control-button>
			<a v-if="savedGroup.can.delete && savedGroup.deletedAt" @click.prevent="showRestore = true" class="button is-primary">
				<span class="icon"><i class="fas fa-trash-restore"></i></span>
				<span>Restore Group</span>
			</a>
		</template>
		<template v-if="tabs.attrition">
			<img v-if="savedGroup.attritionImage && savedGroup.attritionImage.storagePath" :src="savedGroup.attritionImage.storagePath" class="image is-128x128" />
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
												:class="'input' + ((dueDatesErrors['attrition.' + index + '.date'] || []).length ? ' is-danger' : '')"
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
			<control-button v-if="!readonly && !savedGroup.deletedAt" @click="syncAttrition" class="is-primary" :class="{ 'is-loading': isLoading === 'syncAttrition' }">Save</control-button>
			<a v-if="savedGroup.can.delete && savedGroup.deletedAt" @click.prevent="showRestore = true" class="button is-primary">
				<span class="icon"><i class="fas fa-trash-restore"></i></span>
				<span>Restore Group</span>
			</a>
		</template>
		<template v-if="tabs.faqs">
			<form-panel label="FAQs" class="is-borderless">
				<template v-slot:action>
					<control-button v-if="!readonly" class="is-small is-link is-outlined" @click="faqs.push({title: '', description: '', type: 'static'})">
						<i class="fas fa-plus"></i>
					</control-button>
				</template>
        <form-panel v-for="(faq, index) in faqs" :key="index">
					<template v-if="!readonly" v-slot:action>
						<control-button class="is-small is-link is-outlined" @click="faqs.splice(index, 1)">
							<i class="fas fa-minus"></i>
						</control-button>
					</template>
					<form-field label="Title" :errors="faqsErrors['faqs.' + index + '.title'] || []" :required="true">
						<control-input v-model="faq.title" :class="{ 'is-danger': (faqsErrors['faqs.' + index + '.title'] || []).length }" :readonly="(faq.type === 'dynamic') || readonly" />
					</form-field>
					<form-field label="Description" :errors="faqsErrors['faqs.' + index + '.description'] || []">
						<control-editor v-model="faq.description" :class="{ 'is-danger': (faqsErrors['faqs.' + index + '.description'] || []).length }" :readonly="(faq.type === 'dynamic') || readonly" />                                    
					</form-field>
				</form-panel>
    	</form-panel>
			<control-button v-if="!readonly && !savedGroup.deletedAt" @click="updateFaqs" class="is-primary" :class="{ 'is-loading': isLoading === 'updateFaqs' }">Save</control-button>
			<a v-if="savedGroup.can.delete && savedGroup.deletedAt" @click.prevent="showRestore = true" class="button is-primary">
				<span class="icon"><i class="fas fa-trash-restore"></i></span>
				<span>Restore Group</span>
			</a>
		</template>
		<template v-if="tabs.termsConditions">
			<form-field label="Terms & Conditions" :errors="groupErrors.termsAndConditions">
				<control-editor v-model="group.termsAndConditions" :class="{ 'is-danger': (groupErrors.termsAndConditions || []).length }" :readonly="readonly" />
			</form-field>
			<control-button v-if="!readonly && !savedGroup.deletedAt" @click="updateTermsConditions" class="is-primary" :class="{ 'is-loading': isLoading === 'updateTermsConditions' }">Save</control-button>
			<a v-if="savedGroup.can.delete && savedGroup.deletedAt" @click.prevent="showRestore = true" class="button is-primary">
				<span class="icon"><i class="fas fa-trash-restore"></i></span>
				<span>Restore Group</span>
			</a>
		</template>
		<template>
			<modal :is-active="showEmailModal" title="Send message" @hide="closeEmailModal">
				<form-field label="Subject" :errors="emailErrors.subject">
					<control-input v-model="email.subject" class="is-capitalized" :class="{ 'is-danger': (emailErrors.subject || []).length }" />
				</form-field>
				<form-field label="Message:" :errors="emailErrors.message">
					<control-textarea v-model="email.message" :class="{ 'is-danger': (emailErrors.message || []).length }" :readonly="readonly" />
				</form-field>
				<template v-slot:footer>
					<div class="field is-grouped">
						<button @click="closeEmailModal" class="button is-dark is-outlined">Close</button>
						<control-button @click="sendEmail" type="submit" class="is-primary" :class="{ 'is-loading': isLoading === 'sendEmail' }">Send</control-button>
					</div>
				</template>
			</modal>
		</template>
    <template>
			<modal :is-active="groupLeaderEmail" title="Send Group Leader Credentials" @hide="closeGroupLeaderEmailModal">
        <p>{{ 'Are you sure you want to send the credentials to'+' '+  this.group.email +'?' }}</p>
				<template v-slot:footer>
					<div class="field is-grouped">
						<button @click="closeGroupLeaderEmailModal" class="button is-dark is-outlined">Close</button>
						<control-button @click="sendGroupLeaderEmail" type="submit" class="is-primary" :class="{ 'is-loading': isLoading === 'sendGroupLeaderEmail' }">Send</control-button>
					</div>
				</template>
			</modal>
		</template>
		<template>
			<modal :is-active="couplesSitePasswordEmail" title="Send Couples Site Password" @hide="closeCouplesSitePasswordEmailModal">
        <p>{{ 'Are you sure you want to send the password to'+' '+  this.group.email +'?' }}</p>
				<template v-slot:footer>
					<div class="field is-grouped">
						<button @click="closeCouplesSitePasswordEmailModal" class="button is-dark is-outlined">Close</button>
						<control-button @click="sendCouplesSitePasswordEmail" type="submit" class="is-primary" :class="{ 'is-loading': isLoading === 'sendCouplesSitePasswordEmail' }">Send</control-button>
					</div>
				</template>
			</modal>
		</template>
		<template>
			<modal :is-active="showReviewModal" title="Review Group - Upload Rooming List" @hide="closeReviewModal">
				<form-field label="Upload Rooming List Spreadsheet" :errors="reviewErrors.file">
					<div class="file has-name is-fullwidth" :class="{ 'is-danger': (reviewErrors.file || []).length }">
						<label class="file-label">
							<input class="file-input" type="file" @change="handleFileUpload" accept=".xlsx,.xls" ref="fileInput">
							<span class="file-cta">
								<span class="file-icon">
									<i class="fas fa-upload"></i>
								</span>
								<span class="file-label">
									Choose a file…
								</span>
							</span>
							<span class="file-name" v-if="reviewFile">
								{{ reviewFile.name }}
							</span>
							<span class="file-name" v-else>
								No file selected
							</span>
						</label>
					</div>
					<p class="help">Upload an Excel file (.xlsx or .xls) exported from this system or in the same format.</p>
				</form-field>
				<template v-slot:footer>
					<div class="field is-grouped">
						<button @click="closeReviewModal" class="button is-dark is-outlined">Cancel</button>
						<control-button @click="uploadAndReview" type="submit" class="is-primary" :class="{ 'is-loading': isLoading === 'review' }" :disabled="!reviewFile">Review</control-button>
					</div>
				</template>
			</modal>
		</template>
		<template>
			<modal :is-active="showDiscrepancyReport" title="Rooming List Comparison Report" @hide="closeDiscrepancyReport" size="is-large">
				<div v-if="discrepancyReport">
					<div class="notification" :class="discrepancyReport.hasDiscrepancies ? 'is-warning' : 'is-success'">
						<p class="is-size-5 has-text-weight-bold">
							<span class="icon">
								<i class="fas" :class="discrepancyReport.hasDiscrepancies ? 'fa-exclamation-triangle' : 'fa-check-circle'"></i>
							</span>
							{{ discrepancyReport.hasDiscrepancies ? 'Discrepancies Found' : 'No Discrepancies Found' }}
						</p>
						<p v-if="discrepancyReport.hasDiscrepancies">
							The uploaded rooming list has differences compared to the system data. Review the details below.
						</p>
						<p v-else>
							The uploaded rooming list matches the system data perfectly.
						</p>
					</div>

					<div class="box" v-if="discrepancyReport.summary">
						<h3 class="title is-5">Summary</h3>
						<div class="columns is-multiline">
							<div class="column is-4">
								<div class="has-text-centered">
									<p class="heading">Total Bookings</p>
									<p class="title is-4">{{ discrepancyReport.summary.totalBookings }}</p>
								</div>
							</div>
							<div class="column is-4">
								<div class="has-text-centered">
									<p class="heading">Total Guests</p>
									<p class="title is-4">
										<span :class="discrepancyReport.summary.guestCountMatch ? 'has-text-success' : 'has-text-danger'">
											{{ discrepancyReport.summary.systemGuestCount }}
										</span>
										<span v-if="!discrepancyReport.summary.guestCountMatch">
											vs {{ discrepancyReport.summary.spreadsheetGuestCount }}
										</span>
									</p>
								</div>
							</div>
							<div class="column is-4">
								<div class="has-text-centered">
									<p class="heading">Bookings with Issues</p>
									<p class="title is-4 has-text-danger">{{ discrepancyReport.summary.bookingsWithDiscrepancies }}</p>
								</div>
							</div>
						</div>
					</div>

					<div class="box" v-if="discrepancyReport.discrepancies && discrepancyReport.discrepancies.length > 0">
						<h3 class="title is-5">Discrepancy Details</h3>
						<div v-for="(booking, index) in discrepancyReport.discrepancies" :key="index" class="box" style="background-color: #fff9f0;">
							<h4 class="title is-6">
								Booking #{{ booking.bookingNumber }}
								<span v-if="booking.guestName" class="has-text-weight-normal"> - {{ booking.guestName }}</span>
							</h4>
							
							<table class="table is-fullwidth is-striped is-hoverable">
								<thead>
									<tr>
										<th>Field</th>
										<th>System Value</th>
										<th>Spreadsheet Value</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="(issue, idx) in booking.issues" :key="idx">
										<td class="has-text-weight-semibold">{{ issue.field }}</td>
										<td>
											<span class="tag is-info is-light">{{ issue.systemValue }}</span>
										</td>
										<td>
											<span class="tag is-warning is-light">{{ issue.spreadsheetValue }}</span>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<div class="notification is-info is-light" v-if="discrepancyReport.notes && discrepancyReport.notes.length > 0">
						<p class="has-text-weight-bold">Notes:</p>
						<ul>
							<li v-for="(note, idx) in discrepancyReport.notes" :key="idx">{{ note }}</li>
						</ul>
					</div>
				</div>
				<template v-slot:footer>
					<div class="field is-grouped">
						<button @click="closeDiscrepancyReport" class="button is-primary">Close</button>
						<button v-if="discrepancyReport && discrepancyReport.hasDiscrepancies" @click="exportDiscrepancyReport" class="button is-link" :class="{ 'is-loading': isLoading === 'exportReport' }">
							<span class="icon"><i class="fas fa-download"></i></span>
							<span>Export Report</span>
						</button>
					</div>
				</template>
			</modal>
		</template>
	</card>
</template>

<script>
	import Card from '@dashboard/components/Card';
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlRadio from '@dashboard/components/form/controls/Radio';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import ControlSwitch from '@dashboard/components/form/controls/Switch';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';
	import DatePicker from 'v-calendar/lib/components/date-picker.umd';
	import DeleteGroup from '@dashboard/pages/Groups/Delete';
	import RestoreGroup from '@dashboard/pages/Groups/Restore';
	import FormField from '@dashboard/components/form/Field';
	import FormPanel from '@dashboard/components/form/Panel';
	import ImageUploader from '@dashboard/components/file/ImageUploader';
	import Tab from '@dashboard/components/tabs/Tab';
	import Tabs from '@dashboard/components/tabs/Tabs';
	import Modal from '@dashboard/components/Modal';
	import ControlEditor from '@dashboard/components/form/controls/QuillEditor';
	import Loader from '@dashboard/components/Loader';

	export default {
		components: {
			Card,
			ControlButton,
			ControlInput,
			ControlRadio,
			ControlSelect,
			ControlSwitch,
			ControlTextarea,
			DatePicker,
			DeleteGroup,
			RestoreGroup,
			FormField,
			FormPanel,
			ImageUploader,
			Tab,
			Tabs,
			Modal,
			ControlEditor,
			Loader,
		},
		data() {
			return {
				attritionDueDates: [],
				faqs: [],
				faqsErrors: {},
				savedGroup: null,
				group: {},
				groupErrors: {},
				dueDates: [],
				dueDatesErrors: [],
				pastBride: {},
				pastBrideErrors: [],
				email: {
					subject: '',
					message: ''
				},
				emailErrors: [],
				destinations: [],
				agents: [],
				providers: [],
				transfers: [],
				insuranceRates: [],
				showDelete: false,
				showRestore: false,
				tabs: {
					info: true,
					dueDates: false,
					pastBride: false,
					attrition: false,
					faqs: false,
					termsConditions: false,
				},
				isLoading: '',
				showEmailModal: false,
				groupLeaderEmail: false,
				couplesSitePasswordEmail: false,
				airports: [],
				airportOptions: [],
				showReviewModal: false,
				reviewFile: null,
				reviewErrors: {},
				showDiscrepancyReport: false,
				discrepancyReport: null,
			}
		},
		created() {
			this.fetchData();
		},
		computed: {
			readonly() {
				return !this.savedGroup.can.update || !!this.savedGroup.deletedAt;
			},
			couplesPage() {
				return `${process.env.MIX_GROUP_URL}/${this.savedGroup.slug}`;
			},
			destination: {
				get() {
					return this.group.destination;
				},
				set(destination) {
					this.group.destination = destination;
					this.group.airports = [{airport: 0, default: 0}];
					this.selectAirportsByDestination();
				}
			},
			depositTypeOptions() {
				const baseOptions = [
					{ value: 'fixed', text: '$' },
					{ value: 'percentage', text: '%' }
				];

				if (!this.group.fit) {
					baseOptions.push({ value: 'nights', text: 'Nights' });
				}

				return baseOptions;
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
		},
    watch: {
      'group.transportation': function(newVal) {
        if (newVal) {
          this.isLoading = 'fetchingDefaultRates';
          this.$http.post('/groups/' + this.$route.params.id + '/default-rates')
            .then(response => {
              const airports = response.data.airports;

              if (airports.length > 0) {
                const mappedAirports = airports.map(airport => ({
                  airport: airport.airport_id,
                  transfer: airport.transfer_id,
                  transportationRate: airport.transportation_rate,
                  singleTransportationRate: airport.single_transportation_rate,
                  oneWayTransportationRate: airport.one_way_transportation_rate,
                  default: airport.default
                }));

                this.originalAirports = JSON.parse(JSON.stringify(airports));
                this.group.airports = mappedAirports;
              }
            })
            .catch(error => {
              console.error('Error fetching default rates:', error);
            })
            .finally(() => {
              this.isLoading = '';
            });
        }
      }
    },
		methods: {
			fetchData() {
				this.$http.get('/groups/' + this.$route.params.id)
					.then(response => {
						this.savedGroup = response.data.data;

						this.group = {
							fit: this.savedGroup.fit,
							brideFirstName: this.savedGroup.brideFirstName,
							brideLastName: this.savedGroup.brideLastName,
							groomFirstName: this.savedGroup.groomFirstName,
							groomLastName: this.savedGroup.groomLastName,
							email: this.savedGroup.email,
              secondaryEmail: this.savedGroup.secondaryEmail,
							password: this.savedGroup.password,
							slug: this.savedGroup.slug,
							eventDate: this.$moment(this.savedGroup.eventDate).toDate(),
							destination: this.savedGroup.destination.id,
							weddingLocation: this.savedGroup.weddingLocation,
							venueName: this.savedGroup.venueName,
							isActive: !!this.savedGroup.isActive,
							couplesSitePassword: this.savedGroup.couplesSitePassword,
							image: this.savedGroup.image,
							attritionImage: this.savedGroup.attritionImage,
							message: this.savedGroup.message,
							agent: this.savedGroup.agent.id,
							provider: this.savedGroup.provider.id,
							providerId: this.savedGroup.providerId,
							insuranceRate: this.savedGroup.insuranceRate.id,
							useFallbackInsurance: this.savedGroup.useFallbackInsurance,
							transportation: this.savedGroup.transportation,
							transportationRate: this.savedGroup.transportationRate,
							singleTransportationRate: this.savedGroup.singleTransportationRate, 
							oneWayTransportationRate: this.savedGroup.oneWayTransportationRate,
							transportationType: this.savedGroup.transportationType,
							transportationSubmitBefore: this.savedGroup.transportationSubmitBefore === null ? null : this.$moment(this.savedGroup.transportationSubmitBefore).toDate(),
							minNights: this.savedGroup.minNights,
							deposit: this.savedGroup.deposit,
							depositType: this.savedGroup.depositType,
							changeFeeAmount: this.savedGroup.changeFeeAmount,
							changeFeeDate: this.savedGroup.changeFeeDate,
							notes: this.savedGroup.notes,
							bannerMessage: this.savedGroup.bannerMessage,
							staffMessage: this.savedGroup.staffMessage,
							bookingsExportUrl: this.savedGroup.bookingsExportUrl,
							flightManifestsExportUrl: this.savedGroup.flightManifestsExportUrl,
							airports: this.savedGroup.airports.length > 0 ? this.savedGroup.airports : [{airport: 0, default: 0}],
							disableInvoiceSplitting: this.savedGroup.disableInvoiceSplitting,
							disableNotifications: this.savedGroup.disableNotifications,
							termsAndConditions: response.data.data.termsAndConditions,
						};

						this.dueDates = {
							dueDate: this.$moment(this.savedGroup.dueDate).toDate(),
							cancellationDate: this.$moment(this.savedGroup.cancellationDate).toDate(),

							other: this.savedGroup.dueDates.map(dueDate => ({
								date: this.$moment(dueDate.date).toDate(),
								amount: dueDate.amount,
								type: dueDate.type
							}))
						};

            this.attritionDueDates = response.data.data.groupAttritionDueDates.map(dueDate => ({
							date: this.$moment(dueDate.date).toDate(),
						}));

						this.faqs = response.data.data.groupFaqs;

						this.pastBride = {
							message: this.savedGroup.pastBrideMessage,
							show: this.savedGroup.showAsPastBride
						}

						this.destinations = response.data.destinations;
						this.agents = response.data.agents;
						this.providers = response.data.providers;

						this.transfers = response.data.transfers.map(transfer => ({
							value: transfer.id,
							text: transfer.name,
						}));

						this.insuranceRates = response.data.insuranceRates;
						this.airports = response.data.airports;

						this.selectAirportsByDestination();
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
						label: !this.savedGroup.deletedAt ? 'Groups' : 'Deleted Groups',
						route: !this.savedGroup.deletedAt ? 'groups' : 'trash'
					},
					{
						label: this.savedGroup.brideLastName + ' & ' + this.savedGroup.groomLastName,
						route: 'groups.show',
						params: {
							id: this.savedGroup.id
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
				this.group.venueName = this.group.weddingLocation == 'venue' ? this.group.venueName : null;
				this.group.eventDate = this.group.eventDate instanceof Date ? this.group.eventDate.toDateString() : this.group.eventDate;
				this.group.transportationSubmitBefore = this.group.transportationSubmitBefore instanceof Date ? this.group.transportationSubmitBefore.toDateString() : this.group.transportationSubmitBefore;
				this.group.changeFeeDate = this.group.changeFeeDate instanceof Date ? this.group.changeFeeDate.toDateString() : this.group.changeFeeDate;
	
				let request = this.$http.put('/groups/' + this.$route.params.id, this.group)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The group has been updated.'
						});

						this.savedGroup = {...this.savedGroup, ...response.data.data};
						this.groupErrors = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.groupErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			deleted() {
				this.$router.push({
					name: 'groups'
				});
			},
			restored() {
				window.location.href = '/groups/' + this.savedGroup.id;
			},
			toggleBookingAcceptance(newValue) {
				const oldValue = this.savedGroup.acceptsNewBookings;
				this.savedGroup.acceptsNewBookings = newValue;

				this.$http.patch(`/groups/${this.savedGroup.id}/toggle-booking-acceptance`)
					.then(response => {
						if (response.data && typeof response.data.accepts_new_bookings !== 'undefined') {
							this.savedGroup.acceptsNewBookings = response.data.accepts_new_bookings;
						}

						this.$store.commit('notification', {
							type: 'success',
							message: response.data.message || 'Booking acceptance status updated.'
						});
					}).catch(error => {
						this.savedGroup.acceptsNewBookings = oldValue;
						let errorMessage = 'Failed to update booking acceptance status.';
						
						if (error.response && error.response.data && error.response.data.message) {
							errorMessage = error.response.data.message;
						}
						
						this.$store.commit('notification', {
							type: 'danger',
							message: errorMessage
						});
					});
			},
			syncDueDates() {
				this.isLoading = 'syncDueDates';

				let payload = {
					...this.dueDates,
					dueDate: this.dueDates.dueDate instanceof Date ? this.dueDates.dueDate.toDateString() : this.dueDates.dueDate,
					cancellationDate: this.dueDates.cancellationDate instanceof Date ? this.dueDates.cancellationDate.toDateString() : this.dueDates.cancellationDate,

					other: this.dueDates.other.map(otherDueDate => ({
						...otherDueDate,
						date: otherDueDate.date instanceof Date ? otherDueDate.date.toDateString() : otherDueDate.date
					}))
				};
	
				let request = this.$http.patch('/groups/' + this.$route.params.id + '/due-dates', payload)
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
			updatePastBride() {
				this.isLoading = 'updatePastBride';

				let request = this.$http.patch('/groups/' + this.$route.params.id + '/past-bride', this.pastBride)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The past bride information has been updated.'
						});

						this.pastBrideErrors = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.pastBrideErrors = error.response.data.errors;
						}
					});
	
				request.then(() => {
					this.isLoading = '';
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

				let request = this.$http.patch('/groups/' + this.$route.params.id + '/attrition', {
						attritionImage: this.group.attritionImage,
						attritionDueDates: this.attritionDueDates,
					}).then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The attrition have been updated.'
						});

						this.groupErrors.attrition = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.groupErrors.attrition = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			updateFaqs() {
				this.isLoading = 'updateFaqs';

				let request = this.$http.patch('/groups/' + this.$route.params.id + '/faqs', {faqs: this.faqs})
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The FAQs have been updated.'
						});
					}).catch(error => {
						if (error.response.status === 422) {
							this.faqsErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},		
			closeEmailModal() {
				this.showEmailModal = false;
				this.emailErrors = [];
			},
			closeGroupLeaderEmailModal() {
				this.groupLeaderEmail = false;
			},
			closeCouplesSitePasswordEmailModal() {
				this.couplesSitePasswordEmail = false;
			},
			sendEmail() {
				this.isLoading = 'sendEmail';

				let request = this.$http.post('/groups/' + this.$route.params.id + '/send-email', this.email)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The emails have been sent.' 
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
			selectAirportsByDestination() {
				this.airportOptions = this.airports.filter(destination => destination.id == this.group.destination)[0].airports.map(airport => ({
					value: airport.id,
					text: airport.airport_code,
					transferId: airport.transfer_id
				}));
			},
			updateTermsConditions() {
				this.isLoading = 'updateTermsConditions';

				let request = this.$http.patch('/groups/' + this.$route.params.id + '/terms-conditions', {
						termsAndConditions: this.group.termsAndConditions
					}).then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The terms and conditions have been updated.'
						});

						this.groupErrors.termsAndConditions = [];
						this.group.termsAndConditions = response.data.termsAndConditions;
					}).catch(error => {
						if (error.response.status === 422) {
							this.groupErrors.termsAndConditions = error.response.data.errors.termsAndConditions || [];
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			sendGroupLeaderEmail() {
				this.isLoading = 'sendGroupLeaderEmail';

				let request = this.$http.post('/groups/' + this.$route.params.id + '/send-group-leader-email')
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The email has been sent.'
						});
					}).catch(error => {
						if (error.response && error.response.status === 422) {
							this.groupErrors = error.response.data.errors;
						} else {
							this.$store.commit('notification', {
								type: 'danger',
								message: 'Failed to send email.'
							});
						}
					});

				request.then(() => {
					this.isLoading = '';
					this.groupLeaderEmail = false;
				});
			},
			sendCouplesSitePasswordEmail() {
				this.isLoading = 'sendCouplesSitePasswordEmail';

				let request = this.$http.post('/groups/' + this.$route.params.id + '/send-couples-site-password-email')
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The email has been sent.'
						});
					}).catch(error => {
						if (error.response && error.response.status === 422) {
							this.groupErrors = error.response.data.errors;
						} else {
							this.$store.commit('notification', {
								type: 'danger',
								message: 'Failed to send email.'
							});
						}
					});

				request.then(() => {
					this.isLoading = '';
					this.couplesSitePasswordEmail = false;
				});
			},
			handleFileUpload(event) {
				const file = event.target.files[0];
				if (file) {
					this.reviewFile = file;
					this.reviewErrors = {};
				}
			},
			closeReviewModal() {
				this.showReviewModal = false;
				this.reviewFile = null;
				this.reviewErrors = {};
				if (this.$refs.fileInput) {
					this.$refs.fileInput.value = '';
				}
			},
			uploadAndReview() {
				if (!this.reviewFile) {
					this.reviewErrors = { file: ['Please select a file to upload.'] };
					return;
				}

				this.isLoading = 'review';

				const formData = new FormData();
				formData.append('file', this.reviewFile);

				this.$http.post('/groups/' + this.$route.params.id + '/review-rooming-list', formData, {
					headers: {
						'Content-Type': 'multipart/form-data'
					}
				})
				.then(response => {
					this.discrepancyReport = response.data;
					this.showReviewModal = false;
					this.showDiscrepancyReport = true;
					// Keep reviewFile for export functionality
					this.reviewErrors = {};
				})
				.catch(error => {
					if (error.response && error.response.status === 422) {
						this.reviewErrors = error.response.data.errors || {};
						
						this.$store.commit('notification', {
							type: 'danger',
							message: error.response.data.message || 'Failed to process the file.'
						});
					} else {
						this.$store.commit('notification', {
							type: 'danger',
							message: 'An error occurred while processing the file.'
						});
					}
				})
				.finally(() => {
					this.isLoading = '';
				});
			},
			closeDiscrepancyReport() {
				this.showDiscrepancyReport = false;
				this.discrepancyReport = null;
				this.reviewFile = null;
				if (this.$refs.fileInput) {
					this.$refs.fileInput.value = '';
				}
			},
			exportDiscrepancyReport() {
				if (!this.reviewFile) {
					this.$store.commit('notification', {
						type: 'danger',
						message: 'No file available for export.'
					});
					return;
				}

				this.isLoading = 'exportReport';

				const formData = new FormData();
				formData.append('file', this.reviewFile);

				this.$http.post('/groups/' + this.$route.params.id + '/export-rooming-list-comparison', formData, {
					headers: {
						'Content-Type': 'multipart/form-data'
					},
					responseType: 'blob'
				})
				.then(response => {
					const url = window.URL.createObjectURL(new Blob([response.data]));
					const link = document.createElement('a');
					link.href = url;
					link.setAttribute('download', `${this.savedGroup.brideLastName}_${this.savedGroup.groomLastName}_Rooming_List_Comparison_${new Date().toISOString().split('T')[0]}.xlsx`);
					document.body.appendChild(link);
					link.click();
					link.remove();
					window.URL.revokeObjectURL(url);

					this.$store.commit('notification', {
						type: 'success',
						message: 'Report exported successfully.'
					});
				})
				.catch(error => {
					this.$store.commit('notification', {
						type: 'danger',
						message: 'Failed to export the report.'
					});
				})
				.finally(() => {
					this.isLoading = '';
				});
			}
		}
	}
</script>