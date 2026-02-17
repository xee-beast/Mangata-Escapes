<template>
<div>
    <button @click="show = true" class="button is-medium is-fat is-rounded is-outlined is-black">MAKE A PAYMENT</button>
    <modal :is-active="show" @hide="hideModal">
        <div class="form-container">
            <div class="form-header">
                <div class="form-title">{{ group.name }} - Make A Payment</div>
                <button type="button" class="modal-close-booking" aria-label="close" @click="close">
                    <span class="icon"><i class="fas fa-times"></i></span>
                </button>
            </div>
            <div class="form-content is-font-family-montserrat">
                <lost-code v-if="lostCode" @sent="lostCode = false" :group="group.id" return-text="Make A Payment" />
                <div v-else-if="!quoteAccepted">
                    <div class="field">
                        <p>
                            A quote has not been agreed upon yet. You first need to accept the quote to start the process of confirming your booking.
                            <br>
                            If a quote was not received, we are in the process of finalizing the quote and it will be sent to you soon. For more information, please contact us at <a style="color: black;" target="_blank" :href="`mailto:${group.groupsEmail}`"><b>{{ group.groupsEmail }}</b></a>.
                        </p>
                    </div>
                    <button @click="quoteAccepted = true" class="button is-outlined is-dark">Back</button>
                </div>
                <template v-else>
                    <template v-if="step == 1">
                        <client-form v-model="payment.booking" :error-bag="paymentErrors" @codeLost="lostCode = true" />
                    </template>
                    <template v-if="step == 2">
                        <div>
                            <p class="heading">Booking Details</p>
                        </div>
                        <div class="columns">
                            <div class="column is-narrow">
                                <div class="columns is-mobile" style="margin-left: 0;">
                                    <div class="column is-half-mobile has-background-grey-lighter has-text-right">
                                        <div>Total</div>
                                        <div>Payments</div>
                                        <div class="has-text-weight-normal">Balance</div>
                                    </div>
                                    <div class="column is-narrow has-background-primary">
                                        <div>${{ savedBooking.total.toFixed(2) }}</div>
                                        <div>${{ savedBooking.payments.toFixed(2) }}</div>
                                        <div class="has-text-weight-normal"> ${{ (savedBooking.total - savedBooking.payments).toFixed(2) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="column">
                                <div class="field">
                                    <label class="label">Payment Amount</label>
                                    <p v-if="savedBooking.requiredPaymentDeposit" class="help for-label">Minimum Deposit: ${{ payment.insurance.accept ? (savedBooking.minimumPayment + insuranceRate): (savedBooking.minimumPayment) }}</p>
                                    <div class="control has-icons-left">
                                        <input type="text" v-model="payment.amount" class="input" :class="{ 'is-danger': ('amount' in paymentErrors) }">
                                        <span class="icon is-left">
                                            <i class="fas fa-dollar-sign"></i>
                                        </span>
                                    </div>
                                    <p v-if="('amount' in paymentErrors)" class="help is-danger">{{ paymentErrors['amount'][0] }}</p>
                                </div>
                                <div class="field">
                                    <label class="label">Payment Type</label>
                                    <div class="control">
                                        <div class="select is-fullwidth">
                                            <select v-model="payment.type">
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
                        <div v-if="Object.keys(savedCard).length" class="field">
                            <div class="control">
                                <label class="checkbox">
                                    <input type="checkbox" v-model="payment.useCardOnFile">
                                    Use card on file (<i class="fab" :class="`fa-cc-${savedCard.type}`"></i> <span class="is-capitalized">{{ savedCard.type }}</span> ending in {{ savedCard.lastDigits }}).
                                </label>
                            </div>
                            <p v-if="('useCardOnFile' in paymentErrors)" class="help is-danger">{{ paymentErrors['useCardOnFile'] }}</p>
                        </div>
                        <template v-if="!payment.useCardOnFile">
                            <hr/>
                            <div>
                                <p class="heading">Payment Information</p>
                            </div>
                            <div class="field">
                                <div class="control">
                                    <label class="checkbox">
                                        <input type="checkbox" v-model="payment.updateCardOnFile">
                                        Update card on file
                                    </label>
                                </div>
                                <p v-if="('updateCardOnFile' in paymentErrors)" class="help is-danger">{{ paymentErrors['updateCardOnFile'] }}</p>
                            </div>
                            <credit-card-form v-model="payment.card" :error-bag="paymentErrors" />
                            <hr/>
                            <div>
                                <p class="heading">Billing Address</p>
                            </div>
                            <address-form v-model="payment.address" :error-bag="paymentErrors" :countries="countries" />
                        </template>
                        <template v-if="mustSignInsurance">
                            <hr/>
                            <div>
                                <p class="heading">Travel Insurance</p>
                            </div>
                            <travel-insurance-form v-model="payment.insurance" :errors="paymentErrors" :client="savedClient.name" :cancellationDate="group.cancellationDate" :groupsEmail="group.groupsEmail"/>
                        </template>
                        <div>
                            <hr v-if="!payment.updateCardOnFile"/>
                            <p class="heading">Terms & Conditions</p>
                        </div>
                        <terms-conditions-form v-model="payment.confirmation" :errors="paymentErrors" :url="group.termsConditionsUrl" :client="computedClientName" :group="group" />
                        <br/>
                        <div class="field">
                          <div class="notification">
                              <p><strong>Note:</strong> Charges on your credit card statement may appear under <span v-if="group.supplierName">{{ group.supplierName }}</span> and not Barefoot Bridal. Please confirm all charges with us prior to initiating a dispute.</p>
                          </div>
                        </div>
                    </template>
                    <template v-if="step > 2" class="is-font-family-montserrat">
                        <p class="is-size-5 font-weight-600">Thank you for your payment!</p>
                        <br>
                        <p>Please allow up to 3 business days to process your payment. We appreciate your patience.</p>
                    </template>
                </template>
            </div>
            <div v-if="!lostCode && step <= 2 && quoteAccepted" class="form-footer">
                <div class="columns is-mobile">
                    <div class="column">
                        <button v-if="step > 1" @click="back" class="button is-dark is-outlined" :disabled="isLoading">Back</button>
                    </div>
                    <div class="column is-narrow">
                        <button @click="next" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }">{{ step < 2 ? 'Next' : 'Submit Payment' }}</button>
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
        group: {
            type: Object,
            required: true
        },
        countries: {
            type: Array,
            required: true
        }
    },
    mounted() {
        window.EventBus.$on('open-payment-form', (payload = {}) => {
            Object.assign(this.$data, this.$options.data.apply(this));

            if (payload.booking) {
                this.payment.booking = { ...payload.booking };
            }

            this.next();
            this.show = true;
        });
    },
    computed: {
        computedClientName() {
            if (this.payment.useCardOnFile && Object.keys(this.savedCard).length) {
                return this.savedCard.name;
            } else {
                return this.payment.card.name || '';
            }
        }
    },
    data() {
        return {
            show: false,
            step: 1,
            isLoading: false,
            savedClient: {},
            savedBooking: {},
            savedCard: {},
            payment: {
                booking: {
                    email: null,
                    code: null
                },
                amount: null,
                type: 'Payment towards balance',
                useCardOnFile: true,
                updateCardOnFile: false,
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
            paymentErrors: {},
            mustSignInsurance: false,
            lostCode: false,
            quoteAccepted: true,
            insuranceRate: 0,
        }
    },
    methods: {
        close() {
            if (this.lostCode || this.step > 2) {
                Object.assign(this.$data, this.$options.data.apply(this));
            }

            this.show = false;
            this.step = 1;
        },
        hideModal() {
          this.show = false;
        },
        back() {
            this.step--;
        },
        next() {
            this.paymentErrors = {};
            this.isLoading = true;
            let request = this.$http.post(`/groups/${this.group.id}/new-payment/${this.step}`, this.payment)
                .then(response => {
                    if (typeof this[`step${this.step}`] === 'function') {
                        this[`step${this.step}`](response);
                    }

                    this.step++;
                })
                .catch(error => {
                    if (error.response.status == 422) {
                        this.paymentErrors = error.response.data.errors;
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
            this.savedClient = response.data.client;
            this.savedCard = response.data.card;
            this.savedBooking = response.data.booking;
            if (Object.keys(this.savedCard).length)
                this.payment.useCardOnFile = true;
            else {
                this.payment.useCardOnFile = false;
            }
            this.mustSignInsurance = response.data.mustSignInsurance;
            this.insuranceRate = response.data.booking.insuranceRate;
        }
    }
}
</script>
