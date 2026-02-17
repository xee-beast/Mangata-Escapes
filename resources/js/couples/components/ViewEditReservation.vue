<template>
    <div>
        <button @click="show = true" class="button is-medium is-fat is-rounded is-outlined is-black">EDIT RESERVATION</button>
        <modal :is-active="show" @hide="hideModal">
            <div class="form-container">
                <div class="form-header">
                    <div class="form-title">{{ group.name }} - Edit Reservation</div>
                    <button type="button" class="modal-close-booking" aria-label="close" @click="close">
                        <span class="icon"><i class="fas fa-times"></i></span>
                    </button>
                </div>
                <div v-if="!isWithin21DaysOfEvent" class="form-content is-font-family-montserrat">
                    <lost-code v-if="lostCode" @sent="lostCode = false" :group="group.id" return-text="Back" />
                    <div v-else>
                        <!-- Step 1: Email/Code Form -->
                        <template v-if="!reservationLoaded && step == 1 && confirmed">
                            <client-form v-model="reservation.booking" :error-bag="reservationErrors" @codeLost="lostCode = true" />
                        </template>

                        <!-- Step 2: booking is not confirmed -->
                        <template v-else-if="!reservationLoaded && step == 1 && !confirmed && !guestChangesMessage">
                            <div class="field">
                                <p>
                                    This feature will be available for this booking once it has been confirmed with the provider.
                                    <br>
                                    Please allow up to 3 business days to process your booking. We appreciate your patience.
                                </p>
                            </div>
                        </template>

                        <!-- Has Guest Changes -->
                        <template v-else-if="guestChangesMessage">
                            <span class="is-size-5 has-text-weight-normal" v-html="guestChangesMessage"></span>
                        </template>

                        <!-- Step 3: Reservation Details -->
                        <template v-else-if="reservationLoaded && step == 2">

                            <!-- Room Arrangements -->
                            <div>
                                <form-panel label="Room Arrangements" class="is-borderless"  style="padding: 0.5rem; border: 1px solid #dbdbdb; border-radius: 4px;">
                                    <template v-slot:action>
                                        <button v-if="bookingData.roomArrangements" class="button is-small is-link is-outlined" @click="addRoomArrangement">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </template>
                                    <div style="display: grid; row-gap: 5px;">
                                        <form-panel
                                            v-for="(roomArrangement, index) in bookingData.roomArrangements"
                                            :key="'arrangement-' + index"
                                            class="is-borderless"
                                            style="padding: 0.5rem; border: 1px solid #dbdbdb; border-radius: 4px;"
                                        >
                                            <template v-slot:action>
                                                <button class="button is-small is-link is-outlined" @click="removeRoomArrangement(index)">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </template>
                                            <div class="columns is-variable is-2">
                                                <div class="column">
                                                    <form-field label="Hotel" :errors="reservationErrors[`booking.roomArrangements.${index}.hotel`] || []">
                                                        <control-select
                                                            style="width: 100%; display: block;"
                                                            v-model.number="roomArrangement.hotel"
                                                            :options="hotels.map(h => ({ value: h.value, text: h.text || String(h.value) }))"
                                                            first-is-empty
                                                            :key="'hotel-' + index"
                                                            @change="updateRoomOptions(index)"
                                                            :class="{ 'is-danger': (reservationErrors[`booking.roomArrangements.${index}.hotel`] || []).length }"
                                                        />
                                                    </form-field>
                                                </div>
                                                <div class="column">
                                                    <form-field label="Room" :errors="reservationErrors[`booking.roomArrangements.${index}.room`] || []">
                                                        <control-select
                                                            style="width: 100%; display: block;"
                                                            v-model.number="roomArrangement.room"
                                                            :options="getRoomOptions(roomArrangement.hotel)"
                                                            first-is-empty
                                                            :key="'room-' + roomArrangement.hotel + '-' + index"
                                                            @change="updateBedOptions(index)"
                                                            :class="{ 'is-danger': (reservationErrors[`booking.roomArrangements.${index}.room`] || []).length }"
                                                        />
                                                    </form-field>
                                                </div>
                                            </div>
                                            <div class="columns is-variable is-2">
                                                <div class="column">
                                                    <form-field label="Bed Type" :errors="reservationErrors[`booking.roomArrangements.${index}.bed`] || []">
                                                        <control-select
                                                            style="width: 100%; display: block;"
                                                            v-model="roomArrangement.bed"
                                                            :options="getBedOptions(roomArrangement.hotel, roomArrangement.room)"
                                                            first-is-empty
                                                            :key="'bed-' + roomArrangement.hotel + '-' + roomArrangement.room + '-' + index"
                                                            @change="updateBeddingAgreement(index)"
                                                            :class="{ 'is-danger': (reservationErrors[`booking.roomArrangements.${index}.bed`] || []).length }"
                                                        />
                                                    </form-field>
                                                </div>
                                                <div class="column">
                                                    <form-field label="Dates" :errors="[...(reservationErrors[`booking.roomArrangements.${index}.dates.start`] || []), ...(reservationErrors[`booking.roomArrangements.${index}.dates.end`] || [])]">
                                                        <date-picker
                                                            is-range
                                                            v-model="roomArrangement.dates"
                                                            :min-date="$moment(group.date).subtract('10', 'days').toDate()"
                                                            :max-date="$moment(group.date).add('10', 'days').toDate()"
                                                            :color="selectedColor"
                                                        >
                                                            <template v-slot="{ inputValue, inputEvents }">
                                                                <input
                                                                    :value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
                                                                    v-on="inputEvents.start"
                                                                    class="input"
                                                                    :class="{ 'is-danger': [...(reservationErrors[`booking.roomArrangements.${index}.dates.start`] || []), ...(reservationErrors[`booking.roomArrangements.${index}.dates.end`] || [])].length }"
                                                                />
                                                            </template>
                                                        </date-picker>
                                                    </form-field>
                                                </div>
                                            </div>
                                            <div class="columns is-variable is-2 transport-row">
                                                <div class="control mt-10">
                                                    <label class="checkbox is-dusty-rose" style="margin: 10px">
                                                        <input type="checkbox" v-model="roomArrangement.beddingAgreement" :class="{ 'is-danger': (reservationErrors[`booking.roomArrangements.${index}.beddingAgreement`] || []).length }">
                                                        <span>I understand that bedding is a special request and is subject to availability.</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <p v-if="(reservationErrors[`booking.roomArrangements.${index}.beddingAgreement`] || []).length" class="help is-danger">You must agree with the conditions.</p>
                                            <div v-if="canUpdateGuestTravelDates" class="columns is-variable is-2">
                                                <div class="column is-flex" style="align-items: center; gap: 10px;">
                                                    <button type="button" class="button is-link is-outlined" @click="updateGuestTravelDates">
                                                        Apply Dates to All Guests
                                                    </button>
                                                      <span v-if="showApplyDatesMessage" class="has-color-charcoal has-text-weight-semibold">
                                                          <i class="fas fa-check-circle"></i> Dates applied to all guests
                                                      </span>
                                                </div>
                                            </div>
                                        </form-panel>
                                    </div>
                                    <div class="column" style="margin-top: 5px; padding-left: 3px;">
                                        <form-field label="Special Requests" :errors="reservationErrors['booking.specialRequests'] || []">
                                            <textarea v-model="bookingData.specialRequests" class="textarea" rows="2"></textarea>
                                        </form-field>
                                    </div>
                                </form-panel>
                            </div>

                            <!-- Separate Clients -->
                            <div v-if="!group.disableInvoiceSplitting" style="padding: 0.5rem; border: 1px solid #dbdbdb; border-radius: 4px; margin-top: 1rem;">
                                <form-panel label="Invoice Splitting" class="is-borderless">
                                    <div class="field">
                                        <label class="label">If sharing room would you like to be invoiced separately?</label>
                                        <div class="control">
                                            <label class="radio">
                                                <input type="radio" v-model="hasSeperateClients" :value="true">
                                                Yes
                                            </label>
                                            <label class="radio">
                                                <input type="radio" v-model="hasSeperateClients" :value="false">
                                                No
                                            </label>
                                        </div>
                                        <p v-if="'seperateClients' in reservationErrors" class="help is-danger">{{ reservationErrors['seperateClients'][0] }}</p>
                                    </div>
                                    <template v-if="hasSeperateClients">
                                        <div class="field">
                                            <label class="label">Who else should be invoiced?</label>
                                            <div class="field-body">
                                                <div class="field">
                                                    <div class="control">
                                                        <input type="text" v-model="newClient.firstName" class="input is-capitalized" :class="{ 'is-danger': ('newClient.firstName' in newClientErrors) }" placeholder="First Name">
                                                    </div>
                                                    <p v-if="'newClient.firstName' in newClientErrors" class="help is-danger">{{ newClientErrors['newClient.firstName'][0] }}</p>
                                                </div>
                                                <div class="field">
                                                    <div class="control">
                                                        <input type="text" v-model="newClient.lastName" class="input is-capitalized" :class="{ 'is-danger': ('newClient.lastName' in newClientErrors) }" placeholder="Last Name">
                                                    </div>
                                                    <p v-if="'newClient.lastName' in newClientErrors" class="help is-danger">{{ newClientErrors['newClient.lastName'][0] }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <div class="control">
                                                <input type="number" v-model="newClient.phone" class="input" :class="{ 'is-danger': ('newClient.phone' in newClientErrors) }" placeholder="Phone Number">
                                            </div>
                                            <p v-if="'newClient.phone' in newClientErrors" class="help is-danger">{{ newClientErrors['newClient.phone'][0] }}</p>
                                        </div>
                                        <div class="field">
                                            <div class="control">
                                                <div class="field has-addons">
                                                    <div class="control is-expanded">
                                                        <input type="text" v-model="newClient.email" @keyup.enter="addSeperateClient" class="input" :class="{ 'is-danger': ('newClient.email' in newClientErrors) }" placeholder="Email">
                                                    </div>
                                                    <div class="control">
                                                        <button @click="addSeperateClient" type="button" class="button is-outlined is-dark" style="padding: 7px;" :class="{ 'is-loading': newClientIsLoading }">Add</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <p v-if="'newClient.email' in newClientErrors" class="help is-danger">{{ newClientErrors['newClient.email'][0] }}</p>
                                        </div>
                                        <div v-if="seperateClients.length > 0" class="table-container">
                                            <table class="table is-fullwidth">
                                                <tbody>
                                                    <tr v-for="(client, index) in seperateClients" :key="client.email">
                                                        <td class="is-capitalized">{{ client.firstName }} {{ client.lastName }}</td>
                                                        <td>{{ client.email }}</td>
                                                        <td class="has-text-right">
                                                            <a @click="removeSeperateClient(index)">
                                                                <span class="icon has-text-link" style="color: #C7979C !important;">
                                                                    <i class="fas fa-times"></i>
                                                                </span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>
                                </form-panel>
                            </div>

                            <!-- Guests -->
                            <div style="padding: 0.5rem; border: 1px solid #dbdbdb; border-radius: 4px; margin-top: 1rem; ">
                                <form-panel label="Guests" class="is-borderless">
                                    <template v-slot:action>
                                        <button v-if="guestsData" class="button is-small is-link is-outlined" @click="addGuest">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </template>
                                    <div style="display: grid; row-gap: 7px;">
                                        <form-panel v-for="(guest, index) in guestsData.filter(g => !g.deleted_at)" :key="index" :label="'Guest ' + (index + 1)" class="is-borderless" style="padding: 0.5rem; border: 1px solid #dbdbdb; border-radius: 4px;">
                                            <template v-slot:action>
                                                <button class="button is-small is-link is-outlined" @click="removeGuest(guest.id, index)" v-if="activeGuestCount > 1 ">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </template>
                                            <div class="columns">
                                                <div class="column">
                                                    <form-field label="First Name" :errors="reservationErrors[`guests.${index}.firstName`] || []">
                                                        <control-input
                                                            v-model="guest.firstName"
                                                            class="is-capitalized"
                                                            :class="{ 'is-danger': (reservationErrors[`guests.${index}.firstName`] || []).length }"
                                                        />
                                                    </form-field>
                                                    <p v-if="'duplicate_guests_in_request' in reservationErrors && reservationErrors.duplicate_guests_in_request.includes(index)" class="help is-danger">This guest is being duplicated.</p>
                                                    <p v-if="'duplicate_guests' in reservationErrors && reservationErrors.duplicate_guests.includes(index) && !('duplicate_guests_in_request' in reservationErrors && reservationErrors.duplicate_guests_in_request.includes(index))" class="help is-danger">A guest with this name and date of birth is already booked. Please reach out to <a target="_blank" style="color: black; font-weight: bold;" :href="`mailto:${group.groupsEmail}`"><u>{{ group.groupsEmail }}</u></a> for further assistance.</p>
                                                </div>
                                                <div class="column">
                                                    <form-field label="Last Name" :errors="reservationErrors[`guests.${index}.lastName`] || []">
                                                        <control-input
                                                            v-model="guest.lastName"
                                                            class="is-capitalized"
                                                            :class="{ 'is-danger': (reservationErrors[`guests.${index}.lastName`] || []).length }"
                                                        />
                                                    </form-field>
                                                </div>
                                            </div>
                                            <div class="columns">
                                                <div class="column">
                                                    <form-field label="Date of Birth" :errors="reservationErrors[`guests.${index}.birthDate`] || []">
                                                        <date-picker v-model="guest.birthDate" :color="selectedColor">
                                                            <template v-slot="{ inputValue, inputEvents }">
                                                                <input class="input" :value="inputValue" v-on="inputEvents" />
                                                            </template>
                                                        </date-picker>
                                                    </form-field>
                                                </div>
                                                <div class="column">
                                                  <form-field label="Travel Dates" :errors="[...(reservationErrors[`guests.${index}.dates.start`] || []), ...(reservationErrors[`guests.${index}.dates.end`] || [])]">
                                                      <date-picker
                                                          is-range
                                                          v-model="guest.dates"
                                                          :min-date="$moment(group.date).subtract('10', 'days').toDate()"
                                                          :max-date="$moment(group.date).add('10', 'days').toDate()"
                                                          :color="selectedColor"
                                                      >
                                                          <template v-slot="{ inputValue, inputEvents }">
                                                              <input
                                                                  :value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
                                                                  v-on="inputEvents.start"
                                                                  class="input"
                                                              />
                                                          </template>
                                                      </date-picker>
                                                  </form-field>
                                                </div>
                                            </div>
                                            <div class="columns">
                                              <div class="column">
                                                  <form-field label="Gender" :errors="reservationErrors[`guests.${index}.gender`] || []">
                                                      <control-radio
                                                          v-model="guest.gender"
                                                          :options="[{value: 'M', text: 'Male'}, {value: 'F', text: 'Female'}]"
                                                      />
                                                  </form-field>
                                              </div>
                                              <div v-if="group.transportation" class="column">
                                                  <form-field label="Transportation" :errors="reservationErrors[`guests.${index}.transportation`] || []">
                                                      <control-radio
                                                          v-model="guest.transportation"
                                                          :options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
                                                      />
                                                  </form-field>
                                              </div>
                                            </div>
                                            <div class="columns is-variable is-2 transport-row">
                                                <div class="column" v-if="guest.transportation && group.transportation">
                                                    <form-field label="Custom Airport">
                                                        <control-select
                                                            style="width: 100%; display: block;"
                                                            v-model.number="guest.customGroupAirport"
                                                            :options="customGroupAirports"
                                                        />
                                                    </form-field>
                                                </div>
                                                <div class="column" v-if="guest.transportation && group.transportation">
                                                    <form-field label="Transfer Type">
                                                        <control-select
                                                            style="width: 100%; display: block;"
                                                            v-model="guest.transportation_type"
                                                            :options="transportationTypes"
                                                        />
                                                    </form-field>
                                                </div>
                                            </div>
                                            <div class="columns is-variable is-2 transport-row">
                                              <div class="column">
                                                <form-field :label="!guest.id ? 'Do you wish to purchase travel insurance?' : 'Travel Insurance'" :errors="reservationErrors[`guests.${index}.insurance`] || []">
                                                    <div class="control">
                                                      <div v-if="!guest.id">
                                                          <div>
                                                              <label class="radio">
                                                                  <input type="radio" :value="true" v-model="guest.insurance">
                                                                  Yes, I would like to purchase travel insurance and understand that once purchased <b>the cost of travel insurance is non-refundable</b>.
                                                              </label>
                                                          </div>
                                                          <div>
                                                              <label class="radio">
                                                                  <input type="radio" :value="false" v-model="guest.insurance">
                                                                  No, I am <b>not interested in purchasing travel insurance</b> and acknowledge that I have been offered but choose to decline this coverage. I understand the risks in not purchasing travel protection.
                                                              </label>
                                                          </div>
                                                        </div>
                                                        <div v-else>
                                                            <control-radio
                                                                v-model="guest.insurance"
                                                                :options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]"
                                                                :disabled="guest.hasGuestInsuranceDisabled"
                                                            />
                                                          <span v-if="guest.insurance != true" class="span-description">Adding travel insurance to your reservation may not be too late, although it may come with some lost benefits like cancel for any reason, which must be added within 4–17 days of booking, depending on the policy.</span>
                                                        </div>
                                                        <div v-if="isActivelyDecliningInsurance(guest)">

                                                            <div class="control mt-10">
                                                                <label class="checkbox">
                                                                    <input type="checkbox" v-model="guest.declinedInsuranceAgreements.first" :class="{ 'is-danger': (reservationErrors[`guests.${index}.declinedInsuranceAgreements.first`] || []).length }">
                                                                    I understand that by declining travel insurance, that I will not be reimbursed for cancelling my reservation after {{ group.cancellationDate }}.
                                                                </label>
                                                            </div>
                                                            <p v-if="(reservationErrors[`guests.${index}.declinedInsuranceAgreements.first`] || []).length" class="help is-danger">You must agree with the conditions.</p>

                                                            <div class="control mt-10">
                                                                <label class="checkbox">
                                                                    <input type="checkbox" v-model="guest.declinedInsuranceAgreements.second" :class="{ 'is-danger': (reservationErrors[`guests.${index}.declinedInsuranceAgreements.second`] || []).length }">
                                                                    I understand that after {{ group.cancellationDate }}, I will not be able to cancel for a refund even if one or if one or more of the guests on this reservation is/are unable to attend for any reason, including but not limited to:
                                                                    <ul style="list-style: inherit; padding: 0px 10px 0px 20px;">
                                                                        <li>Illness</li>
                                                                        <li>Testing positive for COVID</li>
                                                                        <li>Pregnancy</li>
                                                                        <li>Inability to have time off work approved</li>
                                                                        <li>Military service</li>
                                                                        <li>Weather-related issues including hurricanes, snow storms, or natural disasters that prevent you from being able to travel, or any other reason. </li>
                                                                        <li>Or any other reason. </li>
                                                                    </ul>
                                                                </label>
                                                            </div>
                                                            <p v-if="(reservationErrors[`guests.${index}.declinedInsuranceAgreements.second`] || []).length" class="help is-danger">You must agree with the conditions.</p>

                                                            <div class="control mt-10">
                                                                <label class="checkbox">
                                                                    <input type="checkbox" v-model="guest.declinedInsuranceAgreements.third" :class="{ 'is-danger': (reservationErrors[`guests.${index}.declinedInsuranceAgreements.third`] || []).length }">
                                                                    I understand that the only way to protect my reservation is by purchasing travel insurance.
                                                                </label>
                                                            </div>
                                                            <p v-if="(reservationErrors[`guests.${index}.declinedInsuranceAgreements.third`] || []).length" class="help is-danger">You must agree with the conditions.</p>

                                                            <div class="control mt-10">
                                                                <label class="checkbox">
                                                                  <input type="checkbox" v-model="guest.declinedInsuranceAgreements.fourth" :class="{ 'is-danger': (reservationErrors[`guests.${index}.declinedInsuranceAgreements.fourth`] || []).length }">
                                                                    I understand that if I did not purchase travel insurance and wish to cancel, reduce the guest count, downgrade my room category, or make any other changes to the reservation that would have resulted in a refund prior to {{ group.cancellationDate }}, that I will not receive a refund and I agree not to dispute my charges in this event.
                                                                </label>
                                                            </div>
                                                            <p v-if="(reservationErrors[`guests.${index}.declinedInsuranceAgreements.fourth`] || []).length" class="help is-danger">You must agree with the conditions.</p>
                                                        </div>
                                                    </div>
                                                </form-field>
                                              </div>
                                            </div>
                                            <div class="columns is-variable is-2 transport-row">
                                              <div class="column">
                                                <form-field label="Invoiced To" :errors="reservationErrors[`guests.${index}.client`] || []">
                                                    <control-select
                                                        style="width: 100%; display: block;"
                                                        v-model="guest.client"
                                                        :options="allClients.map(c => ({ value: c.value, text: c.text || String(c.value) }))"
                                                        first-is-empty
                                                        :key="'client-' + index"
                                                    />
                                                </form-field>
                                              </div>
                                            </div>
                                        </form-panel>
                                    </div>
                                </form-panel>
                            </div>
                        </template>
                    </div>
                    <template v-if="step == 3">
                        <!-- No changes detected -->
                        <div v-if="noChangesDetected">
                            <p class="is-size-5 has-text-weight-normal">
                                No changes detected. Reservation remains unchanged.
                            </p>
                        </div>

                        <!-- Balance due date passed && no payment plan -->
                        <div v-if="((hasBalanceDueDatePassed && !hasPaymentPlan) || hasGuestRequiringInsurancePayment) && !showSuccess">
                            <div>
                                <p class="heading">Booking Details</p>
                            </div>
                            <div class="columns">
                                <div class="column is-narrow">
                                    <div class="columns is-mobile" style="margin-left: 0;">
                                        <div class="column is-half-mobile has-background-grey-lighter has-text-right">
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">Subtotal</div>
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">Payments</div>
                                            <div v-if="paymentDetails && (!paymentDetails.showOnlyInsurance || paymentDetails.balanceAmount < 0)">Balance</div>
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">Total After Changes</div>
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">Cost of Changes</div>
                                            <div v-if="hasGuestRequiringInsurancePayment && paymentDetails && paymentDetails.showOnlyInsurance">Travel Insurance</div>
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">Change Fee</div>
                                            <div class="has-text-weight-bold">Total</div>
                                        </div>
                                        <div class="column is-narrow has-background-primary">
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">${{ paymentDetails && paymentDetails.total ? paymentDetails.total : '0.00' }}</div>
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">${{ paymentDetails && paymentDetails.payments ? paymentDetails.payments : '0.00' }}</div>
                                            <div v-if="paymentDetails && (!paymentDetails.showOnlyInsurance || paymentDetails.balanceAmount < 0)">${{ paymentDetails && paymentDetails.balanceAmount ? paymentDetails.balanceAmount : '0.00' }}</div>
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">${{ paymentDetails && paymentDetails.totalAfterChange ? paymentDetails.totalAfterChange : '0.00' }}</div>
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">${{ paymentDetails && paymentDetails.changesCost ? paymentDetails.changesCost : '0.00' }}</div>
                                            <div v-if="paymentDetails && paymentDetails.showOnlyInsurance">${{ paymentDetails && paymentDetails.insuranceCost ? paymentDetails.insuranceCost : '0.00' }}</div>
                                            <div v-if="paymentDetails && !paymentDetails.showOnlyInsurance">${{ paymentDetails && paymentDetails.changeFee ? paymentDetails.changeFee : '0.00' }}</div>
                                            <div class="has-text-weight-bold">${{ paymentDetails && paymentDetails.totalRequired ? paymentDetails.totalRequired : '0.00' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="column">
                                    <div class="field">
                                        <label class="label">Payment Amount</label>
                                        <p v-if="paymentDetails && paymentDetails.totalRequired" class="help for-label">Minimum Deposit: ${{ paymentDetails.totalRequired }}</p>
                                        <input type="number" v-model="paymentAmount" class="input" :class="{ 'is-danger': ('amount' in paymentErrors) }">
                                        <p v-if="'amount' in paymentErrors" class="help is-danger">{{ paymentErrors['amount'][0] }}</p>
                                    </div>
                                    <div class="field">
                                        <label class="label">Payment Type</label>
                                        <div class="control">
                                            <div class="select is-fullwidth">
                                                <select v-model="paymentType">
                                                    <option value="Payment towards balance">Payment towards balance</option>
                                                    <option value="Transfers">Transfers</option>
                                                    <option value="Travel insurance">Travel insurance</option>
                                                    <option value="Final payment">Final payment</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="paymentCardOnFile" class="field">
                                <label class="checkbox">
                                    <input type="checkbox" v-model="paymentUseCardOnFile">
                                    Use card on file (<span class="is-capitalized">{{ paymentCardOnFile.type }}</span> ending in {{ paymentCardOnFile.lastDigits }})
                                </label>
                                <p v-if="'useCardOnFile' in paymentErrors" class="help is-danger">{{ paymentErrors['useCardOnFile'] }}</p>
                            </div>
                            <div v-if="!paymentUseCardOnFile" class="card-details">
                                <div>
                                    <p class="heading">Payment Details</p>
                                </div>
                                <div class="field">
                                    <label class="checkbox">
                                        <input type="checkbox" v-model="paymentUpdateCardOnFile">
                                        Update card on file
                                    </label>
                                    <p v-if="'updateCardOnFile' in paymentErrors" class="help is-danger">{{ paymentErrors['updateCardOnFile'] }}</p>
                                </div>
                                <credit-card-form v-model="paymentCard" :error-bag="paymentErrors" />
                                <hr/>
                                <div>
                                    <p class="heading">Billing Address</p>
                                </div>
                                <address-form v-model="paymentAddress" :error-bag="paymentErrors" :countries="countries" />
                            </div>
                            <hr/>
                            <div>
                                <p class="heading">Terms & Conditions</p>
                            </div>
                            <terms-conditions-form v-model="paymentConfirmation" :errors="paymentErrors" :url="group.termsConditionsUrl" :client="computedPaymentClientName" :group="group" />
                            <div v-if="paymentErrors.general" class="notification is-danger">
                                {{ paymentErrors.general }}
                            </div>
                        </div>


                        <!-- Success Message -->
                        <div v-if="showSuccess && !noChangesDetected && !showFitGroupSuccessMessage">
                            <p class="is-size-5 has-text-weight-normal">
                                We’ve received your change request and our team is working on it.
                            </p>
                        </div>

                        <!-- Balance due date passed && payment plan  Or balance due date not passed -->
                        <div v-if="showSuccess && !showFitGroupSuccessMessage && ((hasPaymentPlan && hasBalanceDueDatePassed) || !hasBalanceDueDatePassed) || (hasGuestRequiringInsurancePayment && !this.step == 3)">
                            <p class="is-size-5 has-text-weight-normal">
                                Please note that your payment plan may need to be altered to accommodate the change.
                            </p>
                        </div>

                        <div v-if="showNewClientSuccessMessage && showSuccess && !showFitGroupSuccessMessage">
                            <p class="is-size-5 has-text-weight-normal" style="margin-top: 5px;">
                              You have requested that a guest be moved to another client. Any refunds due will be calculated and issued after we receive any payments due. Please contact <a class="has-text-link" :href="`mailto:${group.groupsEmail}`">{{ group.groupsEmail }}</a> with any questions.
                            </p>
                        </div>

                        <!-- Fit Group Success Message -->
                        <div v-if="showSuccess && showFitGroupSuccessMessage && !noChangesDetected" style="margin-top: 5px;">
                            <p class="is-size-5 has-text-weight-normal">
                                We’ve received your change request and our team is working on it. Please note that your payment plan might need to be altered. Rates are not fixed, and any requested change will require rebuilding the reservation with a new rate.
                            </p>
                        </div>
                    </template>
                </div>
                <div v-else>
                    <!-- Event is within 21 days -->
                    <div class="form-content">
                        <span class="is-size-5 has-text-weight-normal">
                            We cannot accept change requests this close to the event. Please contact Barefoot Bridal at <a class="has-text-link" :href="`mailto:${group.groupsEmail}`">{{ group.groupsEmail }}</a> directly to request changes.
                        </span>
                    </div>
                </div>
                <!-- Footer -->
                <div v-if="!lostCode && !isWithin21DaysOfEvent && !showSuccess" class="form-footer">
                    <div class="columns is-mobile">
                        <div class="column">
                            <button v-if="step > 1 || !confirmed" @click="back" class="button is-dark is-outlined" :disabled="isLoading">Back</button>
                        </div>
                        <div class="column is-narrow">
                            <button v-if="confirmed" :disabled="!hasChanges && step == 2"  @click="handleStep" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }"> {{ nextOrSubmitLabel }} </button>
                        </div>
                    </div>
                </div>
            </div>
        </modal>
        <modal :is-active="guestErrors.length > 0" @hide="guestErrors = []">
          <div class="form-container">
            <div class="form-header">
              <div class="form-title">Guest Warning(s)</div>
                <button type="button" class="modal-close-booking" aria-label="close" @click="guestErrors = []">
                    <span class="icon"><i class="fas fa-times"></i></span>
                </button>
            </div>
            <div class="form-content">
              <ul style="list-style: inside disc; padding: 15px;">
                  <li v-for="(error, index) in guestErrors" :key="index" class="is-size-5 has-text-weight-normal" v-html="error" style="margin-bottom: 10px;"></li>
              </ul>
            </div>
          </div>
        </modal>
        <modal :is-active="showMinimumNightsModal" @hide="closeMinimumNightsModal">
          <div class="form-container">
            <div class="form-header">
              <div class="form-title">Confirmation Required</div>
              <button type="button" class="modal-close-booking" aria-label="close" @click="closeMinimumNightsModal">
                  <span class="icon"><i class="fas fa-times"></i></span>
              </button>
            </div>
            <div class="form-content">
              <ul style="list-style: disc; padding-left: 20px;">
                <li v-for="(message, index) in confirmationMessages" :key="'message-' + index" class="is-size-5 has-text-weight-normal" style="margin-bottom: 10px;">
                  {{ message }}
                </li>
              </ul>
            </div>
            <div class="form-footer">
              <div class="columns is-mobile">
                <div class="column"></div>
                <div class="column is-narrow">
                  <button @click="acceptMinimumNightsException" class="button is-dark is-outlined">Continue</button>
                </div>
              </div>
            </div>
          </div>
        </modal>
    </div>
</template>

<script>
import FormPanel from '@dashboard/components/form/Panel';
import FormField from '@dashboard/components/form/Field';
import ControlSelect from '@dashboard/components/form/controls/Select';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlRadio from '@dashboard/components/form/controls/Radio.vue';
import DatePicker from 'v-calendar/lib/components/date-picker.umd';
import { trim } from 'lodash';
import cloneDeep from 'lodash/cloneDeep';

export default {
    props: {
        group: { type: Object, required: true },
        countries: { type: Array, required: true },
    },

    components: {
        FormPanel,
        FormField,
        ControlSelect,
        ControlInput,
        ControlRadio,
        DatePicker,
    },
    data() {
        return {
            show: false,
            step: 1,
            isLoading: false,
            reservation: {
                booking: { email: null, code: null },
                validate: true
            },
            reservationErrors: {},
            lostCode: false,
            reservationLoaded: false,
            bookingData: {
                id: null,
                roomArrangements: [{ hotel: '', room: '', bed: '', dates: { start: null, end: null }, beddingAgreement: false }],
                specialRequests: '',
            },
            guestsData: [{
                firstName: '', lastName: '', birthDate: null, dates: { start: null, end: null }, gender: '',
                insurance: undefined, originalInsurance: undefined, transportation: false, transportation_type: null, customGroupAirport: null, client: null, hasGuestInsuranceDisabled: false,
                declinedInsuranceAgreements: { first: false, second: false, third: false, fourth: false}
            }],
            hotels: [],
            clients: [],
            customGroupAirports: [],
            transportationTypes: [],
            guestErrors: [],
            ignoreGuestError: false,
            confirmed: true,
            hasPaymentPlan: false,
            currentClientId: null,
            paymentDetails: null,
            paymentAmount: null,
            paymentType: 'Payment towards balance',
            paymentCardOnFile: null,
            paymentClientName: '',
            paymentErrors: {},
            paymentUseCardOnFile: true,
            paymentUpdateCardOnFile: false,
            paymentCard: {},
            paymentAddress: {},
            paymentConfirmation: {},
            showSuccess: false,
            noChangesDetected: false,
            originalBookingData: null,
            originalGuestsData: null,
            hasBalanceDueDatePassed: false,
            showFitGroupSuccessMessage: false,
            guestChangesMessage: '',
            showMinimumNightsModal: false,
            minimumNightsExceptionAccepted: false,
            confirmationMessages: [],
            selectedColor: 'pink',
            showApplyDatesMessage: false,
            hasSeperateClients: false,
            newClient: {
                firstName: null,
                lastName: null,
                email: null,
                phone: null
            },
            newClientErrors: {},
            newClientIsLoading: false,
            seperateClients: [],
            showNewClientSuccessMessage: false,
        }
    },
    watch: {
        'bookingData.roomArrangements': {
            handler(val) {
                val.forEach((arr, index) => {
                    if (!this.getRoomOptions(arr.hotel).some(r => r.value === arr.room)) {
                        this.$set(arr, 'room', '');
                        this.$set(arr, 'bed', '');
                        this.$set(arr, 'beddingAgreement', false);
                    }
                    if (!this.getBedOptions(arr.hotel, arr.room).some(b => b.value === arr.bed)) {
                        this.$set(arr, 'bed', '');
                        this.$set(arr, 'beddingAgreement', false);
                    }
                    if (!arr.bed && arr.beddingAgreement) {
                        this.$set(arr, 'beddingAgreement', false);
                    }
                });
            },
            deep: true
        },
        'guestsData': {
            handler(guests) {
                guests.forEach((guest, index) => {
                    if (!guest.declinedInsuranceAgreements) {
                        this.$set(guest, 'declinedInsuranceAgreements', { first: false, second: false, third: false, fourth: false, });
                    }

                    if (!guest.hasOwnProperty('originalInsurance')) {
                        this.$set(guest, 'originalInsurance', guest.insurance);
                    }
                });
            },
            deep: true
        }
    },
    computed: {
        isWithin21DaysOfEvent() {
            if (!this.group.date) return false;
            const today = new Date();
            const eventDate = new Date(this.group.date);
            const threeWeeksBeforeEvent = new Date(eventDate.getTime() - 21 * 24 * 60 * 60 * 1000);
            return today >= threeWeeksBeforeEvent && today <= eventDate;
        },
        nextOrSubmitLabel() {
            if (this.step == 1) {
                return 'Next';
            }

            if (this.step == 2  && !this.group.is_fit) {
                if (this.hasGuestRequiringInsurancePayment) {
                    return 'Next';
                }

                if (this.hasBalanceDueDatePassed && !this.hasPaymentPlan) {
                    return 'Next';
                }
            }

            return 'Submit';
        },
        hasGuestRequiringInsurancePayment() {
            return this.getGuestsRequiringInsurancePayment().length > 0;
        },
        guestsRequiringInsurancePaymentIndices() {
            return this.getGuestsRequiringInsurancePayment();
        },
        activeGuestCount() {
            return this.guestsData.filter(guest => !guest.deleted_at).length;
        },
        hasChanges() {
            if (!this.originalBookingData || !this.originalGuestsData) return false;

            const roomsChanged = JSON.stringify(this.bookingData.roomArrangements) !== JSON.stringify(this.originalBookingData.roomArrangements);
            const specialRequestsChanged = (trim(this.bookingData.specialRequests || '') || '') !== (trim(this.originalBookingData.specialRequests || '') || '');

            const currentActiveGuests = this.guestsData.filter(g => !g.deleted_at);
            const originalActiveGuests = this.originalGuestsData.filter(g => !g.deleted_at);

            if (currentActiveGuests.length !== originalActiveGuests.length) {
                return true;
            }

            const currentGuests = currentActiveGuests.map(g => ({
                id: g.id,
                firstName: g.firstName,
                lastName: g.lastName,
                gender: g.gender,
                birthDate: g.birthDate ? this.$moment(g.birthDate).toDate() : null,
                dates: {
                    start: g.dates && g.dates.start ? this.$moment(g.dates.start).toDate() : null,
                    end: g.dates && g.dates.end ? this.$moment(g.dates.end).toDate() : null
                },
                client: g.client,
                insurance: g.insurance,
                transportation: g.transportation,
                transportation_type: g.transportation_type,
                customGroupAirport: g.customGroupAirport,
            }));

            const originalGuests = originalActiveGuests.map(g => ({
                id: g.id,
                firstName: g.firstName,
                lastName: g.lastName,
                gender: g.gender,
                birthDate: g.birthDate ? this.$moment(g.birthDate).toDate() : null,
                dates: {
                    start: g.dates && g.dates.start ? this.$moment(g.dates.start).toDate() : null,
                    end: g.dates && g.dates.end ? this.$moment(g.dates.end).toDate() : null
                },
                client: g.client,
                insurance: g.insurance,
                transportation: g.transportation,
                transportation_type: g.transportation_type,
                customGroupAirport: g.customGroupAirport,
            }));

            const guestsChanged = JSON.stringify(currentGuests) !== JSON.stringify(originalGuests);

            return roomsChanged || specialRequestsChanged || guestsChanged;
        },
        computedPaymentClientName() {
            if (this.paymentUseCardOnFile && this.paymentCardOnFile) {
                return this.paymentCardOnFile.name || this.paymentClientName;
            } else {
                return this.paymentCard.name || '';
            }
        },
        canUpdateGuestTravelDates() {
            if (!this.bookingData || !this.bookingData.roomArrangements) return false;

            if (this.bookingData.roomArrangements.length !== 1) return false;

            const room = this.bookingData.roomArrangements[0];
            return room.dates && room.dates.start && room.dates.end;
        },
        allClients() {
            const existingClients = this.clients.map(c => ({
                value: c.value,
                text: c.text || String(c.value)
            }));
            const newClients = this.seperateClients.map(client => ({
                value: client.email,
                text: `${client.firstName} ${client.lastName}`
            }));
            return [...existingClients, ...newClients];
        }
    },
    methods: {
        getGuestsRequiringInsurancePayment() {
            const guestsRequiringPayment = [];

            this.guestsData.filter(guest => !guest.deleted_at).forEach((guest, index) => {
                if (this.currentClientId && guest.client !== this.currentClientId) {
                    return;
                }

                const currentInsurance = guest.insurance === true || guest.insurance === 1;

                if (!currentInsurance) {
                    return;
                }

                if (!guest.id) {
                    guestsRequiringPayment.push({
                        index: index,
                        id: null
                    });
                    return;
                }

                const oldInsurance = guest.originalInsurance;
                const hadInsurance = oldInsurance === true || oldInsurance === 1;

                if (!hadInsurance) {
                    guestsRequiringPayment.push({
                        index: index,
                        id: guest.id
                    });
                }
            });

            return guestsRequiringPayment;
        },
        isActivelyDecliningInsurance(guest) {
            if (!guest || guest.insurance !== false) {
                return false;
            }

            if (!guest.id) {
                return guest.insurance === false;
            }

            return guest.originalInsurance !== false;
        },
        closeMinimumNightsModal() {
            this.showMinimumNightsModal = false;
            this.isLoading = false;
            this.confirmationMessages = [];
        },
        acceptMinimumNightsException() {
            this.minimumNightsExceptionAccepted = true;
            this.showMinimumNightsModal = false;
            this.saveReservation();
        },
        hideModal() {
            this.show = false;
        },
        close() {
            if (this.lostCode) Object.assign(this.$data, this.$options.data.apply(this));

            this.show = false;
            this.lostCode = false;
            this.reservationLoaded = false;
            this.showNewClientSuccessMessage = false;
            if(this.step != 1){
                this.resetModalData();
            }

            this.step = 1;
        },
        next() {
            this.reservationErrors = {};
            this.isLoading = true;

            this.$http.post(`/groups/${this.group.id}/reservation`, this.reservation)
                .then(res => {
                    var mappedRoomArrangements = res.data.booking.roomArrangements.map(roomArrangement => {
                        return {
                            hotel: roomArrangement.hotel,
                            room: roomArrangement.room,
                            bed: roomArrangement.bed,
                            dates: {
                                start: roomArrangement.dates.start ? this.$moment(roomArrangement.dates.start).toDate() : null,
                                end: roomArrangement.dates.end ? this.$moment(roomArrangement.dates.end).toDate() : null,
                            },
                            beddingAgreement: true,
                        };
                    });

                    this.bookingData = {
                        id: res.data.booking.id,
                        roomArrangements: mappedRoomArrangements,
                        specialRequests: res.data.booking.specialRequests,
                    };

                    this.guestsData = res.data.guests.map(guest => {
                        return {
                            id: guest.id,
                            firstName: guest.firstName,
                            lastName: guest.lastName,
                            gender: guest.gender,
                            birthDate: guest.birthDate ? this.$moment(guest.birthDate).toDate() : null,
                            dates: {
                                start: guest.dates.start ? this.$moment(guest.dates.start).toDate() : null,
                                end: guest.dates.end ? this.$moment(guest.dates.end).toDate() : null,
                            },
                            client: guest.client,
                            insurance: guest.insurance,
                            originalInsurance: guest.insurance,
                            transportation: guest.transportation,
                            transportation_type: guest.transportation_type,
                            deleted_at: guest.deleted_at,
                            customGroupAirport: guest.customGroupAirport,
                            hasGuestInsuranceDisabled: guest.insurance == true,
                            declinedInsuranceAgreements: guest.insurance === false ? { first: true, second: true, third: true, fourth: true } : { first: false, second: false, third: false, fourth: false}
                        };
                    });

                    this.originalBookingData = JSON.parse(JSON.stringify(this.bookingData));
                    this.originalGuestsData = JSON.parse(JSON.stringify(this.guestsData));

                    this.hotels = res.data.hotels || [];
                    this.clients = res.data.clients || [];
                    this.customGroupAirports = res.data.customGroupAirports || [];
                    this.transportationTypes = res.data.transportationTypes || [];
                    this.reservationLoaded = true;
                    this.hasPaymentPlan = res.data.hasPaymentPlan;
                    this.hasBalanceDueDatePassed = res.data.hasBalanceDueDatePassed;
                    this.currentClientId = res.data.currentClientId;
                    this.step = 2;
                }).catch(error => {
                    if (error.response.status == 422) {
                        this.reservationErrors = error.response.data.errors;
                    }
                    if (error.response.status == 403) {
                        this.reservationLoaded = false;
                        this.step = 1;
                        this.confirmed = false;
                        this.guestChangesMessage = error.response.data.message;
                    }
                }).finally(() => {
                    this.isLoading = false;
                });
        },
        back() {
            this.step = this.step == 3 ? 2 : 1;
            if(this.guestChangesMessage || !this.reservationLoaded){
                this.confirmed = true;
                this.guestChangesMessage = '';
            }
            this.hasPaymentPlan = false;
            this.showNewClientSuccessMessage = false;
            if(this.step == 3 || this.step == 1){
                this.reservationLoaded = false;

                if(this.step == 1){
                  this.hasSeperateClients = false;
                  this.newClient = {
                    firstName: null,
                    lastName: null,
                    email: null,
                    phone: null
                  };

                  this.newClientErrors = {};
                  this.newClientIsLoading = false;
                  this.seperateClients = [];
                }

                if(this.step == 3){
                    this.confirmed = true;
                }
            }
        },
        handleStep() {
            if (this.step < 2) {
                this.next();
            } else {
                this.saveReservation();
            }
        },
        addRoomArrangement() {
            this.bookingData.roomArrangements.push({ hotel: '', room: '', bed: '', dates: { start: null, end: null }, beddingAgreement: false });
        },
        removeRoomArrangement(idx) {
            if (this.bookingData.roomArrangements.length > 1) {
                this.bookingData.roomArrangements.splice(idx, 1);
            }
        },
        updateRoomOptions(index) {
            const arr = this.bookingData.roomArrangements[index];
            this.$set(arr, 'room', '');
            this.$set(arr, 'bed', '');
            this.$set(arr, 'beddingAgreement', false);
        },
        updateBedOptions(index) {
            const arr = this.bookingData.roomArrangements[index];
            this.$set(arr, 'bed', '');
            this.$set(arr, 'beddingAgreement', false);
        },
        updateBeddingAgreement(index) {
            const arr = this.bookingData.roomArrangements[index];
            this.$set(arr, 'beddingAgreement', false);
        },
        addGuest() {
            this.guestsData.push({ firstName: '', lastName: '', birthDate: null, dates: { start: null, end: null }, gender: '', insurance: undefined, originalInsurance: undefined, transportation: false, transportation_type: null, customGroupAirport: null, client: null, deleted_at: false, declinedInsuranceAgreements: { first: false, second: false, third: false, fourth: false}});
        },
        removeGuest(guestID, index) {
            if (this.guestsData.length > 1) {
                const guest = this.guestsData.find(g => g.id == guestID);

                if (guest.id && !guest.deleted_at) {
                    this.$set(guest, 'deleted_at', true);
                } else if (!guest.id) {
                    this.guestsData.splice(index, 1);
                }
            }
        },
        updateGuestTravelDates() {
            if (this.bookingData.roomArrangements.length === 1) {
                const roomDates = this.bookingData.roomArrangements[0].dates;

                if (roomDates && roomDates.start && roomDates.end) {
                    this.guestsData.forEach((guest, index) => {
                        if (!guest.deleted_at) {
                            const startDate = roomDates.start instanceof Date ? roomDates.start : (typeof roomDates.start === 'string' ? this.$moment(roomDates.start).toDate() : null);
                            const endDate = roomDates.end instanceof Date ? roomDates.end : (typeof roomDates.end === 'string' ? this.$moment(roomDates.end).toDate() : null);

                            this.$set(guest, 'dates', {
                                start: startDate,
                                end: endDate
                            });
                        }
                    });

                    this.showApplyDatesMessage = true;
                    setTimeout(() => {
                        this.showApplyDatesMessage = false;
                    }, 2000);
                }
            }
        },
        addSeperateClient() {
            this.newClientErrors = {};
            this.newClientIsLoading = true;

            const allClients = [...this.clients.map(c => ({ email: c.email || c.value })), ...this.seperateClients];

            this.$http.post(`/groups/${this.group.id}/reservation/seperate-client`, {
                newClient: this.newClient,
                clients: allClients,
                booking: {
                    code: this.reservation.booking.code,
                    email: this.reservation.booking.email
                }
            })
            .then(() => {
                this.seperateClients.push({...this.newClient});
                this.newClient = {
                    firstName: null,
                    lastName: null,
                    email: null,
                    phone: null
                };
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    this.newClientErrors = error.response.data.errors;
                }
            })
            .finally(() => {
                this.newClientIsLoading = false;
            });
        },
        removeSeperateClient(index) {
            this.seperateClients.splice(index, 1);
        },
        saveReservation() {
            this.isLoading = true;
            this.reservationErrors = {};
            this.guestErrors = [];
            this.paymentErrors = {};

            const processedBookingData = cloneDeep(this.bookingData);
            const processedGuestsData = cloneDeep(this.guestsData);

            this.bookingData.roomArrangements.forEach(arrangement => {
                if (arrangement.dates instanceof Object) {
                    arrangement.dates.start = arrangement.dates.start instanceof Date ? arrangement.dates.start.toDateString() : arrangement.dates.start;
                    arrangement.dates.end = arrangement.dates.end instanceof Date ? arrangement.dates.end.toDateString() : arrangement.dates.end;
                }
            });

            this.guestsData.map(guest => {
                if (guest.dates instanceof Object) {
                    guest.dates.start = guest.dates.start instanceof Date ? guest.dates.start.toDateString() : (typeof guest.dates.start === 'string' ? guest.dates.start : null);
                    guest.dates.end = guest.dates.end instanceof Date ? guest.dates.end.toDateString() : (typeof guest.dates.end === 'string' ? guest.dates.end : null);
                }

                guest.birthDate = guest.birthDate instanceof Date ? guest.birthDate.toDateString() : (typeof guest.birthDate === 'string' ? guest.birthDate : null);

                return guest;
            });

            const hasValidSeperateClients = this.hasSeperateClients && this.seperateClients.length > 0;

            const payload = {
                booking: {
                    id: this.bookingData.id,
                    roomArrangements: this.bookingData.roomArrangements,
                    specialRequests: this.bookingData.specialRequests,
                    code: this.reservation.booking.code,
                    email: this.reservation.booking.email,
                },
                guests: this.guestsData,
                guestsRequiringInsurancePayment: this.guestsRequiringInsurancePaymentIndices,
                ignoreGuestError: this.ignoreGuestError,
                confirm:  this.nextOrSubmitLabel == 'Submit' ? true : false,
                minimumNightsExceptionAccepted: this.minimumNightsExceptionAccepted,
                hasSeperateClients: hasValidSeperateClients,
                seperateClients: hasValidSeperateClients ? this.seperateClients : [],
            };

            if (
               (( this.hasBalanceDueDatePassed && !this.hasPaymentPlan) || (this.step == 3 && this.hasGuestRequiringInsurancePayment))  && !this.group.is_fit
            ) {
                payload.payment = {
                    amount: this.paymentAmount?? 0,
                    totalRequired: this.paymentDetails?.totalRequired ?? 0,
                    changesCost: this.paymentDetails?.changesCost ?? 0,
                    insuranceCost: this.paymentDetails?.insuranceCost ?? 0,
                    extras: this.paymentDetails?.extras ?? [],
                    changeFee: this.paymentDetails?.changeFee ?? 0,
                    total: this.paymentDetails?.total ?? 0,
                    payments: this.paymentDetails?.payments ?? 0,
                    useCardOnFile: this.paymentUseCardOnFile ?? false,
                    updateCardOnFile: this.paymentUpdateCardOnFile ?? false,
                    card: this.paymentUseCardOnFile ? null : this.paymentCard,
                    address: this.paymentUseCardOnFile ? null : this.paymentAddress,
                    confirmation: this.paymentConfirmation ?? {},
                    email: this.reservation.booking.email,
                    code: this.reservation.booking.code,
                };
            }
            this.$http.post(`/groups/${this.group.id}/reservation/update`, payload)
            .then((response) => {
                    if (response.data.requiresConfirmation && response.data.messages && response.data.messages.length > 0) {
                        this.showMinimumNightsModal = true;
                        this.confirmationMessages = response.data.messages;
                    } else if (response.data.errors && response.data.errors.length > 0) {
                        this.guestErrors = response.data.errors;
                    } else if (response.data.paymentDetails) {
                        this.step = 3;
                        this.hasPaymentPlan = response.data.hasPaymentPlan;
                        this.paymentDetails = response.data.paymentDetails;
                        this.paymentCardOnFile = response.data.card;
                        this.paymentClientName = response.data.client ? response.data.client.name : '';
                        this.showSuccess = response.data.showSuccess;
                    } else {
                        this.step = 3;
                        this.hasPaymentPlan = response.data.hasPaymentPlan;
                        this.showSuccess = response.data.showSuccess;
                        this.noChangesDetected = response.data.noChangesDetected;
                        this.showFitGroupSuccessMessage = response.data.showFitGroupSuccessMessage ?? false;
                    }
                    this.showNewClientSuccessMessage = response.data.showNewClientSuccessMessage;
                }).catch(error => {
                    if (error.response && error.response.status === 422 && error.response.data.errors) {
                        this.paymentErrors = {};
                        this.reservationErrors = {};

                        Object.entries(error.response.data.errors).forEach(([key, value]) => {
                            if (
                                key === 'amount' ||
                                key === 'useCardOnFile' ||
                                key === 'updateCardOnFile' ||
                                key.startsWith('card.') ||
                                key.startsWith('address.') ||
                                key.startsWith('confirmation.') ||
                                key.startsWith('insurance.')
                            ) {
                                this.$set(this.paymentErrors, key, value);
                            } else {
                                this.$set(this.reservationErrors, key, value);
                            }
                        });
                    }
                }).finally(() => {
                    if(this.showSuccess){
                        this.confirmed = true;
                    }

                    this.bookingData = processedBookingData;
                    this.guestsData = processedGuestsData;
                    this.isLoading = false;
                });
        },
        getRoomOptions(hotelId) {
            if (!hotelId) return [];

            const hotel = this.hotels.find(h => h.value == hotelId);

            return hotel?.rooms?.map(room => ({ value: room.id, text: room.name })) || [];
        },
        getBedOptions(hotelId, roomId) {
            if (!hotelId) return [];

            if (!hotelId || !roomId) return [];

            const hotel = this.hotels.find(h => h.value == hotelId);
            const room = hotel?.rooms?.find(r => r.id == roomId);

            return room?.beds?.map(bed => ({ value: bed, text: bed })) || [];
        },
        resetModalData() {
            this.reservation = {
                booking: { email: null, code: null },
                validate: true
            };

            this.reservationErrors = {};

            this.bookingData = {
                id: null,
                roomArrangements: [{ hotel: '', room: '', bed: '', dates: { start: null, end: null }, beddingAgreement: false }],
                specialRequests: '',
            };

            this.guestsData = [{
                firstName: '',
                lastName: '',
                birthDate: null,
                dates: { start: null, end: null },
                gender: '',
                insurance: undefined,
                originalInsurance: undefined,
                transportation: false,
                transportation_type: null,
                customGroupAirport: null,
                client: null,
                deleted_at: false,
                hasGuestInsuranceDisabled: false,
                declinedInsuranceAgreements: { first: false, second: false, third: false, fourth: false}
            }];

            this.hotels = [];
            this.clients = [];
            this.customGroupAirports = [];
            this.transportationTypes = [];
            this.guestErrors = [];
            this.ignoreGuestError = false;
            this.reservationLoaded = false;
            this.lostCode = false;
            this.confirmed= true;
            this.hasPaymentPlan = false;
            this.paymentDetails = null;
            this.paymentAmount = null;
            this.paymentType = 'Payment towards balance';
            this.paymentCardOnFile = null;
            this.paymentAgreement = false;
            this.paymentConfirmation = {};
            this.paymentUseCardOnFile = true;
            this.paymentUpdateCardOnFile = false;
            this.paymentCard = {};
            this.paymentAddress = {};
            this.showSuccess = false;
            this.noChangesDetected = false;
            this.originalBookingData = null;
            this.originalGuestsData = null;
            this.hasBalanceDueDatePassed = false;
            this.showFitGroupSuccessMessage = false;
            this.guestChangesMessage = '';
            this.showMinimumNightsModal = false;
            this.minimumNightsExceptionAccepted = false;
            this.confirmationMessages = [];
            this.selectedColor = 'pink';
            this.showApplyDatesMessage = false;
            this.hasSeperateClients = false;
            this.newClient = {
                firstName: null,
                lastName: null,
                email: null,
                phone: null
            };
            this.newClientErrors = {};
            this.newClientIsLoading = false;
            this.seperateClients = [];
            this.showNewClientSuccessMessage = false;
            this.currentClientId = null;
        },
    }
}
</script>
