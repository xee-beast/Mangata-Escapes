<template>
    <div>
        <button @click="show = true" class="button is-medium is-rounded is-outlined is-black custom-booking-button-class">UPDATE CARD ON FILE</button>
        <modal :is-active="show" @hide="close">
            <div class="form-container">
                <div class="form-header">
                    <div class="form-title">Update Card On File</div>
                </div>
                <div class="form-content">
                    <lost-code v-if="lostCode" @sent="lostCode = false" return-text="Update Payment Method" />
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
                            <div class="field">
                                <label class="label">Email</label>
                                <div class="control">
                                    <input type="text" v-model="card.booking['email']" class="input is-warm-gray is-warm-gray-border" :class="{ 'is-danger': ('booking.email' in cardErrors)}">
                                </div>
                                <p v-if="('booking.email' in cardErrors)" class="help is-danger">{{ cardErrors['booking.email'][0] }}</p>
                            </div>
                            <div class="field">
                                <label class="label">Booking Reservation Code <a @click.prevent="lostCode = true" class="is-size-6 has-text-weight-light has-text-link is-pulled-right">Can't find your code?</a></label>
                                <div class="control">
                                    <input type="text" v-model="card.booking['code']" class="input is-warm-gray is-warm-gray-border" :class="{ 'is-danger': ('booking.code' in cardErrors)}">
                                </div>
                                <p v-if="('booking.code' in cardErrors)" class="help is-danger">{{ cardErrors['booking.code'][0] }}</p>
                            </div>
                        </template>
                        <template v-if="step == 2">
                            <template v-if="Object.keys(savedCard).length">
                                <div class="form-seperator">
                                    <label class="label">Current Card On File</label>
                                </div>
                                <div class="columns is-size-6">
                                    <div class="column">
                                        <p class="has-text-weight-normal">Credit Card</p>
                                        <p>{{ savedCard.name }}</p>
                                        <p>
                                            <i class="fab" :class="`fa-cc-${savedCard.type}`"></i>
                                            <span class="is-capitalized">{{ savedCard.type }}</span> ending in {{ savedCard.lastDigits }}.
                                        </p>
                                        <p>Expires {{ savedCard.expMonth }}/{{ savedCard.expYear }}</p>
                                    </div>
                                    <div class="column">
                                        <p class="has-text-weight-normal">Billing Address</p>
                                        <p>{{ savedCard.address.line1 }}</p>
                                        <p v-if="savedCard.address.line2">{{ savedCard.address.line2 }}</p>
                                        <p>{{ savedCard.address.city }}, {{ savedCard.address.state }}</p>
                                        <p>{{ savedCard.address.country }}, {{ savedCard.address.zipCode }}</p>
                                    </div>
                                </div>
                            </template>
                            <div class="form-seperator">
                                <label class="label">Payment Information</label>
                            </div>
                            <credit-card-form v-model="card.card" :error-bag="cardErrors" />
                            <div class="form-seperator">
                                <label class="label">Billing Address</label>
                            </div>
                            <address-form v-model="card.address" :error-bag="cardErrors" :countries="countries" />
                            <template v-if="mustSignInsurance">
                                <div class="form-seperator">
                                    <label class="label">Travel Insurance</label>
                                </div>
                                <travel-insurance-form v-model="card.insurance" :errors="cardErrors" :client="savedClient.name" :cancellationDate="cancellationDate" :groupsEmail="groupsEmail" />
                            </template>
                            <div class="form-seperator">
                                <label class="label">Terms & Conditions</label>
                            </div>
                            <terms-conditions-form v-model="card.confirmation" :errors="cardErrors" :client="savedClient.name" :booking="booking" :groupsEmail="groupsEmail" />
                        </template>
                        <template v-if="step > 2">
                            <p class="is-size-5 has-text-weight-normal">Your billing information has been updated successfully.</p>
                            <br>
                            <p>We will use this information to run all future automized payments towards your booking.</p>
                        </template>
                    </template>
                </div>
                <div v-if="!lostCode && step <= 2 && quoteAccepted" class="form-footer">
                    <div class="columns is-mobile">
                        <div class="column">
                            <button v-if="step > 1" @click="back" class="button is-dark is-outlined" :disabled="isLoading">Back</button>
                        </div>
                        <div class="column is-narrow">
                            <button @click="next" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }">{{ step < 2 ? 'Next' : 'Update Card' }}</button>
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
            groupsEmail: {
                type: String,
                required: true
            },
            countries: {
                type: Array,
                required: true
            }
        },
        data() {
            return {
                show: false,
                step: 1,
                isLoading: false,
                booking: {},
                savedClient: {},
                savedCard: {},
                card: {
                    booking: {
                        email: null,
                        code: null
                    },
                    card: {},
                    address: {},
                    confirmation: {
                        accept: false,
                        signature: null
                    },
                    insurance: {
                        accept: null,
                        signature: null,
                        declinedInsuranceAgreements: {
                            first: false,
                            second: false,
                            third: false,
                            fourth: false
                        }
                    }
                },
                cardErrors: {},
                mustSignInsurance: false,
                lostCode: false,
                quoteAccepted: true,
                cancellationDate: '',
            }
        },
        methods: {
            close() {
                if (this.lostCode || this.step > 2) {
                    Object.assign(this.$data, this.$options.data.apply(this));
                }

                this.show = false;
            },
            back() {
                this.step--;
            },
            next() {
                this.cardErrors = {};
                this.isLoading = true;

                let request = this.$http.post(`/individual-bookings/update-card/${this.step}`, this.card)
                    .then(response => {
                        if (typeof this[`step${this.step}`] === 'function') {
                            this[`step${this.step}`](response);
                        }

                        this.step++;
                    })
                    .catch(error => {
                        if (error.response.status == 422) {
                            this.cardErrors = error.response.data.errors;
                        }

                        if (error.response.status == 403) {
                            this.quoteAccepted = false;
                        }
                    });

                request.then(() => {
                    this.isLoading = false;
                });
            },
            step1(response) {
                this.booking = response.data.booking;
                this.cancellationDate = this.booking.cancellation_date ? this.$moment.utc(this.booking.cancellation_date).format('MM/DD/YYYY') : 'the cancellation date';
                this.savedClient = response.data.client;
                this.savedCard = response.data.card;
                this.mustSignInsurance = response.data.mustSignInsurance;
            }
        }
    }
</script>
