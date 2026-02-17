<template>
	<card v-if="savedLead" :title="savedLead.name">
		<template v-slot:action>
			<span class="tag is-large" :class="statusClass(savedLead.status)">
				{{ savedLead.status }}
			</span>
			<a v-if="savedLead.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-trash"></i></span>
			</a>
			<delete-lead v-if="showDelete" :lead="savedLead" @deleted="deleted" @canceled="showDelete = false" />
		</template>
		<template v-slot:tabs>
			<tabs class="is-boxed">
				<tab @click="setTab('info')" :is-active="tabs.info">Lead</tab>
				<tab @click="setTab('hotelRequests')" :is-active="tabs.hotelRequests">Hotel Requests</tab>
			</tabs>
		</template>
		<template v-if="tabs.info">
			<div class="columns">
				<div class="column">
					<form-field label="Bride First Name" :errors="leadErrors.brideFirstName" :required="true">
						<control-input v-model="lead.brideFirstName" class="is-capitalized" :readonly="readonly" :class="{ 'is-danger': leadErrors.brideFirstName && leadErrors.brideFirstName.length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Bride Last Name" :errors="leadErrors.brideLastName" :required="true">
						<control-input v-model="lead.brideLastName" class="is-capitalized" :readonly="readonly" :class="{ 'is-danger': leadErrors.brideLastName && leadErrors.brideLastName.length }" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Groom First Name" :errors="leadErrors.groomFirstName" :required="true">
						<control-input v-model="lead.groomFirstName" class="is-capitalized" :readonly="readonly" :class="{ 'is-danger': leadErrors.groomFirstName && leadErrors.groomFirstName.length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Groom Last Name" :errors="leadErrors.groomLastName" :required="true">
						<control-input v-model="lead.groomLastName" class="is-capitalized" :readonly="readonly" :class="{ 'is-danger': leadErrors.groomLastName && leadErrors.groomLastName.length }" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Email" :errors="leadErrors.email" :required="true">
						<control-input v-model="lead.email" :readonly="readonly" :class="{ 'is-danger': leadErrors.email && leadErrors.email.length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Phone" :errors="leadErrors.phone">
						<control-input v-model="lead.phone" :readonly="readonly" :class="{ 'is-danger': leadErrors.phone && leadErrors.phone.length }" />
					</form-field>
					<form-field>
						<label class="checkbox">
							<input type="checkbox" v-model="lead.textAgreement" :disabled="readonly" />
							I agree to receive text messages
						</label>
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Wedding Date" :errors="leadErrors.weddingDate">
						<control-input v-model="lead.weddingDate" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.weddingDate && leadErrors.weddingDate.length }" :min="$moment.utc(lead.contactedUsDate).add(1, 'day').format('YYYY-MM-DD')" />
					</form-field>
					<form-field>
						<label class="checkbox">
							<input type="checkbox" v-model="lead.weddingDateConfirmed" :disabled="readonly" />
							The wedding date is confirmed
						</label>
					</form-field>
				</div>
				<div class="column">
					<form-field label="Travel Dates" :errors="[...(leadErrors.travelStartDate || []), ...(leadErrors.travelEndDate || [])]">
						<div class="is-flex is-align-items-center">
							<control-input v-model="lead.travelStartDate" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.travelStartDate && leadErrors.travelStartDate.length }" :min="$moment.utc(lead.weddingDate).subtract(10, 'day').format('YYYY-MM-DD')" :max="$moment.utc(lead.weddingDate).format('YYYY-MM-DD')" />
							<span style="margin: 0 10px;">to</span>
							<control-input v-model="lead.travelEndDate" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.travelEndDate && leadErrors.travelEndDate.length }" :min="$moment.utc(lead.weddingDate).format('YYYY-MM-DD')" :max="$moment.utc(lead.weddingDate).add(10, 'day').format('YYYY-MM-DD')" />
						</div>
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Travel Agent" :errors="leadErrors.travelAgentId">
						<control-select
							v-model="lead.travelAgentId"
							:options="travelAgents"
							:class="{ 'is-danger': (leadErrors.travelAgentId || []).length }"
							:disabled="readonly || !can.updateAllLeads"
						/>
						<small v-if="savedLead.assignedAt"><b>Assigned At:</b> {{ savedLead.assignedAt }}</small>
					</form-field>
				</div>
				<div class="column">
					<form-field label="Travel Agent Requested" :errors="leadErrors.travelAgentRequested">
						<control-input v-model="lead.travelAgentRequested" :readonly="readonly" :class="{ 'is-danger': leadErrors.travelAgentRequested && leadErrors.travelAgentRequested.length }" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Status" :errors="leadErrors.status" :required="true">
						<control-select
							v-model="lead.status"
							:options="statusOptions"
							:class="{ 'is-danger': leadErrors.status && leadErrors.status.length }"
							:disabled="readonly"
						/>
					</form-field>
				</div>
				<div class="column">
					<form-field label="Departure" :errors="leadErrors.departure">
						<control-select
							v-model="lead.departure"
							:options="departureOptions"
							:class="{ 'is-danger': leadErrors.departure && leadErrors.departure.length }"
							first-is-empty
							:disabled="readonly"
						/>
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Lead Type" :errors="leadErrors.isFit">
						<label class="checkbox" :class="{ 'is-danger': (leadErrors.isFit || []).length }">
							<input type="checkbox" v-model="lead.isFit" :disabled="readonly" />
							Is this an FIT lead?
						</label>
					</form-field>
				</div>
				<div class="column">
					<form-field label="Canadian Lead" :errors="leadErrors.isCanadian">
						<label class="checkbox" :class="{ 'is-danger': (leadErrors.isCanadian || []).length }">
							<input type="checkbox" v-model="lead.isCanadian" :disabled="readonly" />
							Is this a Canadian lead?
						</label>
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="How They Contacted Us?" :errors="leadErrors.contactedUsBy">
						<control-select
							v-model="lead.contactedUsBy"
							:options="contactedUsOptions"
							:class="{ 'is-danger': leadErrors.contactedUsBy && leadErrors.contactedUsBy.length }"
							:disabled="readonly"
						/>
					</form-field>
				</div>
				<div class="column">
					<form-field label="Contacted Us Date" :errors="leadErrors.contactedUsDate" :required="true">
						<control-input v-model="lead.contactedUsDate" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.contactedUsDate && leadErrors.contactedUsDate.length }" :max="$moment().format('YYYY-MM-DD')" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Balance Due Date" :errors="leadErrors.balanceDueDate">
						<control-input v-model="lead.balanceDueDate" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.balanceDueDate && leadErrors.balanceDueDate.length }" :max="$moment.utc(lead.weddingDate).subtract(1, 'day').format('YYYY-MM-DD')" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Cancellation Date" :errors="leadErrors.cancellationDate">
						<control-input v-model="lead.cancellationDate" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.cancellationDate && leadErrors.cancellationDate.length }" :max="$moment.utc(lead.weddingDate).subtract(1, 'day').format('YYYY-MM-DD')" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Release Rooms By" :errors="leadErrors.releaseRoomsBy">
						<control-input v-model="lead.releaseRoomsBy" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.releaseRoomsBy && leadErrors.releaseRoomsBy.length }" :max="$moment.utc(lead.weddingDate).add(10, 'day').format('YYYY-MM-DD')" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Contract Sent On" :errors="leadErrors.contractSentOn">
						<control-input v-model="lead.contractSentOn" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.contractSentOn && leadErrors.contractSentOn.length }" :max="$moment.utc(lead.weddingDate).subtract(1, 'day').format('YYYY-MM-DD')" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Last Attempt" :errors="leadErrors.lastAttempt">
						<control-input v-model="lead.lastAttempt" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.lastAttempt && leadErrors.lastAttempt.length }" :max="$moment.utc(lead.weddingDate).subtract(1, 'day').format('YYYY-MM-DD')" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Responded On" :errors="leadErrors.respondedOn">
						<control-input v-model="lead.respondedOn" type="date" :readonly="readonly" :class="{ 'is-danger': leadErrors.respondedOn && leadErrors.respondedOn.length }" :max="$moment.utc(lead.weddingDate).subtract(1, 'day').format('YYYY-MM-DD')" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Notes" :errors="leadErrors.notes">
						<control-textarea v-model="lead.notes" :readonly="readonly" :class="{ 'is-danger': (leadErrors.notes || []).length }" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Venue / Resort" :errors="leadErrors.venue">
						<control-input v-model="lead.venue" :readonly="readonly" :class="{ 'is-danger': leadErrors.venue && leadErrors.venue.length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field>
						<form-field label="On-site or Off-site" :errors="leadErrors.site">
							<control-select
								v-model="lead.site"
								:options="siteOptions"
								:class="{ 'is-danger': leadErrors.site && leadErrors.site.length }"
								:disabled="readonly"
							/>
						</form-field>
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Number of People" :errors="leadErrors.numberOfPeople">
						<control-input type="number" v-model="lead.numberOfPeople" :readonly="readonly" :class="{ 'is-danger': leadErrors.numberOfPeople && leadErrors.numberOfPeople.length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Number of Rooms" :errors="leadErrors.numberOfRooms">
						<control-input type="number" v-model="lead.numberOfRooms" :readonly="readonly" :class="{ 'is-danger': leadErrors.numberOfRooms && leadErrors.numberOfRooms.length }" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Destination(s)" :errors="leadErrors.destinations">
						<control-textarea v-model="lead.destinations" :readonly="readonly" :class="{ 'is-danger': (leadErrors.destinations || []).length }" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="How They Heard About Us?" :errors="leadErrors.referralSource">
						<control-select
							v-model="lead.referralSource"
							:options="referralSourceOptions"
							:class="{ 'is-danger': leadErrors.referralSource && leadErrors.referralSource.length }"
							first-is-empty
							:disabled="readonly"
						/>
					</form-field>
				</div>
				<div v-if="lead.referralSource === 'Referral (please include who)'" class="column">
					<form-field label="Referred By (if applicable)" :errors="leadErrors.referredBy">
						<control-input v-model="lead.referredBy" :readonly="readonly" :class="{ 'is-danger': leadErrors.referredBy && leadErrors.referredBy.length }" />
					</form-field>
				</div>
			</div>
			<div v-if="lead.referralSource === 'Facebook Group (please include which)'" class="columns">
				<div class="column">
					<form-field label="Facebook Group (if applicable)" :errors="leadErrors.facebookGroup">
						<control-textarea rows="1" v-model="lead.facebookGroup" :readonly="readonly" :class="{ 'is-danger': (leadErrors.facebookGroup || []).length }" />
					</form-field>
				</div>
			</div>
			<div class="columns">
				<div class="column">
					<form-field label="Message" :errors="leadErrors.message">
						<control-textarea v-model="lead.message" :readonly="readonly" :class="{ 'is-danger': (leadErrors.message || []).length }" />
					</form-field>
				</div>
			</div>
			<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
		</template>
		<template v-if="tabs.hotelRequests">
			<form-panel label="Hotel Requests" class="is-borderless">
				<template v-slot:action>
					<control-button v-if="!readonly" class="is-small is-link is-outlined" @click="leadProviders.push({providerId: '', idAtProvider: '', specialistId: '', leadHotels: [{hotel: '', brandId: '', requestedOn: $moment().format('YYYY-MM-DD'), weddingDate: '', travelStartDate: '', travelEndDate: '', receivedOn: '', proposalDocument: null}]})">
						<i class="fas fa-plus"></i>
					</control-button>
				</template>
				<form-panel v-for="(leadProvider, index) in leadProviders" :key="index">
					<template v-if="!readonly" v-slot:action>
						<control-button v-if="leadProvider.providerId && leadProvider.leadHotels.some(leadHotel => leadHotel.hotel && leadHotel.requestedOn && leadHotel.weddingDate && leadHotel.travelStartDate && leadHotel.travelEndDate && !leadHotel.receivedOn && !leadHotel.proposalDocument)" class="is-small is-primary" @click="openSupplierEmailModal(leadProvider)">
							Request Rates
						</control-button>
						<control-button class="is-small is-link is-outlined" @click="leadProviders.splice(index, 1)">
							<i class="fas fa-minus"></i>
						</control-button>
					</template>
					<div class="columns">
						<div class="column">
							<form-field label="Supplier" :errors="leadProviderErrors['leadProviders.' + index + '.providerId']" :required="true">
								<control-select 
									v-model="leadProvider.providerId" 
									:options="providers" 
									first-is-empty
									@change="leadProvider.specialistId = null"
									:class="{ 'is-danger': (leadProviderErrors['leadProviders.' + index + '.providerId'] || []).length }" 
									:disabled="readonly" 
								/>
							</form-field>
						</div>
						<div class="column">
							<form-field label="Group ID" :errors="leadProviderErrors['leadProviders.' + index + '.idAtProvider']">
								<control-input v-model="leadProvider.idAtProvider" :readonly="readonly" :class="{ 'is-danger': leadProviderErrors['leadProviders.' + index + '.idAtProvider'] && leadProviderErrors['leadProviders.' + index + '.idAtProvider'].length }" />
							</form-field>
						</div>
						<div class="column">
							<form-field label="Specialist" :errors="leadProviderErrors['leadProviders.' + index + '.specialistId']">
								<control-select 
									v-model="leadProvider.specialistId" 
									:options="leadProvider.providerId ? getSpecialistOptions(leadProvider.providerId) : []" 
									first-is-empty
									:class="{ 'is-danger': (leadProviderErrors['leadProviders.' + index + '.specialistId'] || []).length }" 
									:disabled="readonly" 
								/>
							</form-field>
						</div>
					</div>
					<form-panel label="Hotels" class="is-borderless">
						<template v-slot:action>
							<control-button v-if="!readonly" class="is-small is-link is-outlined" @click="leadProvider.leadHotels.push({hotel: '', brandId: '', requestedOn: $moment().format('YYYY-MM-DD'), weddingDate: '', travelStartDate: '', travelEndDate: '', receivedOn: '', proposalDocument: null})">
								<i class="fas fa-plus"></i>
							</control-button>
						</template>
						<form-panel v-for="(leadHotel, hotelIndex) in leadProvider.leadHotels" :key="hotelIndex">
							<template v-if="!readonly" v-slot:action>
								<control-button v-if="leadHotel.id && leadHotel.hotel && leadHotel.brandId && leadHotel.requestedOn && leadHotel.weddingDate && leadHotel.travelStartDate && leadHotel.travelEndDate && leadHotel.receivedOn && leadHotel.proposalDocument && leadHotel.proposalDocument.storagePath" class="is-small is-primary" @click="openConvertProposalDocumentModal(leadHotel)">
									Convert Proposal Document
								</control-button>
								<control-button class="is-small is-link is-outlined" @click="leadProvider.leadHotels.splice(hotelIndex, 1)">
									<i class="fas fa-minus"></i>
								</control-button>
							</template>
							<div class="columns">
								<div class="column">
									<form-field label="Hotel" :errors="leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.hotel']" :required="true">
										<control-input v-model="leadHotel.hotel" :readonly="readonly" :class="{ 'is-danger': leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.hotel'] && leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.hotel'].length }" />
									</form-field>
								</div>
								<div class="column">
									<form-field label="Brand" :errors="leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.brandId']">
										<control-select
											v-model="leadHotel.brandId"
											:options="brands"
											:class="{ 'is-danger': leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.brandId'] && leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.brandId'].length }"
											:disabled="readonly"
										/>
									</form-field>
								</div>
								<div class="column">
									<form-field label="Requested On" :errors="leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.requestedOn']">
										<control-input v-model="leadHotel.requestedOn" type="date" :readonly="readonly" :class="{ 'is-danger': leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.requestedOn'] && leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.requestedOn'].length }" :max="$moment().format('YYYY-MM-DD')" />
									</form-field>
								</div>
							</div>
							<div class="columns">
								<div class="column">
									<form-field label="Wedding Date" :errors="leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.weddingDate']">
										<control-input v-model="leadHotel.weddingDate" type="date" :readonly="readonly" :class="{ 'is-danger': leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.weddingDate'] && leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.weddingDate'].length }" :min="$moment.utc(lead.contactedUsDate).add(1, 'day').format('YYYY-MM-DD')" />
									</form-field>
								</div>
								<div class="column">
									<form-field label="Travel Dates" :errors="[...(leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.travelStartDate'] || []), ...(leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.travelEndDate'] || [])]">
										<div class="is-flex is-align-items-center">
											<control-input v-model="leadHotel.travelStartDate" type="date" :readonly="readonly" :class="{ 'is-danger': leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.travelStartDate'] && leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.travelStartDate'].length }" :min="$moment.utc(leadHotel.weddingDate).subtract(10, 'day').format('YYYY-MM-DD')" :max="$moment.utc(leadHotel.weddingDate).format('YYYY-MM-DD')" />
											<span style="margin: 0 10px;">to</span>
											<control-input v-model="leadHotel.travelEndDate" type="date" :readonly="readonly" :class="{ 'is-danger': leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.travelEndDate'] && leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.travelEndDate'].length }" :min="$moment.utc(leadHotel.weddingDate).format('YYYY-MM-DD')" :max="$moment.utc(leadHotel.weddingDate).add(10, 'day').format('YYYY-MM-DD')" />
										</div>
									</form-field>
								</div>
								<div class="column">
									<form-field label="Received On" :errors="leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.receivedOn']">
										<control-input v-model="leadHotel.receivedOn" type="date" :readonly="readonly" :class="{ 'is-danger': leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.receivedOn'] && leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.receivedOn'].length }" :max="$moment().format('YYYY-MM-DD')" />
									</form-field>
								</div>
							</div>
							<div class="columns">
								<div class="column">
									<form-field label="Proposal Document" :errors="leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.proposalDocument']">
										<file-uploader
											v-model="leadHotel.proposalDocument"
											@errors="$set(leadProviderErrors, 'leadProviders.' + index + '.leadHotels.' + hotelIndex + '.proposalDocument', $event)"
											:class="{ 'is-danger': (leadProviderErrors['leadProviders.' + index + '.leadHotels.' + hotelIndex + '.proposalDocument'] || []).length }"
											:max-size="3072"
											is-single
											:disabled="readonly"
											:accepted-types="['application/pdf', 'pdf']"
											@input="onProposalUploaded(leadHotel)"
										/>
									</form-field>
								</div>
							</div>
						</form-panel>
					</form-panel>
				</form-panel>
			</form-panel>
			<modal :is-active="showSupplierEmailModal" @hide="closeSupplierEmailModal" title="Request Rates From Supplier">
				<form-field label="To" :errors="supplierEmailErrors.email" :required="true">
					<control-input v-model="supplierEmail.email" :readonly="readonly" :class="{ 'is-danger': supplierEmailErrors.email && supplierEmailErrors.email.length }" />
				</form-field>
				<form-field label="Cc" :errors="supplierEmailErrors.cc">
					<control-input v-model="supplierEmail.cc" :readonly="readonly" placeholder="john@example.com,team@example.com" :class="{ 'is-danger': supplierEmailErrors.cc && supplierEmailErrors.cc.length }" />
					<small>Emails will be CC’d to Admin and Ops by default. To CC additional recipients, enter their email addresses separated by commas.</small>
				</form-field>
				<form-field label="Body" :errors="supplierEmailErrors.body" :required="true">
					<control-textarea v-model="supplierEmail.body" rows="20" :readonly="readonly" :class="{ 'is-danger': (supplierEmailErrors.body || []).length }" />
				</form-field>
				<template v-slot:footer>
					<div class="field is-grouped">
						<control-button @click="closeSupplierEmailModal" :disabled="isLoading === 'sendSupplierEmail'">Cancel</control-button>
						<control-button @click="sendSupplierEmail" type="submit" class="is-primary" :class="{ 'is-loading': isLoading === 'sendSupplierEmail' }">Send</control-button>
					</div>
				</template>
			</modal>
			<modal :is-active="showConvertProposalDocumentModal" @hide="closeConvertProposalDocumentModal" title="Convert Proposal Document">
				<p>Are you sure you want to convert the proposal document for {{ leadHotelToConvert ? leadHotelToConvert.hotel : 'this hotel' }} into Barefoot Bridal branding?</p>
				<template v-slot:footer>
					<div class="field is-grouped">
						<control-button @click="closeConvertProposalDocumentModal" :disabled="isLoading === 'convertProposalDocument'">Cancel</control-button>
						<control-button @click="convertProposalDocument" type="submit" class="is-primary" :class="{ 'is-loading': isLoading === 'convertProposalDocument' }">Convert</control-button>
					</div>
				</template>
			</modal>
			<control-button v-if="!readonly" @click="updateHotelRequests" class="is-primary" :class="{ 'is-loading': isLoading === 'updateHotelRequests' }">Save</control-button>
		</template>
	</card>
</template>

<script>
	import Card from '@dashboard/components/Card';
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';
	import DeleteLead from '@dashboard/pages/Leads/Delete';
	import FormField from '@dashboard/components/form/Field';
	import Tab from '@dashboard/components/tabs/Tab';
	import Tabs from '@dashboard/components/tabs/Tabs';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import FormPanel from '@dashboard/components/form/Panel';
	import Modal from '@dashboard/components/Modal';
	import FileUploader from '@dashboard/components/file/FileUploader';

	export default {
		components: {
			Card,
			ControlButton,
			ControlInput,
			ControlTextarea,
			DeleteLead,
			FormField,
			Tab,
			Tabs,
			ControlSelect,
			FormPanel,
			Modal,
			FileUploader,
		},
		data() {
			return {
				savedLead: null,
				lead: {},
				leadErrors: {},
				leadProviders: [],
				leadProviderErrors: {},
				showDelete: false,
				tabs: {
					info: true,
					hotelRequests: false,
				},
				isLoading: '',
				can: {},
				departureOptions: [
					{ value: "US", text: "US" },
					{ value: "Canada", text: "Canada" },
					{ value: "Other", text: "Other" },
				],
				referralSourceOptions: [],
				statusOptions: [
					{ value: 'Unassigned', text: 'Unassigned' },
					{ value: 'Assigned', text: 'Assigned' },
					{ value: 'Pending Rates', text: 'Pending Rates' },
					{ value: 'Received Rates', text: 'Received Rates' },
					{ value: 'Pending K', text: 'Pending K' },
					{ value: 'Pending Deposit', text: 'Pending Deposit' },
					{ value: 'Signed K', text: 'Signed K' },
					{ value: 'Declined', text: 'Declined' },
				],
				siteOptions: [
					{ value: 'Unknown', text: 'Unknown' },
					{ value: 'On-site', text: 'On-site' },
					{ value: 'Off-site', text: 'Off-site' },
				],
				contactedUsOptions: [],
				brands: [],
				travelAgents: [],
				providers: [],
				showSupplierEmailModal: false,
				supplierEmail: {},
				supplierEmailErrors: {},
				showConvertProposalDocumentModal: false,
				leadHotelToConvert: null,
			}
		},
		created() {
			this.fetchData();
		},
		computed: {
			readonly() {
				return !this.savedLead.can.update;
			},
			getSpecialistOptions() {
				return (providerId) => {
					if (!Array.isArray(this.providers)) return [];

					const selectedProvider = this.providers.find(provider => provider.id === providerId);

					return selectedProvider?.specialists ? selectedProvider.specialists : [];
				};
			},
		},
		methods: {
			fetchData() {
				this.$http.get('/leads/' + this.$route.params.id)
					.then(response => {
						this.savedLead = response.data.data;
						this.leadProviders = this.savedLead.leadProviders;
						this.travelAgents = response.data.travelAgents;
						this.providers = response.data.providers;
						this.referralSourceOptions = response.data.referralSourceOptions;
						this.contactedUsOptions = response.data.contactedUsOptions;
						this.brands = response.data.brands;
						this.can = response.data.can;

						this.assignLead();
						this.setBreadcrumbs();
					});
			},
			assignLead() {
				this.lead = {
					isFit: this.savedLead.isFit,
					isCanadian: this.savedLead.isCanadian,
					travelAgentId: this.savedLead.travelAgentId,
					assignedAt: this.savedLead.assignedAt,
					brideFirstName: this.savedLead.brideFirstName,
					brideLastName: this.savedLead.brideLastName,
					groomFirstName: this.savedLead.groomFirstName,
					groomLastName: this.savedLead.groomLastName,
					departure: this.savedLead.departure,
					phone: this.savedLead.phone,
					textAgreement: this.savedLead.textAgreement,
					email: this.savedLead.email,
					venue: this.savedLead.venue,
					site: this.savedLead.site,
					numberOfPeople: this.savedLead.numberOfPeople,
					numberOfRooms: this.savedLead.numberOfRooms,
					destinations: this.savedLead.destinations,
					weddingDate: this.savedLead.weddingDate,
					weddingDateConfirmed: this.savedLead.weddingDateConfirmed,
					travelStartDate: this.savedLead.travelStartDate,
					travelEndDate: this.savedLead.travelEndDate,
					status: this.savedLead.status,
					travelAgentRequested: this.savedLead.travelAgentRequested,
					referralSource: this.savedLead.referralSource,
					facebookGroup: this.savedLead.facebookGroup,
					referredBy: this.savedLead.referredBy,
					message: this.savedLead.message,
					lastAttempt: this.savedLead.lastAttempt,
					respondedOn: this.savedLead.respondedOn,
					releaseRoomsBy: this.savedLead.releaseRoomsBy,
					balanceDueDate: this.savedLead.balanceDueDate,
					cancellationDate: this.savedLead.cancellationDate,
					contractSentOn: this.savedLead.contractSentOn,
					notes: this.savedLead.notes,
					contactedUsBy: this.savedLead.contactedUsBy,
					contactedUsDate: this.savedLead.contactedUsDate,
				};
			},
			statusClass(status) {
				switch (status) {
					case 'Unassigned': return 'is-warning';
					case 'Assigned': return 'is-white';
					case 'Pending Rates': return 'has-background-sandstone';
					case 'Received Rates': return 'has-background-charcoal has-text-white';
					case 'Pending K': return 'has-background-dusty-rose has-text-white';
					case 'Pending Deposit': return 'has-background-mauve has-text-white';
					case 'Signed K': return 'is-success has-text-black';
					case 'Declined': return 'is-light';
					default: return 'is-light';
				}
			},
			setBreadcrumbs() {
				this.$store.commit('breadcrumbs', [
					{
						label: 'Dashboard',
						route: 'home'
					},
					{
						label: 'Leads',
						route: 'leads'
					},
					{
						label: this.savedLead.name,
						route: 'leads.show',
						params: {
							id: this.savedLead.id
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

				let request = this.$http.put('/leads/' + this.$route.params.id, this.lead)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The lead has been updated.'
						});

						this.savedLead = response.data.data;
						this.leadErrors = [];

						this.assignLead();
						this.setBreadcrumbs();
					}).catch(error => {
						if (error.response.status === 422) {
							this.leadErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			onProposalUploaded(leadHotel) {
				if (leadHotel.proposalDocument && !leadHotel.receivedOn) {
					leadHotel.receivedOn = this.$moment().format('YYYY-MM-DD');
				}
			},
			updateHotelRequests() {
				this.isLoading = 'updateHotelRequests';

				let request = this.$http.put('/leads/' + this.$route.params.id + '/hotel-requests', {leadProviders: this.leadProviders})
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The hotel requests have been updated.'
						});

						this.savedLead = response.data.data;
						this.leadProviders = this.savedLead.leadProviders;
						this.leadProviderErrors = {};

						this.assignLead();
					}).catch(error => {
						if (error.response.status === 422) {
							this.leadProviderErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			openSupplierEmailModal(leadProvider) {
				this.showSupplierEmailModal = true;
				this.supplierEmail= {};
				this.supplierEmailErrors = [];

				const provider = this.providers.find(provider => provider.id === leadProvider.providerId);
				const site = this.savedLead.site == 'Unknown' ? 'Unknown Site' : this.savedLead.site; 
				const travelAgent = this.savedLead.travelAgentId ? this.travelAgents.find(travelAgent => travelAgent.value === this.savedLead.travelAgentId) : null;

				let body = '';

				if (leadProvider.specialistId) {
					const specialist = provider.specialists.find(specialist => specialist.id === leadProvider.specialistId);
					this.supplierEmail.email = specialist.email;
          const firstName = specialist.name.trim().split(' ')[0];
				  body += `Hi ${firstName},\n\n`;
				} else {
					this.supplierEmail.email = provider.email ?? '';
					body += `Hi,\n\n`;
				}

				body += `Please request rates for me:\n\n`

				leadProvider.leadHotels.forEach(leadHotel => {
					if (leadHotel.hotel && leadHotel.requestedOn && leadHotel.weddingDate && leadHotel.travelStartDate && leadHotel.travelEndDate && !leadHotel.receivedOn && !leadHotel.proposalDocument) {
						const weddingDateFormatted = this.$moment(leadHotel.weddingDate).format('MMMM D, YYYY');
						const travelStartFormatted = this.$moment(leadHotel.travelStartDate).format('MMMM D, YYYY');
						const travelEndFormatted = this.$moment(leadHotel.travelEndDate).format('MMMM D, YYYY');

						body += `${leadHotel.hotel}\n`;
						body += `${weddingDateFormatted} Wedding Date, ${site}, ${this.savedLead.weddingDateConfirmed ? 'Confirmed' : 'Not Yet Confirmed'}.\n`;

						if (travelStartFormatted && travelEndFormatted) {
							body += `Travel Dates: ${travelStartFormatted} - ${travelEndFormatted}\n\n`;
						} else {
							body += `\n`;
						}
					}
				});

				body += `Wedding Couple: ${this.savedLead.name}\n`;
				body += `# rooms: ${this.savedLead.numberOfRooms ?? '0'}  \n`;

				if (travelAgent) {
					body += `TA: ${travelAgent.text}\n\n`;
				} else {
					body += `\n`;
				}

				body +=`Thank you!`;

				this.supplierEmail.body = body;
				this.supplierEmail.supplierIdentifier = provider?.abbreviation;
			},
			closeSupplierEmailModal() {
				this.showSupplierEmailModal = false;
				this.supplierEmail= {};
				this.supplierEmailErrors = [];
			},
			sendSupplierEmail() {
				this.isLoading = 'sendSupplierEmail';

				let request = this.$http.post('/leads/' + this.$route.params.id + '/supplier-email', this.supplierEmail)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The email has been sent.'
						});

						this.closeSupplierEmailModal();
					}).catch(error => {
						if (error.response.status === 422) {
							this.supplierEmailErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			openConvertProposalDocumentModal(leadHotel) {
				this.leadHotelToConvert = leadHotel;
				this.showConvertProposalDocumentModal = true;
			},
			closeConvertProposalDocumentModal() {
				this.showConvertProposalDocumentModal = false;
				this.leadHotelToConvert = null;
			},
			convertProposalDocument() {
				this.isLoading = 'convertProposalDocument';

				let request = this.$http.post('/leads/' + this.$route.params.id + '/convert-proposal-document', this.leadHotelToConvert)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The document is being converted. It will be sent to you via email once the conversion process is completed.'
						});

						this.closeConvertProposalDocumentModal();
					}).catch(error => {
						//
					});

				request.then(() => {
					this.isLoading = '';
				});
			},
			deleted() {
				this.$router.push({
					name: 'leads'
				});
			}
		}
	}
</script>