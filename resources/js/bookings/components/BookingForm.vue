<template>
    <div>
        <button @click="showBookingModal" :class=customCssClass>GET A QUOTE</button>

        <modal :is-active="show" @hide="close">
            <div class="form-container is-font-family-montserrat">
                <div class="form-header">
                    <div class="form-title">Get a Quote</div>
                </div>
                <div class="form-content">
                    <template v-if="step == 1">
                        <p class="heading">Accommodation Information</p>
                        <div class="field">
                            <label class="label">Do you need assistance choosing the hotel?</label>
                            <div class="control">
                                <label class="radio">
                                    <input type="radio" v-model="booking.hotelAssistance" :value="true">
                                    Yes
                                </label>
                                <label class="radio">
                                    <input type="radio" v-model="booking.hotelAssistance" :value="false">
                                    No
                                </label>
                            </div>
                            <p v-if="'hotelAssistance' in bookingErrors" class="help is-danger">{{ bookingErrors['hotelAssistance'][0] }}</p>
                        </div>
                        <div class="field" v-if="booking.hotelAssistance">
                            <label class="label">Share with us what you’re looking for out of a property, including aesthetic, vibe, activity level, amenities, size, etc.</label>
                            <div class="control">
                                <textarea v-model="booking.hotelPreferences" class="textarea" :class="{ 'is-danger': ('hotelPreferences' in bookingErrors) }"></textarea>
                            </div>
                            <p v-if="('hotelPreferences' in bookingErrors)" class="help is-danger">{{ bookingErrors['hotelPreferences'][0] }}</p>
                        </div>
                        <div class="field" v-else>
                            <label class="label">Let us know the name of the hotel</label>
                            <div class="control">
                                <input type="text" v-model="booking.hotelName" class="input" :class="{ 'is-danger': ('hotelName' in bookingErrors) }">
                            </div>
                            <p v-if="'hotelName' in bookingErrors" class="help is-danger">{{ bookingErrors['hotelName'][0] }}</p>
                        </div>
                        <div class="field">
                            <label class="label">What room category would you like your initial quote for?</label>
                            <div class="control">
                                <div>
                                    <label class="radio">
                                        <input type="radio" v-model="booking.roomCategory" :value="true">
                                        I want to specify a room category
                                    </label>
                                </div>
                                <div>
                                    <label class="radio">
                                        <input type="radio" v-model="booking.roomCategory" :value="false">
                                        I don’t know (we will quote the least expensive option to get you started)
                                    </label>
                                </div>
                            </div>
                            <p v-if="'roomCategory' in bookingErrors" class="help is-danger">{{ bookingErrors['roomCategory'][0] }}</p>
                        </div>
                        <div class="field" v-if="booking.roomCategory">
                            <label class="label">Room Category Name</label>
                            <div class="control">
                                <input type="text" v-model="booking.roomCategoryName" class="input" :class="{ 'is-danger': ('roomCategoryName' in bookingErrors) }">
                            </div>
                            <p v-if="'roomCategoryName' in bookingErrors" class="help is-danger">{{ bookingErrors['roomCategoryName'][0] }}</p>
                        </div>
                        <div class="field">
                            <div class="field-body">
                                <div class="field">
                                    <label class="label">Travel Dates</label>
                                    <div class="control">
                                        <v-date-picker
                                            v-model="travelDates"
                                            is-range
                                            :min-date="getStartDate()"
                                            :step="1"
                                            :columns="$screens({ sm: 2 }, 1)"
                                        >
                                            <template v-slot="{ inputValue, inputEvents }">
                                                <input
                                                    :value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
                                                    v-on="inputEvents.start"
                                                    :class="'input' + (('checkIn' in bookingErrors) || ('checkOut' in bookingErrors) ? ' is-danger' : '')"
                                                />
                                            </template>
                                        </v-date-picker>
                                    </div>
                                    <p v-if="('checkIn' in bookingErrors) || ('checkOut' in bookingErrors)" class="help is-danger">{{ [...(bookingErrors['checkIn'] || []), ...(bookingErrors['checkOut'] || [])][0] }}</p>
                                </div>
                                <div class="field">
                                    <label class="label">Total Guests</label>
                                    <div class="control">
                                        <input type="number" v-model="booking.totalGuests" class="input" :class="{ 'is-danger': ('totalGuests' in bookingErrors) }">
                                    </div>
                                    <p v-if="'totalGuests' in bookingErrors" class="help is-danger">{{ bookingErrors['totalGuests'][0] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label">Special Requests (Optional)</label>
                            <div class="control">
                                <textarea v-model="booking.specialRequests" class="textarea" :class="{ 'is-danger': ('specialRequests' in bookingErrors) }"></textarea>
                            </div>
                            <p v-if="('specialRequests' in bookingErrors)" class="help is-danger">{{ bookingErrors['specialRequests'][0] }}</p>
                        </div>
                        <p class="heading">Budget Information</p>
                        <div class="field">
                            <label class="label">Do you have a budget in mind for this trip? We will try to stick as close to the budget as possible, considering your wants/desires.</label>
                            <div class="control has-icons-left">
                                <input type="text" v-model="booking.budget" class="input" :class="{ 'is-danger': ('budget' in bookingErrors) }">
                                <span class="icon is-left"><i class="fas fa-dollar-sign"></i></span>
                            </div>
                            <p v-if="'budget' in bookingErrors" class="help is-danger">{{ bookingErrors['budget'][0] }}</p>
                        </div>
                        <p class="heading">Contact Information</p>
                        <div class="field">
                            <div class="field-body">
                                <div class="field">
                                    <label class="label">First Name</label>
                                    <div class="control">
                                        <input type="text" v-model="booking.clients[0]['firstName']" class="input is-capitalized" :class="{ 'is-danger': ('clients.0.firstName' in bookingErrors) }">
                                    </div>
                                    <p v-if="'clients.0.firstName' in bookingErrors" class="help is-danger">{{ bookingErrors['clients.0.firstName'][0] }}</p>
                                </div>
                                <div class="field">
                                    <label class="label">Last Name</label>
                                    <div class="control">
                                        <input type="text" v-model="booking.clients[0]['lastName']" class="input is-capitalized" :class="{ 'is-danger': ('clients.0.lastName' in bookingErrors) }">
                                    </div>
                                    <p v-if="'clients.0.lastName' in bookingErrors" class="help is-danger">{{ bookingErrors['clients.0.lastName'][0] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label">Email</label>
                            <div class="control">
                                <input type="text" v-model="booking.clients[0]['email']" class="input" :class="{ 'is-danger': ('clients.0.email' in bookingErrors) }">
                            </div>
                            <p v-if="'clients.0.email' in bookingErrors" class="help is-danger">{{ bookingErrors['clients.0.email'][0] }}</p>
                        </div>
                        <div class="field">
                            <label class="label">Phone Number</label>
                            <div class="control">
                                <input type="number" v-model="booking.clients[0]['phone']" class="input" :class="{ 'is-danger': ('clients.0.phone' in bookingErrors) }">
                            </div>
                            <p v-if="('clients.0.phone' in bookingErrors)" class="help is-danger">{{ bookingErrors['clients.0.phone'][0] }}</p>
                        </div>
                        <div class="field">
                            <label class="label">If sharing room would you like to be invoiced seperately?</label>
                            <div class="control">
                                <label class="radio">
                                    <input type="radio" v-model="booking.hasSeperateClients" :value="true">
                                    Yes
                                </label>
                                <label class="radio">
                                    <input type="radio" v-model="booking.hasSeperateClients" :value="false">
                                    No
                                </label>
                            </div>
                            <p v-if="'hasSeperateClients' in bookingErrors" class="help is-danger">{{ bookingErrors['hasSeperateclients'] }}</p>
                        </div>
                        <template v-if="booking.hasSeperateClients">
                            <div class="field">
                                <label class="label">Who else should be invoiced?</label>
                                <div class="field-body">
                                    <div class="field">
                                        <div class="control">
                                            <input type="text" v-model="newClient['firstName']" class="input is-capitalized" :class="{ 'is-danger': ('newClient.firstName' in newClientErrors) }" placeholder="First Name">
                                        </div>
                                        <p v-if="'newClient.firstName' in newClientErrors" class="help is-danger">{{ newClientErrors['newClient.firstName'][0] }}</p>
                                    </div>
                                    <div class="field">
                                        <div class="control">
                                            <input type="text" v-model="newClient['lastName']" class="input is-capitalized" :class="{ 'is-danger': ('newClient.lastName' in newClientErrors) }" placeholder="Last Name">
                                        </div>
                                        <p v-if="'newClient.lastName' in newClientErrors" class="help is-danger">{{ newClientErrors['newClient.lastName'][0] }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="field">
                                <div class="control">
                                    <input type="number" v-model="newClient['phone']" class="input" :class="{ 'is-danger': ('newClient.phone' in newClientErrors) }" placeholder="Phone Number">
                                </div>
                                <p v-if="'newClient.phone' in newClientErrors" class="help is-danger">{{ newClientErrors['newClient.phone'][0] }}</p>
                            </div>
                            <div class="field">
                                <div class="control">
                                    <div class="field has-addons">
                                        <div class="control is-expanded">
                                            <input type="text" v-model="newClient['email']" @keyup.enter="addSeperateClient" class="input" :class="{ 'is-danger': ('newClient.email' in newClientErrors) || ('seperateClients' in bookingErrors) }" placeholder="Email">
                                        </div>
                                        <div class="control">
                                            <button @click="addClient" class="button is-outlined is-dark" style="padding: 6px;" :class="{ 'is-loading': newClientIsLoading }">Add</button>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="'newClient.email' in newClientErrors" class="help is-danger">{{ newClientErrors['newClient.email'][0] }}</p>
                            </div>
                            <div v-if="booking.clients.length > 1" class="table-container">
                                <table class="table is-fullwidth">
                                <tbody>
                                    <tr v-for="(client, index) in booking.clients.slice(1)" :key="client.email">
                                        <td class="is-capitalized">{{ client.firstName }} {{ client.lastName }}</td>
                                        <td>{{ client.email }}</td>
                                        <td class="has-text-right">
                                            <a @click="booking.clients.splice(index + 1, 1)">
                                                <span class="icon has-text-link" style="color: black !important">
                                                    <i class="fas fa-times"></i>
                                                </span>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p><strong>Please note:</strong> The full deposit must be received prior to confirming the room.</p>
                            </div>
                        </template>
                    </template>

                    <template v-if="step == 2">
                        <div v-for="(guest, index) in booking.guests" :key="index">
                            <div>
                                <p class="heading">Guest {{ index + 1 }}</p>
                            </div>
                            <div class="field">
                                <div class="field-body">
                                    <div class="field">
                                        <label class="label">First Name</label>
                                        <div class="control">
                                            <input type="text" v-model="guest['firstName']" class="input is-capitalized" :class="{ 'is-danger': (`guests.${index}.firstName` in bookingErrors) }">
                                        </div>
                                        <p v-if="`guests.${index}.firstName` in bookingErrors" class="help is-danger">{{ bookingErrors[`guests.${index}.firstName`][0] }}</p>
                                        <p v-if="bookingErrors.duplicate_guests_in_request && bookingErrors.duplicate_guests_in_request.includes(index)" class="help is-danger">This guest is being duplicated.</p>
                                    </div>
                                    <div class="field">
                                        <label class="label">Last Name</label>
                                        <div class="control">
                                            <input type="text" v-model="guest['lastName']" class="input is-capitalized" :class="{ 'is-danger': (`guests.${index}.lastName` in bookingErrors) }">
                                        </div>
                                        <p v-if="`guests.${index}.lastName` in bookingErrors" class="help is-danger">{{ bookingErrors[`guests.${index}.lastName`][0] }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">Date of Birth</label>
                                <div class="control">
                                    <v-date-picker
                                        :value="guest['birthDate']"
                                        @input="setBirthDate(guest, $event)"
                                        :popover="{ visibility: 'focus' }"
                                    >
                                        <template v-slot="{ inputValue, inputEvents }">
                                            <input
                                                placeholder="MM/DD/YYYY"
                                                :class="'input' + ((`guests.${index}.birthDate` in bookingErrors) ? ' is-danger' : '')"
                                                :value="inputValue"
                                                v-on="inputEvents"
                                            />
                                        </template>
                                    </v-date-picker>
                                </div>
                                <p v-if="`guests.${index}.birthDate` in bookingErrors" class="help is-danger">{{ bookingErrors[`guests.${index}.birthDate`][0] }}</p>
                            </div>
                            <div class="field">
                                <label class="label">Gender</label>
                                <div class="control">
                                    <label class="radio">
                                        <input type="radio" value="M" v-model="guest['gender']">
                                        Male
                                    </label>
                                    <label class="radio">
                                        <input type="radio" value="F" v-model="guest['gender']">
                                        Female
                                    </label>
                                </div>
                                <p v-if="`guests.${index}.gender` in bookingErrors" class="help is-danger">{{ bookingErrors[`guests.${index}.gender`][0] }}</p>
                            </div>
                            <hr v-if="!booking.hasSeperateClients && index != booking.guests.length - 1" />
                            <div v-if="booking.hasSeperateClients" class="field">
                                <label class="label">Invoiced To</label>
                                <div class="select is-fullwidth">
                                    <select v-model="guest['client']" class="is-capitalized">
                                        <option :value="null" disabled></option>
                                        <option v-for="client in booking.clients" :key="client['email']" :value="client['email']">{{ client['firstName'] }} {{ client['lastName'] }}</option>
                                    </select>
                                </div>
                                <p v-if="`guests.${index}.client` in bookingErrors" class="help is-danger">{{ bookingErrors[`guests.${index}.client`][0] }}</p>
                            </div>
                            <hr v-if="booking.hasSeperateClients && index != booking.guests.length - 1" />
                        </div>
                    </template>

                    <template v-if="step == 3">
                        <div class=" is-first">
                            <p class="heading">Flights Information</p>
                        </div>
                        <div class="field">
                            <label class="label">Would you like us to quote flights for you?</label>
                            <div class="control">
                                <label class="radio">
                                    <input type="radio" v-model="booking.transportation" :value="true">
                                    Yes
                                </label>
                                <label class="radio">
                                    <input type="radio" v-model="booking.transportation" :value="false">
                                    No
                                </label>
                            </div>
                            <p v-if="'transportation' in bookingErrors" class="help is-danger">{{ bookingErrors['transportation'][0] }}</p>
                        </div>
                        <div v-if="booking.transportation">
                            <div class="field">
                                <label class="label">Departure Gateway</label>
                                <div class="control">
                                    <input type="text" v-model="booking.departureGateway" class="input" :class="{ 'is-danger': ('departureGateway' in bookingErrors) }">
                                </div>
                                <p v-if="'departureGateway' in bookingErrors" class="help is-danger">{{ bookingErrors['departureGateway'][0] }}</p>
                            </div>
                            <div class="field">
                                <label class="label">Flight Preferences</label>
                                <div class="control">
                                    <textarea v-model="booking.flightPreferences" class="textarea" :class="{ 'is-danger': ('flightPreferences' in bookingErrors) }"></textarea>
                                </div>
                                <p v-if="'flightPreferences' in bookingErrors" class="help is-danger">{{ bookingErrors['flightPreferences'][0] }}</p>
                            </div>
                            <div class="field">
                                <label class="label">Airline Membership Number</label>
                                <div class="control">
                                    <input type="text" v-model="booking.airlineMembershipNumber" class="input" :class="{ 'is-danger': ('airlineMembershipNumber' in bookingErrors) }">
                                </div>
                                <p v-if="'airlineMembershipNumber' in bookingErrors" class="help is-danger">{{ bookingErrors['airlineMembershipNumber'][0] }}</p>
                            </div>
                            <div class="field">
                                <label class="label">Known Traveler Number (KTN)</label>
                                <div class="control">
                                    <input type="text" v-model="booking.knownTravelerNumber" class="input" :class="{ 'is-danger': ('knownTravelerNumber' in bookingErrors) }">
                                </div>
                                <p v-if="'knownTravelerNumber' in bookingErrors" class="help is-danger">{{ bookingErrors['knownTravelerNumber'][0] }}</p>
                            </div>
                            <div class="field">
                                <label class="label">Any message for us?</label>
                                <div class="control">
                                    <textarea v-model="booking.flightMessage" class="textarea" :class="{ 'is-danger': ('flightMessage' in bookingErrors) }"></textarea>
                                </div>
                                <p v-if="'flightMessage' in bookingErrors" class="help is-danger">{{ bookingErrors['flightMessage'][0] }}</p>
                            </div>
                        </div>
                        <hr>
                        <div>
                            <p class="heading">Travel Insurance</p>
                        </div>
                        <p class="is-size-7 has-text-weight-normal">
                            Travel Insurance is available for purchase and it is highly recommended that you add travel insurance to your package.
                        </p>
                        <p class="is-size-7 has-text-justified">
                            The Travel Insurance that is available may cover you up to the total trip cost for trip cancellation or interruption for a covered reason and up to 75% for any reason
                            (this Cancel For Any Reason Benefit only applies if you add the travel insurance to your package within 14 days of booking).
                            Emergency Evacuation/Repatriation, Accidental Medical Expenses and Sickness Medical Expenses may be covered up to $25,000.
                            Also included is baggage protection and worldwide emergency assistance. It is a cancellation/interruption insurance with medical benefits.
                            Your health insurance may not cover you outside of the US. Accidents can happen and travel insurance can help protect you in the event you need medical attention.
                            For a brochure detailing what is included, please email us at groups@barefootbridal.com and I will send it to you via email.
                            The cost for travel insurance is dependant on the cost of your trip, the number of nights, and whether or not a flight is included.
                            To get an exact price of travel insurance, please email us for a quote.
                            The cost of travel insurance for each guest is due at time of booking. Please note that once purchased, travel insurance is nonrefundable.
                        </p>
                        <div class="field">
                            <div class="label">Do you wish to purchase travel insurance?</div>
                            <div class="control">
                                <div>
                                    <label class="radio">
                                        <input type="radio" :value="true" v-model="booking.insurance">
                                        Yes, I would like to purchase travel insurance and understand that once purchased <b>the cost of travel insurance is non-refundable</b>.
                                    </label>
                                </div>
                                <div>
                                    <label class="radio">
                                        <input type="radio" :value="false" v-model="booking.insurance">
                                        No, I am <b>not interested in purchasing travel insurance</b> and acknowledge that I have been offered but choose to decline this coverage. I understand the risks in not purchasing travel protection.
                                    </label>
                                </div>
                                <div v-if="false === booking.insurance">
                                    <div class="control mt-10">
                                        <label class="checkbox">
                                            <input type="checkbox" v-model="booking.declinedInsuranceAgreements.first" :class="{ 'is-danger': ('declinedInsuranceAgreements.first' in bookingErrors) }">
                                            I understand that by declining travel insurance, that I will not be reimbursed for cancelling my reservation after the cancellation date. 
                                        </label>
                                    </div>
                                    <p v-if="('declinedInsuranceAgreements.first' in bookingErrors)" class="help is-danger">You must agree with the conditions.</p>
                                    <div class="control mt-10">
                                        <label class="checkbox">
                                            <input type="checkbox" v-model="booking.declinedInsuranceAgreements.second" :class="{ 'is-danger': ('declinedInsuranceAgreements.second' in bookingErrors) }">
                                            I understand that after the cancellation date, I will not be able to cancel for a refund even if one or if one or more of the guests on this reservation is/are unable to attend for any reason, including but not limited to:
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
                                    <p v-if="('declinedInsuranceAgreements.second' in bookingErrors)" class="help is-danger">You must agree with the conditions.</p>
                                    <div class="control mt-10">
                                        <label class="checkbox">
                                            <input type="checkbox" v-model="booking.declinedInsuranceAgreements.third" :class="{ 'is-danger': ('declinedInsuranceAgreements.third' in bookingErrors) }">
                                            I understand that the only way to protect my reservation is by purchasing travel insurance.
                                        </label>
                                    </div>
                                    <p v-if="('declinedInsuranceAgreements.third' in bookingErrors)" class="help is-danger">You must agree with the conditions.</p>
                                    <div class="control mt-10">
                                        <label class="checkbox">
                                            <input type="checkbox" v-model="booking.declinedInsuranceAgreements.fourth" :class="{ 'is-danger': ('declinedInsuranceAgreements.fourth' in bookingErrors) }">
                                            I understand that if I did not purchase travel insurance and wish to cancel, reduce the guest count, downgrade my room category, or make any other changes to the reservation that would have resulted in a refund prior to the cancellation date, that I will not receive a refund and I agree not to dispute my charges in this event.
                                        </label>
                                    </div>
                                    <p v-if="('declinedInsuranceAgreements.fourth' in bookingErrors)" class="help is-danger">You must agree with the conditions.</p>
                                </div>
                            </div>
                            <p v-if="('insurance' in bookingErrors)" class="help is-danger">{{ bookingErrors['insurance'][0] }}</p>
                        </div>
                        <div class="field">
                            <label class="label">Travel Insurance Signature</label>
                            <p class="help for-label">
                                Type your name here to confirm your decision above. You must sign this, whether you are purchasing or declining travel insurance!
                            </p>
                            <div class="control">
                                <input type="text" v-model="booking.insuranceSignature" :placeholder="`${booking.clients[0]['firstName']} ${booking.clients[0]['lastName']}`" class="input is-capitalized" :class="{ 'is-danger': ('insuranceSignature' in bookingErrors) }">
                            </div>
                            <p v-if="('insuranceSignature' in bookingErrors)" class="help is-danger">{{ bookingErrors['insuranceSignature'][0] }}</p>
                        </div>
                    </template>
                    <template v-if="step > 3">
                        <p class="is-size-5 has-text-weight-normal">Thank you for your quote request!</p>
                        <br>
                        <p>Further communication will be via e-mail. Please check your spam folder and if you did not receive an e-mail confirmation from us, reach out to <a href="mailto:groups@barefootbridal.com" target="_blank" class="has-text-black"><b>groups@barefootbridal.com</b></a>.</p>
                    </template>
                </div>
                <div v-if="step <= 3" class="form-footer">
                    <div class="columns is-mobile">
                        <div class="column">
                            <button v-if="step > 1 && step <= 3" @click="back" class="button is-outlined is-dark" :disabled="isLoading">Back</button>
                        </div>
                        <div class="column is-narrow">
                            <button @click="next" class="button is-outlined is-dark" :class="{ 'is-loading': isLoading }">{{ (step < 3) ? 'Next' : 'Get a Quote' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </modal>
    </div>
</template>

<script>
export default {
    props: {
        customCssClass: {
            type: String,
            required: false,
            default: 'button is-medium is-rounded is-outlined is-black hidden'
        },
    },
    data() {
        return {
            show: false,
            step: 1,
            isLoading: false,
            booking: {
                hotelAssistance: false,
                hotelPreferences: null,
                hotelName: null,
                roomCategory: true,
                roomCategoryName: null,
                checkIn: null,
                checkOut: null,
                totalGuests: null,
                specialRequests: null,
                budget: null,
                clients: [
                    {
                        firstName: '',
                        lastName: '',
                        email: '',
                        phone: '',
                    }
                ],
                hasSeperateClients: false,
                guests: [],
                transportation: true,
                departureGateway: null,
                flightPreferences: null,
                airlineMembershipNumber: null,
                knownTravelerNumber: null,
                flightMessage: null,
                insurance: null,
                declinedInsuranceAgreements: {
                    first: false,
                    second: false,
                    third: false,
                    fourth: false,
                },
                insuranceSignature: '',
            },
            bookingErrors: {},
            newClient: {
                firstName: null,
                lastName: null,
                phone: null,
                email: null
            },
            newClientErrors: {},
            newClientIsLoading: false,
        }
    },
    computed: {
        travelDates: {
            get() {
                return {
                    start: this.booking.checkIn,
                    end: this.booking.checkOut
                }
            },
            set(dates) {
                if (! dates) {
                    this.booking.checkIn = null;
                    this.booking.checkOut = null;

                    return;
                }

                this.booking.checkIn = dates.start instanceof Date ? dates.start.toDateString() : null;
                this.booking.checkOut = dates.end instanceof Date ? dates.end.toDateString() : null;
            }
        },
    },
    methods: {
        close() {
            if (this.step > 3) {
                Object.assign(this.$data, this.$options.data.apply(this));
            }

            this.show = false;
        },
        getStartDate() {
            return new Date();
        },
        setBirthDate(guest, date) {
            guest.birthDate = date instanceof Date ? date.toDateString() : null;
        },
        back() {
            this.step--;
        },
        next() {
            this.bookingErrors = {};
            this.isLoading = true;

            let request = this.$http.post(`/individual-bookings/new-booking/${this.step}`, this.booking)
                .then(response => {
                    if (typeof this[`step${this.step}`] === 'function') {
                        this[`step${this.step}`](response);
                    }

                    this.step++;
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
        step1() {
            this.booking.clients.forEach(client => client.email = client.email.trim().toLowerCase());

            while (this.booking.guests.length < (this.booking.totalGuests)) {
                this.booking.guests.push({
                    firstName: null,
                    lastName: null,
                    birthDate: null,
                    gender: null,
                    client: null
                });
            }

            if (this.booking.guests.length > (this.booking.totalGuests)) {
                this.booking.guests.splice(this.booking.totalGuests);
            }

            this.booking.guests = this.booking.guests.reverse();
        },
        addClient() {
            this.newClientErrors = {};
            this.newClientIsLoading = true;

            let request = this.$http.post(`/individual-bookings/seperate-client`, {newClient: this.newClient, clients: this.booking.clients})
                .then(() => {
                    this.booking.clients.push(this.newClient);
                    this.newClient = this.$options.data.call(this).newClient;
                    delete this.bookingErrors['seperateClients'];
                }).catch(error => {
                    if (error.response.status == 422) {
                        this.newClientErrors = error.response.data.errors;
                    }
                });

            request.then(() => {
                this.newClientIsLoading = false;
            });
        },
        showBookingModal() {
            this.show = true;
        }
    }
}
</script>
