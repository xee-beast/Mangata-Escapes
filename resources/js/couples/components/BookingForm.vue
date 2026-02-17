<template>
<div>
    <button @click="showBookingModal" :class=customCssClass>
        <span v-if="group.is_fit">GET A QUOTE</span>
        <span v-else>BOOK NOW</span>
    </button>
    <modal :is-active="show" @hide="close">
        <div class="form-container is-font-family-montserrat">
            <div class="form-header">
                <div class="form-title">{{ group.name }} - {{ group.is_fit ? 'Get a Quote' : 'Booking Form' }}</div>
                <button type="button" class="modal-close-get-quote" aria-label="close" @click="close">
                    <span class="icon"><i class="fas fa-times"></i></span>
                </button>
            </div>

            <div class="form-content">
                <template v-if="step == 1">
                <div>
                    <div class="hide">{{ group.cancellationDate }}</div>
                    <p class="heading">Travel & Accommodation Information</p>
                </div>
                <div class="field">
                    <label class="label">Hotel</label>
                    <div class="control">
                        <div class="select is-fullwidth" :class="{ 'is-danger': ('hotel' in bookingErrors) }">
                            <select v-model="booking.hotel">
                                <option value="" disabled selected></option>
                                <option v-for="hotel in hotels" :key="hotel.id" :value="hotel.id">{{ hotel.name }}</option>
                            </select>
                        </div>
                    </div>
                    <p v-if="'hotel' in bookingErrors" class="help is-danger">{{ bookingErrors['hotel'][0] }}</p>
                </div>
                <div class="field">
                    <label class="label">Room</label>
                    <div class="control">
                        <div class="select is-fullwidth" :class="{ 'is-danger': ('room' in bookingErrors) }">
                            <select v-model="booking.room">
                                <option value="" disabled selected></option>
                                <template v-if="'rooms' in selectedHotel">
                                <option v-for="room in selectedHotel.rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                                </template>
                            </select>
                        </div>
                        <p v-if="'room' in bookingErrors" class="help is-danger">{{ bookingErrors['room'][0] }}</p>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Bedding Request</label>
                    <div class="control">
                        <div class="select is-fullwidth" :class="{ 'is-danger': ('bed' in bookingErrors) }">
                            <select v-model="booking.bed">
                                <option value="" disabled selected></option>
                                <template v-if="'beds' in selectedRoom">
                                <option v-for="(bed, index) in selectedRoom.beds" :key="index" :value="bed">{{ bed }}</option>
                                </template>
                            </select>
                        </div>
                        <p v-if="'bed' in bookingErrors" class="help is-danger">{{ bookingErrors['bed'][0] }}</p>
                    </div>
                    <div class="control mt-10">
                        <label class="checkbox is-dusty-rose">
                            <input type="checkbox" v-model="booking.beddingAgreement" :class="{ 'is-danger': ('beddingAgreement' in bookingErrors) }">
                            I understand that bedding is a special request and is subject to availability.
                        </label>
                    </div>
                    <p v-if="('beddingAgreement' in bookingErrors)" class="help is-danger">You must agree with the conditions.</p>
                </div>
                <div class="field">
                    <div class="field-body">
                        <div class="field">
                            <label class="label">Travel Dates</label>
                            <div class="control">
                                <v-date-picker v-model="travelDates" is-range
                                    :min-date="getStartDate()" :max-date="getEndDate()"
                                    :step="1"
                                    :color="selectedColor"
                                    :columns="$screens({ sm: 2 }, 1)">
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
                            <div class="select is-fullwidth" :class="{ 'is-danger': ('totalGuests' in bookingErrors) }">
                                <select v-model="booking.totalGuests">
                                    <option :value="0" disabled></option>
                                    <option v-for="index in maxGuests" :key="index" :value="index">{{ index }}</option>
                                </select>
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
                <hr/>
                <div>
                    <p class="heading">Contact Information</p>
                </div>
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
                <div class="field" v-if="!group.disableInvoiceSplitting">
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
                                <button @click="addClient" class="button is-outlined is-dark" style="padding: 6px;"  :class="{ 'is-loading': newClientIsLoading }">Add</button>
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
                                        <span class="icon has-text-link" style="color: #C7979C !important;">
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
                <div v-for="(guest, index) in booking.guests" :key="index" class="is-font-family-montserrat">
                    <p class="heading">Guest {{ index + 1 }}</p>
                    <div class="field">
                        <div class="field-body">
                            <div class="field">
                                <label class="label">First Name</label>
                                <div class="control">
                                    <input type="text" v-model="guest['firstName']" class="input is-capitalized" :class="{ 'is-danger': (`guests.${index}.firstName` in bookingErrors) }">
                                </div>
                                <p v-if="`guests.${index}.firstName` in bookingErrors" class="help is-danger">{{ bookingErrors[`guests.${index}.firstName`][0] }}</p>
                                <p v-if="'duplicate_guests_in_request' in bookingErrors && bookingErrors.duplicate_guests_in_request.includes(index)" class="help is-danger">This guest is being duplicated.</p>
                                <p v-if="'duplicate_guests' in bookingErrors && bookingErrors.duplicate_guests.includes(index) && !('duplicate_guests_in_request' in bookingErrors && bookingErrors.duplicate_guests_in_request.includes(index))" class="help is-danger">A guest with this name and date of birth is already booked. Please reach out to <a target="_blank" style="color: black; font-weight: bold;" :href="`mailto:${group.groupsEmail}`"><u>{{ group.groupsEmail }}</u></a> for further assistance.</p>
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
                            <v-date-picker :value="guest['birthDate']" @input="setBirthDate(guest, $event)"
                                :popover="{ visibility: 'focus' }" :color="selectedColor">
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
                    <hr v-if="!booking.hasSeperateClients && index != booking.guests.length - 1"/>
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
                    <hr v-if="booking.hasSeperateClients && index != booking.guests.length - 1"/>
                </div>
                </template>

                <template v-if="step == 3" class="is-font-family-montserrat">
                <div>
                    <p class="heading">Payment Information</p>
                </div>
                <div class="field">
                    <label class="label">Cardholder Name</label>
                    <div class="control">
                        <input type="text" v-model="booking.card['name']" class="input is-capitalized" :class="{ 'is-danger': ('card.name' in bookingErrors) }">
                    </div>
                    <p v-if="'card.name' in bookingErrors" class="help is-danger">{{ bookingErrors['card.name'][0] }}</p>
                </div>
                <div class="field">
                    <label class="label">Card Number</label>
                    <div class="field-body">
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input type="text" v-model="cardNumber" class="input" :class="{ 'is-danger': ('card.number' in bookingErrors) || ('card.type' in bookingErrors) || invalidCard }">
                            </div>
                            <div class="control">
                                <button class="button is-static is-height-80">
                                    <span class="icon">
                                        <i class="fa-lg" :class="cardTypeClass"></i>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-if="('card.number' in bookingErrors) || ('card.type' in bookingErrors) || invalidCard" class="help is-danger">{{ [...(bookingErrors['card.number'] || []), ...(bookingErrors['card.type'] || [])][0] || 'The card number is not valid.' }}</p>
                </div>
                <div class="field">
                    <div class="field-body">
                        <div class="field">
                            <label class="label">Expiration</label>
                            <div class="field-body">
                                <div class="field">
                                    <div class="select is-fullwidth" :class="{ 'is-danger': ('card.expMonth' in bookingErrors)}">
                                        <select v-model="booking.card['expMonth']">
                                            <option :value="null" disabled>Month</option>
                                            <option v-for="month in 12" :key="month" :value="month.toString().padStart(2, 0)">{{ month.toString().padStart(2, 0) }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="select is-fullwidth" :class="{ 'is-danger': ('card.expYear' in bookingErrors)}">
                                        <select v-model="booking.card['expYear']">
                                            <option :value="null" disabled>Year</option>
                                            <option :value="new Date().getFullYear()">{{ new Date().getFullYear() }}</option>
                                            <option v-for="index in 19" :key="index" :value="new Date().getFullYear() + index">{{ new Date().getFullYear() + index }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <p v-if="('card.expMonth' in bookingErrors) || ('card.expYear' in bookingErrors)" class="help is-danger">{{ [...(bookingErrors['card.expMonth'] || []), ...(bookingErrors['card.expYear'] || [])][0] }}</p>
                        </div>
                        <div class="field">
                            <label class="label">{{ cardCode.name }}</label>
                            <div class="control">
                                <input type="text" v-model="booking.card['code']" class="input" :class="{ 'is-danger': ('card.code' in bookingErrors) }">
                            </div>
                            <p v-if="('card.code' in bookingErrors)" class="help is-danger">{{ bookingErrors['card.code'][0] }}</p>
                        </div>
                    </div>
                </div>
                <hr/>
                <div>
                    <p class="heading">Billing Address</p>
                </div>
                <div class="field">
                    <label class="label">Country</label>
                    <div class="select is-fullwidth" :class="{ 'is-danger': ('address.country' in bookingErrors) }">
                        <select v-model="booking.address['country']">
                            <option :value="null" disabled></option>
                            <option v-for="country in countries" :key="country.id" :value="country.id">{{ country.name }}</option>
                            <option value="0">Other...</option>
                        </select>
                    </div>
                    <div v-if="booking.address['country'] == 0" class="control">
                        <input type="text" v-model="booking.address['otherCountry']"  placeholder="Please specify" class="input" :class="{ 'is-danger': ('address.otherCountry' in bookingErrors) }">
                    </div>
                    <p v-if="('address.country' in bookingErrors) || ('address.otherCountry' in bookingErrors)" class="help is-danger">{{ [...(bookingErrors['address.country'] || []), ...(bookingErrors['address.otherCountry'] || [])][0] }}</p>
                </div>
                <div class="field">
                    <div class="field-body">
                        <div class="field">
                            <label class="label">{{ selectedCountry['division'] ? selectedCountry['division'] : 'State/Province' }}</label>
                            <div v-if="selectedCountry['states']" class="select is-fullwidth" :class="{ 'is-danger': ('address.state' in bookingErrors) }">
                                <select v-model="booking.address['state']">
                                    <option :value="null" disabled></option>
                                    <option v-for="state in selectedCountry['states']" :key="state.id" :value="state.id">{{ state.name }} ({{ state.abbreviation }})</option>
                                </select>
                            </div>
                            <div v-else class="control">
                                <input type="text" v-model="booking.address['otherState']" class="input" :class="{ 'is-danger': ('address.otherState' in bookingErrors) }">
                            </div>
                            <p v-if="('address.state' in bookingErrors) || ('address.otherState' in bookingErrors)" class="help is-danger">{{ [...(bookingErrors['address.state'] || []), ...(bookingErrors['address.otherState'] || [])][0] }}</p>
                        </div>
                        <div class="field">
                            <label class="label">City</label>
                            <div class="control">
                                <input type="text" v-model="booking.address['city']" class="input" :class="{ 'is-danger': ('address.city' in bookingErrors) }">
                            </div>
                            <p v-if="('address.city' in bookingErrors)" class="help is-danger">{{ bookingErrors['address.city'][0] }}</p>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Address Line 1</label>
                    <div class="control">
                        <input type="text" v-model="booking.address['line1']" class="input" :class="{ 'is-danger': ('address.line1' in bookingErrors) }">
                    </div>
                    <p v-if="('address.line1' in bookingErrors)" class="help is-danger">{{ bookingErrors['address.line1'][0] }}</p>
                </div>
                <div class="field">
                    <div class="field-body">
                        <div class="field">
                            <label class="label">Address Line 2 (Optional)</label>
                            <div class="control">
                                <input type="text" v-model="booking.address['line2']" class="input" :class="{ 'is-danger': ('address.line2' in bookingErrors) }">
                            </div>
                            <p v-if="('address.line2' in bookingErrors)" class="help is-danger">{{ bookingErrors['address.line2'][0] }}</p>
                        </div>
                        <div class="field is-narrow">
                            <label class="label">Zip/Postal Code</label>
                            <div class="control">
                                <input type="text" v-model="booking.address['zipCode']" class="input" :class="{ 'is-danger': ('address.zipCode' in bookingErrors) }">
                            </div>
                            <p v-if="('address.zipCode' in bookingErrors)" class="help is-danger">{{ bookingErrors['address.zipCode'][0] }}</p>
                        </div>
                    </div>
                </div>
                </template>

                <template v-if="step == 4" class="is-font-family-montserrat">
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
                    For a brochure detailing what is included, please email us at {{ group.groupsEmail }} and I will send it to you via email.
                    The cost for travel insurance is dependant on the cost of your trip, the number of nights, and whether or not a flight is included.
                    To get an exact price of travel insurance, please email us for a quote.
                    The cost of travel insurance for each guest is due at time of booking. Please note that once purchased, travel insurance is nonrefundable.
                </p>
                <br>
                <div v-if="group.hasTransportation">
                    <label class="label font-weight-600">Do you wish to include airport transfers?</label>
                    <p class="help for-label">
                        Check here if you want us to mange your transportation from airport to hotel and back to the airport.
                    </p>
                    <div class="control">
                        <label class="checkbox">
                            <input type="checkbox" v-model="booking.transportation">
                            Yes, I want airport transfers.
                        </label>
                    </div>
                    <p v-if="('transportation' in bookingErrors)" class="help is-danger">{{ bookingErrors['transportation'][0] }}</p>
                    <div v-if="false === booking.transportation">
                        <div class="control mt-10">
                            <label class="checkbox">
                                <input type="checkbox" v-model="booking.declinedTransportation" :class="{ 'is-danger': ('declinedTransportation' in bookingErrors) }">
                                I understand that by declining airport transfers, Barefoot Bridal will not coordinate our pick up from the airport upon our arrival and transfer to the hotel or back to the airport for our flight home. I understand that I will need to coordinate the airport transfers on my own.
                            </label>
                        </div>
                        <p v-if="('declinedTransportation' in bookingErrors)" class="help is-danger">You must agree with this statement.</p>
                    </div>
                    <br>
                </div>
                <div class="field">
                    <div class="label font-weight-600">Do you wish to purchase travel insurance?</div>
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
                                    I understand that by declining travel insurance, that I will not be reimbursed for cancelling my reservation after {{ group.cancellationDate }}.
                                </label>
                            </div>
                            <p v-if="('declinedInsuranceAgreements.first' in bookingErrors)" class="help is-danger">You must agree with the conditions.</p>

                            <div class="control mt-10">
                                <label class="checkbox">
                                    <input type="checkbox" v-model="booking.declinedInsuranceAgreements.second" :class="{ 'is-danger': ('declinedInsuranceAgreements.second' in bookingErrors) }">
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
                            <p v-if="('declinedInsuranceAgreements.second' in bookingErrors)" class="help is-danger">You must agree with the conditions.</p>

                            <div class="control mt-10">
                                <label>
                                  <p class="checkbox">
                                    <input type="checkbox" v-model="booking.declinedInsuranceAgreements.third" :class="{ 'is-danger': ('declinedInsuranceAgreements.third' in bookingErrors) }">
                                    I understand that the only way to protect my reservation is by purchasing travel insurance.
                                  </p>
                              </label>
                            </div>
                            <p v-if="('declinedInsuranceAgreements.third' in bookingErrors)" class="help is-danger">You must agree with the conditions.</p>

                            <div class="control mt-10">
                                <label class="checkbox">
                                    <input type="checkbox" v-model="booking.declinedInsuranceAgreements.fourth" :class="{ 'is-danger': ('declinedInsuranceAgreements.fourth' in bookingErrors) }">
                                    I understand that if I did not purchase travel insurance and wish to cancel, reduce the guest count, downgrade my room category, or make any other changes to the reservation that would have resulted in a refund prior to {{ group.cancellationDate }}, that I will not receive a refund and I agree not to dispute my charges in this event.
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
                <div v-if="!group.is_fit">
                    <hr/>
                    <div>
                        <p class="heading">Checkout</p>
                    </div>
                    <div class="columns is-vcentered">
                        <div class="column is-narrow">
                            <div class="columns is-mobile" style="margin-left: 0;">
                                <div class="column is-half-mobile has-background-grey-lighter has-text-right">
                                    <div>Subtotal</div>
                                    <div v-if="group.hasTransportation">{{ group.transportationType }} Transfers</div>
                                    <div>Travel Insurance</div>
                                    <div class="has-text-weight-normal">Total</div>
                                </div>
                                <div class="column is-narrow has-background-primary">
                                    <div>
                                        <div>${{ subTotal.toFixed(2) }}</div>
                                        <div v-if="group.hasTransportation">${{ transportationRate.toFixed(2) }}</div>
                                        <div>${{ (booking.insurance ? insuranceRate : 0).toFixed(2) }}</div>
                                        <div class="has-text-weight-normal">${{ (subTotal + transportationRate + (booking.insurance ? insuranceRate : 0)).toFixed(2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="column">
                            <div class="field">
                                <label class="label">Payment Amount</label>
                                <p class="help for-label">Minimum Deposit: ${{ minimumDeposit.toFixed(2) }}</p>
                                <div class="control has-icons-left">
                                    <input type="text" v-model="booking.deposit" class="input" :class="{ 'is-danger': ('deposit' in bookingErrors) }">
                                    <span class="icon is-left">
                                        <i class="fas fa-dollar-sign"></i>
                                    </span>
                                </div>
                                <p v-if="('deposit' in bookingErrors)" class="help is-danger">{{ bookingErrors['deposit'][0] }}</p>
                                <p v-if="group.isNonRefundable" class="help">Note: This group's deposit is no longer refundable.</p>
                                <p class ="help">We recommend paying the full deposit amount ${{ bookingDeposit.toFixed(2) }} required for the room to avoid delays confirming your room while we wait for the payment from your guests. The deposit will still apply to your portion only</p>
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label font-weight-600">Payment Authorization</label>
                        <p class="help for-label">
                            Check here if you understand that your balance will be automatically charged to the card you use for your deposit on the balance due date unless you contact us in writing at least 7 days before the balance due date?
                        </p>
                        <div class="control">
                          <label>
                            <p class="checkbox">
                                <input type="checkbox" v-model="booking.cardConfirmation">
                                Yes, I understand that payments will be automatically charged to the card I use for my deposit according to the payment structure of {{ getPaymentStructureText() }} unless I contact Barefoot Bridal in writing at least 3 business days prior via email at <a target="_blank"  :href="`mailto:${group.groupsEmail}`" class="has-text-link">{{ group.groupsEmail }}</a> or text 866-822-7336.
                            </p>
                          </label>
                        </div>
                        <p v-if="('cardConfirmation' in bookingErrors)" class="help is-danger">{{ bookingErrors['cardConfirmation'][0] }}</p>
                    </div>
                    <p class="is-size-7 has-text-justified">
                        Notwithstanding anything contained in my Cardholder Agreement with the provider that is to the contrary,
                        written notice of rejection or cancellation of these arrangements must be received in writing within the time limits stated in the <a target="_blank" :href="group.termsConditionsUrl" class="has-text-link">Terms & Conditions</a>.
                        If not received, no charge-backs or cancellation will then be accepted.
                        My signature on this charge confirmation form is an acknowledgement that I have received and read the <a target="_blank" :href="group.termsConditionsUrl" class="has-text-link">Terms & Conditions</a> and that I understand the Cancellation Policy,
                        which details this company's policies on payments, cancellations and refunds for the travel arrangements I have made.
                        You should review this document thoroughly before finalizing any travel arrangements. Barefoot Bridal cancellation fees are in addition to any supplier cancellation fees.
                        I am aware of all cancellation policies and agree not to dispute or attempt to charge back any of the above signed for and acknowledged charges.
                    </p>
                    <br>
                    <div class="field">
                        <label class="label font-weight-600">Terms & Conditions Signature</label>
                        <p class="help for-label">
                            Type your name to authorize this transaction on your credit card and to indicate that you accept and acknowledge these <a target="_blank" :href="group.termsConditionsUrl" class="has-text-link">Terms & Conditions</a>.
                        </p>
                        <div class="control">
                            <input type="text" v-model="booking.cardSignature" :placeholder="`${booking.clients[0]['firstName']} ${booking.clients[0]['lastName']}`" class="input is-capitalized" :class="{ 'is-danger': ('cardSignature' in bookingErrors) }">
                        </div>
                        <p v-if="('cardSignature' in bookingErrors)" class="help is-danger">{{ bookingErrors['cardSignature'][0] }}</p>
                    </div>
                    <br/>
                    <div class="field">
                      <div class="notification">
                          <p><strong>Note:</strong> Charges on your credit card statement may appear under <span v-if="group.supplierName">{{ group.supplierName }}</span> and not Barefoot Bridal. Please confirm all charges with us prior to initiating a dispute.</p>
                      </div>
                    </div>
                </div>
                </template>

                <template v-if="step > 4" class="is-font-family-montserrat">
                <p class="is-size-5 font-weight-600 ">Thank you for your {{ group.is_fit ? 'quote request' : 'booking' }}!</p>
                <br>
                <p>Further communication will be via e-mail. Please check your spam folder and if you did not receive an e-mail confirmation from us, reach out to <a :href="`mailto:${group.groupsEmail}`" target="_blank" class="has-text-black"><b>{{ group.groupsEmail }}</b></a>.</p>
                </template>
            </div>
            <div v-if="step <= 4" class="form-footer">
                <div class="columns is-mobile">
                    <div class="column">
                        <button v-if="step > 1 && step <= 4" @click="back" class="button is-outlined is-dark" :disabled="isLoading">Back</button>
                    </div>
                    <div class="column is-narrow">
                        <button @click="next" class="button is-outlined is-dark" :class="{ 'is-loading': isLoading }">{{ (step < 4) ? 'Next' : (group.is_fit ? 'Get a Quote' : 'Book Now') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </modal>
</div>
</template>

<script>
import validate from 'card-validator';

export default {
    props: {
        group: {
            type: Object,
            required: true
        },
        hotels: {
            type: Array,
            required: true
        },
        countries: {
            type: Array,
            required: true
        },
        customCssClass: {
            type: String,
            required: false,
            default: 'button is-medium is-fat is-rounded is-outlined is-black hidden'
        },
        customBooking: {
            type: Object,
            required: false,
        },
        customHotel: {
            type: Number,
            required: false
        },
        customRoom: {
            type: Number,
            required: false,
        }
    },
    data() {
        return {
            show: false,
            step: 1,
            isLoading: false,
            booking: {
                hotel: this.hotels.length == 1 ? this.hotels[0].id : null,
                room: null,
                bed: null,
                checkIn: null,
                checkOut: null,
                totalGuests: 0,
                specialRequests: null,
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
                card: {
                    name: null,
                    number: '',
                    type: null,
                    expMonth: null,
                    expYear: null,
                    code: null
                },
                address: {
                    country: 1,
                    state: null,
                    city: null,
                    line1: null,
                    line2: null,
                    zipCode: null
                },
                insurance: null,
                declinedInsuranceAgreements: {
                    first: false,
                    second: false,
                    third: false,
                    fourth: false,
                    fifth: false,
                },
                insuranceSignature: '',
                deposit: null,
                cardConfirmation: false,
                cardSignature: '',
                transportation: true,
                declinedTransportation: false,
            },
            minimumDeposit: 0,
            minimumDepositBaseline: 0,
            bookingDeposit: 0,
            bookingErrors: {},
            newClient: {
                firstName: null,
                lastName: null,
                email: null
            },
            newClientErrors: {},
            newClientIsLoading: false,
            cardTypeClass: 'fas fa-credit-card',
            subTotal: 0,
            insuranceRate: 0,
            transportationRate: 0,
            transportationRateBaseline: 0,
            selectedColor: 'pink',
        }
    },
    computed: {
        selectedHotel () {
            return this.hotels.find(hotel => hotel.id == this.booking.hotel) || {};
        },
        selectedRoom() {
            return (this.selectedHotel.rooms || []).find(room => room.id == this.booking.room) || {};
        },
        maxGuests() {
            return this.selectedRoom.maxGuests;
        },
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
        cardNumber: {
            get() {
                return this.booking.card['number'];
            },
            set(number) {
                this.booking.card['number'] = number;

                var numberValidation = validate.number(this.booking.card['number']);
                if (numberValidation.card != null && ['visa', 'mastercard', 'american-express', 'discover'].includes(numberValidation.card.type)) {
                    if (numberValidation.card.type == 'american-express') {
                        this.booking.card['type'] = 'amex';
                    } else {
                        this.booking.card['type'] = numberValidation.card.type;
                    }

                    this.cardTypeClass = `fab fa-cc-${this.booking.card['type']}`;
                } else {
                    this.booking.card['type'] = null;

                    this.cardTypeClass = 'fas fa-credit-card';
                }
            }
        },
        invalidCard() {
            return !(validate.number(this.cardNumber).isPotentiallyValid);
        },
        cardCode() {
            var numberValidation = validate.number(this.cardNumber);

            if(numberValidation.card != null) {
                return numberValidation.card.code;
            } else {
                return {
                    name: 'CVV',
                    size: 3
                }
            }
        },
        selectedCountry() {
            return this.countries.find(country => country.id == this.booking.address['country']) || {};
        },
    },
    watch: {
        'booking.insurance': {
            handler(newValue) {
                if (newValue === false) {
                    this.minimumDeposit = (this.minimumDeposit == this.minimumDepositBaseline) ? this.minimumDeposit : this.minimumDeposit - this.insuranceRate; 
                    this.bookingDeposit = this.bookingDeposit - this.insuranceRate;
                } else if (newValue === true) {
                    this.minimumDeposit = this.minimumDeposit + this.insuranceRate;
                    this.bookingDeposit = this.bookingDeposit + this.insuranceRate;
                }
            }
        },

        'booking.transportation': {
            handler(newValue) {
                if (newValue === false) {
                    this.transportationRate = 0;
                } else if (newValue === true) {
                    this.transportationRate = this.group.hasTransportation ? this.transportationRateBaseline : 0;
                }
            }
        },
    },
    methods: {
        close() {
            if (this.step > 4) {
                Object.assign(this.$data, this.$options.data.apply(this));
            }

            this.show = false;
        },
        getStartDate() {
            var date = new Date(this.group.date);
            date.setDate(date.getDate() - 10);

            return date;
        },
        getEndDate() {
            var date = new Date(this.group.date);
            date.setDate(date.getDate() + 10);

            return date;
        },
        setBirthDate(guest, date) {
            guest.birthDate = date instanceof Date ? date.toDateString() : null;
        },
        back() {
            if (this.step == 4) {
                this.booking.insurance = null;
            }

            if (this.group.is_fit && this.step == 4) {
                this.step = 2;
            } else {
                this.step--;
            }
        },
        next() {
            this.bookingErrors = {};
            this.isLoading = true;
            let request = this.$http.post(`/groups/${this.group.id}/new-booking/${this.step}`, this.booking)
                .then(response => {
                    if (typeof this[`step${this.step}`] === 'function') {
                        this[`step${this.step}`](response);
                    }

                    if (this.group.is_fit && this.step == 2) {
                        this.step = 4;
                    } else {
                        this.step++;
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
        step3(response) {
            this.subTotal = response.data.subTotal;
            this.transportationRateBaseline = this.group.hasTransportation ? response.data.transportationRate : 0;
            this.transportationRate = this.booking.transportation ? this.transportationRateBaseline : 0;
            this.insuranceRate = response.data.insuranceRate;
            this.minimumDeposit = response.data.minimumDeposit;
            this.minimumDepositBaseline = response.data.minimumDeposit;
            this.bookingDeposit = response.data.bookingDeposit;
        },
        addClient() {
            this.newClientErrors = {};

            this.newClientIsLoading = true;
            let request = this.$http.post(`/groups/${this.group.id}/new-booking/seperate-client`, {newClient: this.newClient, clients: this.booking.clients})
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

            this.booking.hotel = this.customHotel;
            this.booking.room = this.customRoom;

            this.booking.checkIn = this.customBooking.checkIn;
            this.booking.checkOut = this.customBooking.checkOut;

            this.booking.totalGuests = Number(this.customBooking.adults) + Number(this.customBooking.children);

            if(this.customBooking.birthDates.length > 0) {

                this.booking.guests = [];

                this.customBooking.birthDates.forEach(date => {
                    let guest = {
                        firstName: null,
                        lastName: null,
                        birthDate: date,
                        gender: null,
                        client: null
                    };

                    this.booking.guests.push(guest);
                });
            }

            this.show = true;
        },
        getPaymentStructureText() {
            if (!this.group.dueDates || this.group.dueDates.length === 0) {
                return this.group.balance_due_date ? `balance due on ${this.group.balance_due_date}` : 'my group';
            }

            const paymentTexts = this.group.dueDates.map(dueDate => {
                if (dueDate.type === 'nights') {
                    return `${parseInt(dueDate.amount)} night(s) due on ${dueDate.date}`;
                } else if (dueDate.type === 'percentage') {
                    return `${parseInt(dueDate.amount)}% due on ${dueDate.date}`;
                } else if (dueDate.type === 'price') {
                    return `$${dueDate.amount} due on ${dueDate.date}`;
                }
            }).filter(Boolean);

            let balanceText = '';
            if (this.group.balance_due_date) {
                balanceText = `balance due on ${this.group.balance_due_date}`;
            }

            const parts = [];
            if (paymentTexts.length > 0) {
                parts.push(paymentTexts.join(', '));
            }
            if (balanceText) {
                parts.push(balanceText);
            }

            return parts.join(' and ');
        },
    }
}
</script>
