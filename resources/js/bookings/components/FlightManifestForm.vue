<template>
    <div>
        <button @click="show = true" class="button is-medium is-rounded is-outlined is-black custom-booking-button-class">FLIGHT ITINERARY</button>
        <modal :is-active="show" @hide="close">
            <div class="form-container">
                <div class="form-header">
                    <div class="form-title">Flight Itinerary</div>
                </div>
                <div class="form-content">
                    <lost-code v-if="lostCode" @sent="lostCode = false" return-text="Flight Itinerary"/>
                    <div v-else-if="!quoteAccepted">
                        <div class="field">
                            <p>
                                A quote has not been agreed upon yet. You first need to accept the quote to start the process of confirming your booking.
                                <br>
                                If a quote was not received, we are in the process of finalizing the quote and it will be sent to you soon. For more information, please contact us at <a style="color: black;" target="_blank" :href="`mailto:${groupsEmail}`"><b>{{ groupsEmail }}</b></a>.
                            </p>
                        </div>
                        <button @click="quoteAccepted = true" class="button is-outlined is-dark">Back</button>
                    </div>
                    <template v-else>
                        <template v-if="step == 1">
                            <div v-if="!checkInWithinSevenDays">
                                <ul v-if="showErrorAlert">
                                    <li>Looks like you already submitted your flight itinerary!</li>
                                    <li>Need to make changes?</li>
                                    <li>Please email {{ groupsEmail }} with any changes you need to make.</li>
                                    <li>Thank you!</li>
                                </ul>
                                <client-form v-if="!showErrorAlert" v-model="flightManifest.booking" :error-bag="formErrors" @codeLost="lostCode = true" />
                            </div>
                            <div v-else>
                                <span class="label-description">Unfortunately, you have missed the deadline to be added to the flight manifest. Please email <a class="has-text-link" target="_blank" :href="`mailto:${groupsEmail}`">{{ groupsEmail }}</a> if you are due a refund and be sure to secure your own airport transfers. Thank you!</span>
                            </div>
                        </template>
                        <template v-if="step == 2">
                            <template v-if="transportation">
                                <span class="label-description">
                                    Thank you for entering your flight information! For your arrival, please enter the date and flight number for your arrival into the country you are traveling to. For your departure, please enter the date and flight number for the country you're traveling from. Your arrival and departure times will automatically populate based on the information you have provided. If you have any questions or concerns, please do not hesitate to contact us at {{ groupsEmail }} or via phone or text at 866-822-7336
                                </span>
                                <div v-if="flightManifest.guests.length > 1 && this.sameTransportationType" class="field" style="margin-top: 20px;">
                                    <label class="label">Will all guests included in your booking be arriving & departing on the same flights?</label>
                                    <div class="control">
                                        <label class="radio">
                                            <input type="radio" v-model="clone" :value="true">
                                            Yes
                                        </label>
                                        <label class="radio">
                                            <input type="radio" v-model="clone" :value="false">
                                            No
                                        </label>
                                    </div>
                                </div>
                                <template  v-if="!clone">
                                    <div class="form-seperator">
                                        <label class="label">Please select the guests to which the following flight information applies. If any guest is on a different flight, you can submit the form again.</label>
                                    </div>
                                    <div v-if="flightManifest.guests.length > 0">
                                        <div v-for="(guest, index) in flightManifest.guests" :key="guest.id">
                                            <div class="columns">
                                                <div class="column is-narrow ">
                                                    <label class="checkbox">
                                                        <input type="checkbox" checked @change="removeGuest(index)" />
                                                        {{ isChild(guest.dob) ? guest.name + ' (' + guest.dob + ')' : guest.name }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="removedGuestFlightManifests.length > 0">
                                        <div v-for="(guest, index) in removedGuestFlightManifests" :key="'removed-' + guest.id">
                                            <div class="columns">
                                                <div class="column is-narrow ">
                                                    <label class="checkbox">
                                                        <input type="checkbox" @change="restoreGuest(index)" />
                                                        {{ isChild(guest.dob) ? guest.name + ' (' + guest.dob + ')' : guest.name }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <form-panel v-if="flightManifest.guests.length > 0">
                                    <div>
                                        <div class="columns">
                                            <div class="column">
                                                <form-field label="Phone" :errors="formErrors['form.phoneNumber']">
                                                    <control-input 
                                                        v-model="flightManifest.form.phoneNumber"
                                                        placeholder="Phone"
                                                        :class="{ 'is-danger': (formErrors['form.phoneNumber'] || []).length }"
                                                    />
                                                </form-field>
                                            </div>
                                        </div>
                                        <div v-if="shouldShowArrivalFields">
                                            <h2 class="mb-15 has-background-primary has-text-weight-bold is-size-5" style="padding: 10px 5px 5px 5px;">Arrival Information</h2>
                                        </div>
                                        <div class="columns" v-if="shouldShowArrivalFields">
                                            <div class="column">
                                                <form-field label="Departure Airport" class="control" :errors="formErrors['form.arrivalDepartureAirport']">
                                                    <control-input 
                                                        v-model="flightManifest.form.arrivalDepartureAirport"  
                                                        placeholder="Enter departure airport code"
                                                        @input="debouncedFetchFlightTimes('arrival')"
                                                        :class="{ 'is-danger': (formErrors['form.arrivalDepartureAirport'] || []).length }"
                                                    />
                                                </form-field>
                                                <span class="span-description">Please enter the 3-letter airport code (like <b>JFK</b> for John F. Kennedy International Airport in New York).</span>
                                            </div>
                                            <div class="column">
                                                <form-field label="Departure Date" :errors="formErrors['form.arrivalDepartureDate']">
                                                    <date-picker
                                                        v-model="flightManifest.form.arrivalDepartureDate"
                                                        :min-date="getStartDate()"
                                                        :max-date="getEndDate()"
                                                        mode="date"
                                                        :time="null"
                                                        @input="fetchFlightTimes('arrival')"
                                                        :popover="{ visibility: 'focus' }"
                                                    >
                                                        <template v-slot="{ inputValue, inputEvents }">
                                                            <input
                                                                placeholder="MM/DD/YYYY"
                                                                :class="'input' + ((formErrors['form.arrivalDepartureDate'] || []).length ? ' is-danger' : '')"
                                                                :value="inputValue"
                                                                v-on="inputEvents"
                                                            />
                                                        </template>
                                                    </date-picker>
                                                </form-field>
                                            </div>
                                        </div>
                                        <div class="columns" v-if="shouldShowArrivalFields">
                                            <div class="column">
                                                <form-field label="Arrival Airport" class="control" :errors="formErrors['form.arrivalAirport']">
                                                    <control-select 
                                                        style="width:100%;"
                                                        v-model="flightManifest.form.arrivalAirport"  
                                                        control-class="is-capitalized" 
                                                        :options="[ { value: '', text: 'Select Airport', disabled: true }, ...airports ]" 
                                                        default-value="" 
                                                        @change="fetchFlightTimes('arrival')"
                                                        :class="{ 'is-danger': (formErrors['form.arrivalAirport'] || []).length }"
                                                    />
                                                </form-field>
                                            </div>
                                            <div class="column">
                                                <form-field label="Arrival Airline" class="control" :errors="formErrors['form.arrivalAirline']">
                                                    <control-select 
                                                        style="width:100%;"
                                                        v-model="flightManifest.form.arrivalAirline"  
                                                        control-class="is-capitalized" 
                                                        :options="[ { value: '', text: 'Select Airline', disabled: true }, ...airlines ]" 
                                                        default-value="" 
                                                        @change="fetchFlightTimes('arrival')"
                                                        :class="{ 'is-danger': (formErrors['form.arrivalAirline'] || []).length }"
                                                    />
                                                    <span class="span-description">If your airline does not appear above, please forward your flight confirmation to {{ groupsEmail }} for further assistance.</span>
                                                </form-field>
                                            </div>
                                        </div>
                                        <div class="columns" v-if="shouldShowArrivalFields">
                                            <div class="column">
                                                <form-field label="Arrival Flight Number" :errors="formErrors['form.arrivalNumber']">
                                                    <control-input 
                                                        v-model="flightManifest.form.arrivalNumber"
                                                        placeholder="Flight Number"
                                                        @input="debouncedFetchFlightTimes('arrival')"
                                                        :class="{ 'is-danger': (formErrors['form.arrivalNumber'] || []).length }"
                                                    />
                                                    <span class="span-description">Please enter flight number without the airline iata code.</span>
                                                </form-field>
                                            </div>
                                            <div class="column">
                                                <template v-if="fetchingArrivalFlightInfo">
                                                    <div class="loader-wrapper">
                                                        <span class="loader loading"></span> Fetching arrival flight information...
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <form-field :errors="formErrors['form.arrivalDateTime']"  label="Arrival Date & Time">
                                                        <template v-if="flightManifestDateTimeField['arrivalDateTime'] !== true">
                                                            <control-select
                                                                style="width:100%;" 
                                                                v-model="flightManifest.form.arrivalDateTime"
                                                                control-class="is-capitalized w-100" 
                                                                default-value="" 
                                                                :options="[ ...flightDateTimeOptions['arrival'] || { value: '', text: 'Enter arrival details to fetch automatically', disabled: true, selected: true} ]" 
                                                                :class="{ 'is-danger': (formErrors['form.arrivalDateTime'] || []).length }"
                                                            />
                                                        </template>
                                                        <template v-else>
                                                            <date-picker
                                                                v-model="flightManifest.form.arrivalDateTime"
                                                                :min-date="getStartDate()"
                                                                :max-date="getEndDate()"
                                                                mode="datetime"
                                                                is24hr
                                                                :popover="{ visibility: 'focus' }"
                                                            >
                                                                <template v-slot="{ inputValue, inputEvents }">
                                                                    <input
                                                                        placeholder="MM/DD/YYYY hh:mm"
                                                                        :class="'input' + ((formErrors['form.arrivalDateTime'] || []).length ? ' is-danger' : '')"
                                                                        :value="inputValue"
                                                                        v-on="inputEvents"
                                                                    />
                                                                </template>
                                                            </date-picker>
                                                        </template>
                                                        <span class="span-description">If there are multiple flights, please click on the above field to select your arrival date and time from the dropdown.</span>
                                                    </form-field>
                                                </template>
                                            </div>
                                        </div>
                                        <div v-if="shouldShowDepartureFields">
                                            <h2 class="mb-15 has-background-primary has-text-weight-bold is-size-5" style="padding: 10px 5px 5px 5px;">Departure Information</h2>
                                        </div>
                                        <div class="columns" v-if="shouldShowDepartureFields">
                                            <div class="column">
                                                <form-field label="Departure Airport" :errors="formErrors['form.departureAirport']">
                                                    <control-select
                                                        style="width:100%;" 
                                                        v-model="flightManifest.form.departureAirport"  
                                                        control-class="is-capitalized  w-100" 
                                                        :options="[ { value: '', text: 'Select Airport', disabled: true }, ...airports ]" 
                                                        default-value="" 
                                                        @change="fetchFlightTimes('departure')"
                                                        :class="{ 'is-danger': (formErrors['form.departureAirport'] || []).length }"
                                                    />
                                                </form-field>
                                            </div>
                                            <div class="column">
                                                <form-field label="Departure Date" :errors="formErrors['form.departureDate']">
                                                    <date-picker
                                                        v-model="flightManifest.form.departureDate"
                                                        :min-date="getStartDate()" 
                                                        :max-date="getEndDate()"
                                                        mode="date"
                                                        :time="null"
                                                        @input="fetchFlightTimes('departure')"
                                                        :popover="{ visibility: 'focus' }"    
                                                    >
                                                        <template v-slot="{ inputValue, inputEvents }">
                                                            <input
                                                                placeholder="MM/DD/YYYY"
                                                                :class="'input' + ((formErrors['form.departureDate'] || []).length ? ' is-danger' : '')"
                                                                :value="inputValue"
                                                                v-on="inputEvents"
                                                            />
                                                        </template>
                                                    </date-picker>
                                                </form-field>
                                            </div>
                                        </div>
                                        <div class="columns" v-if="shouldShowDepartureFields">
                                            <div class="column">
                                                <form-field label="Departure Airline" :errors="formErrors['form.departureAirline']">
                                                    <control-select
                                                        style="width:100%;" 
                                                        v-model="flightManifest.form.departureAirline"  
                                                        control-class="is-capitalized  w-100" 
                                                        :options="[ { value: '', text: 'Select Airline', disabled: true }, ...airlines ]" 
                                                        default-value="" 
                                                        @change="fetchFlightTimes('departure')"
                                                        :class="{ 'is-danger': (formErrors['form.departureAirline'] || []).length }"
                                                    />
                                                    <span class="span-description">If your airline does not appear above, please forward your flight confirmation to {{ groupsEmail }} for further assistance.</span>
                                                </form-field>
                                            </div>
                                            <div class="column">
                                                <form-field label="Departure Flight Number" :errors="formErrors['form.departureNumber']">
                                                    <control-input
                                                        v-model="flightManifest.form.departureNumber"
                                                        placeholder="Flight Number"
                                                        @input="debouncedFetchFlightTimes('departure')"
                                                        :class="{ 'is-danger': (formErrors['form.departureNumber'] || []).length }"
                                                    />
                                                    <span class="span-description">Please enter flight number without the airline iata code.</span>
                                                </form-field>
                                            </div>
                                        </div>
                                        <div class="columns" v-if="shouldShowDepartureFields">
                                            <div class="column">
                                                <template v-if="fetchingDepartureFlightInfo">
                                                    <div class="loader-wrapper">
                                                        <span class="loader loading"></span> Fetching departure flight information...
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <form-field :errors="formErrors['form.departureDateTime']"  label="Departure Date & Time">
                                                        <template v-if="flightManifestDateTimeField['departureDateTime'] !== true">
                                                            <control-select
                                                                style="width:100%;" 
                                                                v-model="flightManifest.form.departureDateTime"
                                                                control-class="is-capitalized w-100" 
                                                                default-value="" 
                                                                :options="[ ...flightDateTimeOptions['departure'] || { value: '', text: 'Enter departure details to fetch automatically', disabled: true, selected: true} ]" 
                                                                :class="{ 'is-danger': (formErrors['form.departureDateTime'] || []).length }"
                                                            />
                                                        </template>
                                                        <template v-else>
                                                            <date-picker
                                                                v-model="flightManifest.form.departureDateTime"
                                                                :min-date="getStartDate()"
                                                                :max-date="getEndDate()"
                                                                mode="datetime"
                                                                is24hr
                                                                :popover="{ visibility: 'focus' }"
                                                            >
                                                                <template v-slot="{ inputValue, inputEvents }">
                                                                    <input
                                                                        placeholder="MM/DD/YYYY hh:mm"
                                                                        :class="'input' + ((formErrors['form.departureDateTime'] || []).length ? ' is-danger' : '')"
                                                                        :value="inputValue"
                                                                        v-on="inputEvents"
                                                                    />
                                                                </template>
                                                            </date-picker>
                                                        </template>
                                                        <span class="span-description">If there are multiple flights, please click on the above field to select your departure date and time from the dropdown.</span>
                                                    </form-field>
                                                </template>
                                            </div>
                                            <div class="column">
                                            </div>
                                        </div>
                                    </div>
                                </form-panel>
                            </template>
                            <template v-else>
                                <p class="is-size-5 has-text-weight-normal">Your booking does not include airport transfers.</p>
                                <br>
                                <p>If you wish to add transfers to your booking or you believe this is a mistake, please contact us at <a class="has-text-link" :href="`mailto:${groupsEmail}`">{{ groupsEmail }}</a>.</p>
                            </template>
                        </template>
                        <template v-if="step > 2">
                            <p class="is-size-5 has-text-weight-normal">
                                Thank you for submitting your flight itinerary! You will receive travel documents with instructions on how to find your shuttle when you arrive approximately 1 week prior to your departure. If you have any questions or concerns, please do not hesitate to contact us at {{ groupsEmail }} or by phone or text at 866-822-7336.
                            </p>
                        </template>
                    </template>
                </div>
                <div v-if="!lostCode && step <= 2 && quoteAccepted" class="form-footer">
                    <div class="columns is-mobile">
                        <div class="column">
                            <button v-if="step > 1" @click="back" class="button is-dark is-outlined" :disabled="isLoading">Back</button>
                        </div>
                        <div v-if="transportation" class="column is-narrow">
                            <button v-if="step != 2 && !showErrorAlert && !checkInWithinSevenDays" @click="next" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }">Next</button>
                            <button v-else-if="flightManifest.guests.length > 0 && !showErrorAlert && !checkInWithinSevenDays" @click="next" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }">Submit Manifest</button>
                            <button v-else @click="close" class="button is-dark is-outlined">Close</button>
                        </div>
                        <div v-else class="column is-narrow">
                            <button @click="close" class="button is-dark is-outlined">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </modal>
        <modal :is-active="showModal" @hide="closeModal">
            <div class="form-container">
                <div class="form-header">
                    <div class="form-title">Flight Itinerary</div>
                </div>
                <div class="form-content">
                    <div class="field">
                        <div v-for="(date_check, index) in dates_check" :key="index" class="radio-flight">
                            <label class="label">
                                {{ date_check.name }}'s {{ date_check.type }} date for their hotel is {{ date_check.check_in_date || date_check.check_out_date }}, 
                                but you have entered {{ date_check.arrival_date || date_check.departure_date }}.
                            </label>
                            <div v-if="date_check.type == 'arrival'">
                                <label class="columns">
                                    <input type="radio" :key="'arrival1_' + date_check.guest_id" :name="'arrival_' + date_check.guest_id" @change="updateDatesMismatch(date_check.guest_id, 'arrival', date_check.remove_transfer, date_check.check_in_date)" :value="date_check.remove_transfer">
                                    {{ date_check.remove_transfer }}
                                </label>
                                <label class="columns">
                                    <input type="radio" :key="'arrival2_' + date_check.guest_id" :name="'arrival_' + date_check.guest_id" @change="updateDatesMismatch(date_check.guest_id, 'arrival', date_check.change_reservation, date_check.check_in_date)" :value="date_check.change_reservation">
                                    {{ date_check.change_reservation }}
                                </label>
                                <label class="columns">
                                    <input type="radio" :key="'arrival3_' + date_check.guest_id" :name="'arrival_' + date_check.guest_id" @change="updateDatesMismatch(date_check.guest_id, 'arrival', date_check.change_flight, date_check.check_in_date)" :value="date_check.change_flight">
                                    {{ date_check.change_flight }}
                                </label>
                            </div>
                            <div v-if="date_check.type == 'departure'">
                                <label class="columns">
                                    <input type="radio" :key="'departure1_' + date_check.guest_id" :name="'departure_' + date_check.guest_id" @change="updateDatesMismatch(date_check.guest_id, 'departure', date_check.remove_transfer, date_check.check_out_date)" :value="date_check.remove_transfer">
                                    {{ date_check.remove_transfer }}
                                </label>
                                <label class="columns">
                                    <input type="radio" :key="'departure2_' + date_check.guest_id" :name="'departure_' + date_check.guest_id" @change="updateDatesMismatch(date_check.guest_id, 'departure', date_check.change_reservation, date_check.check_out_date)" :value="date_check.change_reservation">
                                    {{ date_check.change_reservation }}
                                </label>
                                <label class="columns">
                                    <input type="radio" :key="'departure3_' + date_check.guest_id" :name="'departure_' + date_check.guest_id" @change="updateDatesMismatch(date_check.guest_id, 'departure', date_check.change_flight, date_check.check_out_date)" :value="date_check.change_flight">
                                    {{ date_check.change_flight }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-footer">
                    <div class="columns is-mobile">
                        <div class="column"></div>
                        <div class="column is-narrow">
                            <button @click="next" class="button is-dark is-outlined">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </modal>
    </div>
</template>

<script>
    import ControlInput from '@dashboard/components/form/controls/Input';
    import DatePicker from 'v-calendar/lib/components/date-picker.umd';
    import ControlSelect from '@dashboard/components/form/controls/Select';
    import FormField from '@dashboard/components/form/Field';
    import FormPanel from '@dashboard/components/form/Panel';
    import moment from 'moment';

    export default {
        props: {
            groupsEmail: {
                type: String,
                required: true
            },
            airline: {
                type: Array,
                required: true
            },
        },

        components: {
            ControlInput,
            DatePicker,
            FormField,
            FormPanel,
            ControlSelect,
        },

        data() {
            return {
                show: false,
                showModal: false,
                step: 1,
                isLoading: false,
                clone: true,
                flightManifest: {
                    booking: {
                        email: null,
                        code: null
                    },
                    guests: [],
                    form: {
                        phoneNumber: null,
                        arrivalDetailsRequired: false,
                        arrivalDepartureAirport: null,
                        arrivalDepartureDate: null,
                        arrivalAirport: null,
                        arrivalAirline: null,
                        arrivalNumber: null,
                        arrivalDateTime: null,
                        arrivalManual: false,
                        departureDetailsRequired: false,
                        departureAirport: null,
                        departureDate: null,
                        departureAirline: null,
                        departureNumber: null,
                        departureDateTime: null,
                        departureManual: false,
                    },
                    dates_mismatch: {},
                },
                removedGuestFlightManifests: [],
                formErrors: {},
                guests: {},
                transportation: true,
                clientPhone: null,
                lostCode: false,
                airlines: [],
                showErrorAlert: false,
                dates_check: [],
                submitManifest: true,
                flightDateTimeOptions: [],
                sameTransportationType: false,
                flightManifestDateTimeField: {},
                debounceTimer: null,
                fetchingArrivalFlightInfo: false,
                fetchingDepartureFlightInfo: false,
                quoteAccepted: true,
                booking: {},
                checkInWithinSevenDays: false,
                airports: [],
            }
        },

        mounted() {
            this.airlines = this.airline.map(res => ({
                value: res.iata_code,
                text: res.name
            }));
        },

        computed: {
            shouldShowArrivalFields() {
                return this.flightManifest.guests.some(
                    (guest) => guest.transportationType === 1 || guest.transportationType === 2
                );
            },
            shouldShowDepartureFields() {
                return this.flightManifest.guests.some(
                    (guest) => guest.transportationType === 1 || guest.transportationType === 3
                );
            },
        },

        watch: {
            clone(newValue) {
                if (newValue) {
                    this.flightManifest.guests.push(...this.removedGuestFlightManifests);
                    this.removedGuestFlightManifests = [];
                }
            },

            shouldShowArrivalFields(newValue) {
                this.flightManifest.form.arrivalDetailsRequired = newValue;
            },

            shouldShowDepartureFields(newValue) {
                this.flightManifest.form.departureDetailsRequired = newValue;
            },
        },

        methods: {
            getStartDate() {
                var date = new Date(this.booking.check_in);
                date.setDate(date.getDate() + 1);
                return date;
            },
            
            getEndDate() {
                var date = new Date(this.booking.check_out);
                date.setDate(date.getDate() + 1);
                return date;
            },

            removeGuest(index) {
                const removedGuest = this.flightManifest.guests.splice(index, 1)[0];
                this.removedGuestFlightManifests.push(removedGuest);
            },

            restoreGuest(index) {
                const restoredGuest = this.removedGuestFlightManifests.splice(index, 1)[0];
                this.flightManifest.guests.push(restoredGuest);
            },

            debouncedFetchFlightTimes(type) {
                clearTimeout(this.debounceTimer);
                
                this.debounceTimer = setTimeout(() => {
                    this.fetchFlightTimes(type);
                }, 1000);
            },

            fetchFlightTimes(type) {
                var data;

                if (this.flightManifest.form.arrivalDepartureDate) {
                    this.flightManifest.form.arrivalDepartureDate = this.flightManifest.form.arrivalDepartureDate instanceof Date ? this.flightManifest.form.arrivalDepartureDate.toDateString() : this.flightManifest.form.arrivalDepartureDate;
                }

                if (this.flightManifest.form.departureDate) {
                    this.flightManifest.form.departureDate = this.flightManifest.form.departureDate instanceof Date ? this.flightManifest.form.departureDate.toDateString() : this.flightManifest.form.departureDate;
                }

                if (type === 'arrival' && this.flightManifest.form.arrivalDepartureAirport && this.flightManifest.form.arrivalDepartureDate && this.flightManifest.form.arrivalAirport && this.flightManifest.form.arrivalAirline && this.flightManifest.form.arrivalNumber) {
                    data = {
                        type: type,
                        departureAirport: this.flightManifest.form.arrivalDepartureAirport,
                        departureDate: this.flightManifest.form.arrivalDepartureDate,
                        arrivalAirport: this.flightManifest.form.arrivalAirport,
                        airline: this.flightManifest.form.arrivalAirline,
                        flightNumber: this.flightManifest.form.arrivalNumber,
                    }
                } else if (type === 'departure' && this.flightManifest.form.departureAirport && this.flightManifest.form.departureDate && this.flightManifest.form.departureAirline && this.flightManifest.form.departureNumber) {
                    data = {
                        type: type,
                        departureAirport: this.flightManifest.form.departureAirport,
                        departureDate: this.flightManifest.form.departureDate,
                        airline: this.flightManifest.form.departureAirline,
                        flightNumber: this.flightManifest.form.departureNumber,
                    }
                }

                if (data) {
                    if (type == 'arrival') {
                        this.fetchingArrivalFlightInfo = true;
                    } else {
                        this.fetchingDepartureFlightInfo = true;
                    }

                    let request = this.$http.post('/get/flight-time', data)
                        .then(response => {
                            let flights = response.data.flights;

                            if (flights.length > 1) {
                                let options = flights.map(flight => ({
                                    value: flight.airport_datetime,
                                    text: flight.airport_datetime_formatted,
                                }));

                                options.unshift({
                                    value: '',
                                    text: 'Select ' + type.charAt(0).toUpperCase() + type.slice(1) + ' Date & Time',
                                    disabled: true,
                                    selected: true,
                                });

                                this.$set(this.flightDateTimeOptions, type, options);
                                this.$set(this.flightManifest.form, type + 'DateTime', null);
                                this.$set(this.formErrors, 'form.' + type + 'DateTime', []);
                                this.$set(this.flightManifestDateTimeField, type + 'DateTime', false);
                                this.$set(this.flightManifest.form, type + 'Manual', false);
                            } else if (flights.length == 1) {
                                let options = [{
                                    value: flights[0].airport_datetime,
                                    text: flights[0].airport_datetime_formatted,
                                }];

                                this.$set(this.flightDateTimeOptions, type, options);
                                this.$set(this.flightManifest.form, type + 'DateTime', flights[0].airport_datetime);
                                this.$set(this.formErrors, 'form.' + type + 'DateTime', []);
                                this.$set(this.flightManifestDateTimeField, type + 'DateTime', false);
                                this.$set(this.flightManifest.form, type + 'Manual', false);
                            } else {
                                this.$set(this.flightDateTimeOptions, type, []);
                                this.$set(this.flightManifest.form, type + 'DateTime', null);
                                this.$set(this.formErrors, 'form.' + type + 'DateTime', ['No flights found. Make sure flight details are correct or enter date & time manually.']);
                                this.$set(this.flightManifestDateTimeField, type + 'DateTime', true);
                                this.$set(this.flightManifest.form, type + 'Manual', true);
                            }
                        })
                        .catch(error => {
                            let errors;
                            
                            if (error.response.status == 422) {
                                errors = error.response.data.errors;
                                errors = Object.values(errors)[0];
                            } else if (error.response.status == 404) {
                                errors = [error.response.data.errors];
                            }

                            this.$set(this.flightDateTimeOptions, type, []);
                            this.$set(this.flightManifest.form, type + 'DateTime', null);
                            this.$set(this.formErrors, 'form.' + type + 'DateTime', errors);
                            this.$set(this.flightManifestDateTimeField, type + 'DateTime', true);
                            this.$set(this.flightManifest.form, type + 'Manual', true);
                        });
                    
                    request.then(() => {
                        if (type == 'arrival') {
                            this.fetchingArrivalFlightInfo = false;
                        } else {
                            this.fetchingDepartureFlightInfo = false;
                        }
                    });
                }
            },

            close() {
                if (this.lostCode || this.checkInWithinSevenDays || this.showErrorAlert || this.step > 2 || (this.step === 2 && (!this.transportation || this.flightManifest.guests.length === 0))) {
                    Object.assign(this.$data, this.$options.data.apply(this));

                    this.airlines = this.airline.map(res => ({
                        value: res.iata_code,
                        text: res.name
                    }));
                }
                
                this.showErrorAlert = false;
                this.show = false;
            },

            closeModal() {
                this.showModal= false;
            }, 

            back() {
                if (this.step === 2) {
                    this.transportation = true;
                }

                this.step--;
            },

            updateDatesMismatch(guest_id, type, selected_option, date) {
                if (!this.flightManifest.dates_mismatch[guest_id]) {
                    this.$set(this.flightManifest.dates_mismatch, guest_id, {});
                }

                if (!this.flightManifest.dates_mismatch[guest_id][type]) {
                    this.$set(this.flightManifest.dates_mismatch[guest_id], type, {});
                }

                this.flightManifest.dates_mismatch[guest_id][type] = {
                    selected_option: selected_option,
                    date: date,
                };
            },

            next() {
                if (this.step == 2) {
                    this.dates_check = [];
                    this.showModal= false;

                    this.flightManifest.guests.forEach((guest) => {
                        guest.phoneNumber = this.flightManifest.form.phoneNumber;

                        if (this.flightManifest.form.arrivalDateTime) {
                            this.flightManifest.form.arrivalDateTime = moment(this.flightManifest.form.arrivalDateTime).format('YYYY-MM-DD HH:mm');
                        }

                        if (this.flightManifest.form.departureDateTime) {
                            this.flightManifest.form.departureDateTime = moment(this.flightManifest.form.departureDateTime).format('YYYY-MM-DD HH:mm');
                        }

                        if (guest.transportationType == 1 || guest.transportationType == 2) {
                            guest.arrivalDepartureAirport = this.flightManifest.form.arrivalDepartureAirport;
                            guest.arrivalDepartureDate = this.flightManifest.form.arrivalDepartureDate;
                            guest.arrivalAirport = this.flightManifest.form.arrivalAirport;
                            guest.arrivalAirline = this.flightManifest.form.arrivalAirline;
                            guest.arrivalNumber = this.flightManifest.form.arrivalNumber;
                            guest.arrivalDateTime = this.flightManifest.form.arrivalDateTime;
                            guest.arrivalDate = this.flightManifest.form.arrivalDateTime ? this.formatDate(this.flightManifest.form.arrivalDateTime) : null;
                            guest.arrivalManual = this.flightManifest.form.arrivalManual;
                        }

                        if (guest.transportationType == 1 || guest.transportationType == 3) {
                            guest.departureAirport = this.flightManifest.form.departureAirport;
                            guest.departureDate = this.flightManifest.form.departureDate;
                            guest.departureAirline = this.flightManifest.form.departureAirline;
                            guest.departureNumber = this.flightManifest.form.departureNumber;
                            guest.departureDateTime = this.flightManifest.form.departureDateTime;
                            guest.departureDate = this.flightManifest.form.departureDateTime ? this.formatDate(this.flightManifest.form.departureDateTime) : null;
                            guest.departureManual = this.flightManifest.form.departureManual;
                        }
    
                        if (guest.arrivalDate && guest.checkIn !== guest.arrivalDate && (guest.transportationType == 1 || guest.transportationType == 2) && !(this.flightManifest.dates_mismatch[guest.id] && this.flightManifest.dates_mismatch[guest.id].arrival)) {
                            this.dates_check.push({
                                guest_id: guest.id,
                                type: 'arrival',
                                name: guest.name,
                                arrival_date: guest.arrivalDate,
                                check_in_date: guest.checkIn,
                                remove_transfer: guest.name + ' is staying at a different location prior to arriving to the hotel. Please remove the arrival transfer.',
                                change_reservation: guest.name + ' requests to change their reservation to match their flight and understands that this may result in an additional charge if additional nights are needed and that reducing nights may not result in a refund.',
                                change_flight: guest.name + ' will change their flight to match their reservation and send an updated flight to groups@barefootbridal.com when completed.',
                            });
                        } else if (guest.checkIn === guest.arrivalDate) {
                            if (this.flightManifest.dates_mismatch[guest.id] && this.flightManifest.dates_mismatch[guest.id].arrival) {
                                this.$delete(this.flightManifest.dates_mismatch[guest.id], 'arrival');
                                if (Object.keys(this.flightManifest.dates_mismatch[guest.id]).length === 0) {
                                    this.$delete(this.flightManifest.dates_mismatch, guest.id);
                                }
                            }
                        }

                        if (guest.departureDate && guest.checkOut !== guest.departureDate && (guest.transportationType == 1 || guest.transportationType == 3) && !(this.flightManifest.dates_mismatch[guest.id] && this.flightManifest.dates_mismatch[guest.id].departure)) {
                            this.dates_check.push({
                                guest_id: guest.id,
                                type: 'departure',
                                name: guest.name,
                                departure_date: guest.departureDate,
                                check_out_date: guest.checkOut,
                                remove_transfer: guest.name + ' is staying at a different location after checking out of the hotel. Please remove the departure transfer.',
                                change_reservation: guest.name + '  requests to change their reservation to match their flight and understands that this may result in an additional charge if additional nights are needed and that reducing nights may not result in a refund.',
                                change_flight: guest.name + ' will change their flight to match their reservation and send an updated flight to groups@barefootbridal.com when completed.',
                            });
                        } else if (guest.checkOut === guest.departureDate) {
                            if (this.flightManifest.dates_mismatch[guest.id] && this.flightManifest.dates_mismatch[guest.id].departure) {
                                this.$delete(this.flightManifest.dates_mismatch[guest.id], 'departure');
                                if (Object.keys(this.flightManifest.dates_mismatch[guest.id]).length === 0) {
                                    this.$delete(this.flightManifest.dates_mismatch, guest.id);
                                }
                            }
                        }
    
                        if (this.dates_check.length > 0) {
                            this.showModal = true;
                            this.submitManifest = false;
                        } else {
                            this.submitManifest = true;
                        }
                    });
                }

                if (this.submitManifest) {
                    this.formErrors = {};
                    this.isLoading = true;

                    let request = this.$http.post(`/individual-bookings/new-flight-manifest/${this.step}`, this.flightManifest)
                        .then(response => {
                            if (typeof this[`step${this.step}`] === 'function') {
                                this[`step${this.step}`](response);
                            }

                            this.step++;
                        })
                        .catch (error => {
                            if (error.response.status == 422) {
                                this.formErrors = error.response.data.errors;
                            }

                            if (error.response.status == 500) {
                                this.showErrorAlert = true;
                            }

                            if (error.response.status == 403) {
                                if (error.response.data.error == 'date_check') {
                                    this.checkInWithinSevenDays = true;
                                } else {
                                    this.quoteAccepted = false;
                                }
                            }
                        });
                    
                    request.then(() => {
                        this.isLoading = false;
                    });
                }
            },

            formatDate(date) {
                return moment(date).format('YYYY-MM-DD');
            },

            step1(response) {
                this.booking = response.data.booking;
                this.airports = response.data.airports.map(airport => ({ value: airport.id, text: airport.airport_code }));
                this.guests = response.data.guests;
                this.transportation = response.data.transportation;
                this.clientPhone = response.data.clientPhone;
                this.flightManifest.guests = [];
                this.removedGuestFlightManifests = [];

                this.guests.forEach(guest => {
                    let flightManifest = {
                        id: guest.id,
                        name: guest.firstName + ' ' + guest.lastName,
                        phoneNumber: this.clientPhone,
                        dob: guest.birthDate,
                        checkIn: this.formatDate(guest.checkIn),
                        checkOut: this.formatDate(guest.checkOut),
                        transportationType: guest.transportation_type,
                    };

                    this.flightManifest.guests.push(flightManifest);
                });

                this.sameTransportationType = this.checkSameTransportationType();
                this.sameTransportationType ? this.clone = true : this.clone = false;
            },

            checkSameTransportationType() {
                const types = [...new Set(this.flightManifest.guests.map(guest => guest.transportationType))];
                if (types.length === 1) {
                    return true;
                }

                return false;
            },

            isChild(dob) {
                let age = new Date().getFullYear() - new Date(dob).getFullYear();
                return age < 18;
            }
        }
    }
</script>