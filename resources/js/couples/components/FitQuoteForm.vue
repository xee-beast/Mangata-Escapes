<template>
    <div>
        <button @click="show = true" class="button is-medium is-fat is-rounded is-outlined is-black">ACCEPT QUOTE</button>
        <modal :is-active="show" @hide="hideModal">
            <div class="form-container">
                <div class="form-header">
                    <div class="form-title">{{ group.bride_first_name }} & {{ group.groom_first_name }} - Accept Quote</div>
                    <button type="button" class="modal-close-booking" aria-label="close" @click="close">
                        <span class="icon"><i class="fas fa-times"></i></span>
                    </button>
                </div>
                <div class="form-content is-font-family-montserrat">
                    <lost-code v-if="lostCode" @sent="lostCode = false" :group="group.id" return-text="Back" />
                    <div v-else-if="!pendingFitQuote">
                        <div class="field">
                            <p v-if="fitQuoteErrorType == 'missing_fit_quote'">
                                We are in the process of finalizing a quote for you and it will be sent to you soon. For more information, please contact us at <a style="color: black;" target="_blank" :href="`mailto:${groupsEmail}`"><b>{{ groupsEmail }}</b></a>.
                            </p>
                            <p v-else-if="fitQuoteErrorType == 'accepted_fit_quote'">
                                You have already accepted a quote. For more information, please contact us at <a style="color: black;" target="_blank" :href="`mailto:${groupsEmail}`"><b>{{ groupsEmail }}</b></a>.
                            </p>
                        </div>
                        <button @click="pendingFitQuote = true" class="button is-outlined is-dark">Back</button>
                    </div>
                    <template v-else>
                        <template v-if="step == 1">
                            <client-form v-model="clientCredentials.booking" :error-bag="clientCredentialsErrors" @codeLost="lostCode = true" />
                        </template>
                        <template v-else-if="step == 2">
                            <p>
                                Are you sure you want to accept your quote? If you need to take a look at your quote invoice, <u style="cursor: pointer;"><b @click="viewQuoteInvoice">click here</b></u>.
                            </p>
                            <form ref="form" :action="`/${group.slug}/quote-invoice`" target="_blank" method="POST">
                                <input type="hidden" name="_token" :value="csrfToken">
                                <input type="hidden" name="booking[email]" :value="clientCredentials.booking['email']">
                                <input type="hidden" name="booking[code]" :value="clientCredentials.booking['code']">
                            </form>
                        </template>
                        <template v-else-if="step == 3">
                            <p>
                                Congratulations! You've accepted your quote. But we can't move forward until we receive a deposit. If you have any questions, please contact your Wedding Guest Concierges at <a style="color: black;" target="_blank" :href="`mailto:${groupsEmail}`"><b>{{ groupsEmail }}</b></a>.
                            </p>
                        </template>
                    </template>
                </div>
                <div v-if="!lostCode && step <= 3 && pendingFitQuote" class="form-footer">
                    <div class="columns is-mobile">
                        <div class="column">
                            <button v-if="step == 2" @click="back" class="button is-dark is-outlined" :disabled="isLoading">Back</button>
                        </div>
                        <div class="column is-narrow">
                            <button v-if="step == 1" @click="next" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }">Next</button>
                            <button v-else-if="step == 2" @click="next" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }">Accept Quote</button>
                            <button v-else-if="step == 3" @click="emitProceedToPayment" class="button is-dark is-outlined">Proceed to Payment</button>
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
            csrfToken: {
                type: String,
                required: true
            },
            groupsEmail:{
                type: String,
                default: '',
            }
        },
        data() {
            return {
                show: false,
                step: 1,
                isLoading: false,
                clientCredentials: {
                    booking: {
                        email: null,
                        code: null,
                    }
                },
                clientCredentialsErrors: {},
                lostCode: false,
                pendingFitQuote: true,
                fitQuoteErrorType: '',
            }
        },
        methods: {
            close() {
                if (this.lostCode || !this.pendingFitQuote || this.step == 3) {
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

            viewQuoteInvoice () {
                this.$refs.form.submit();
            },

            next() {
                this.isLoading = true;
                this.clientCredentialsErrors = {};

                let request = this.$http.post(`/groups/${this.group.id}/accept-fit-quote/${this.step}`, this.clientCredentials)
                    .then(response => {
                        this.step++;
                    })
                    .catch (error => {
                        if (error.response.status == 422) {
                            this.clientCredentialsErrors = error.response.data.errors;
                        }

                        if (error.response.status == 403) {
                            this.pendingFitQuote = false;
                            this.fitQuoteErrorType = error.response.data.error;
                        }
                    });

                request.then(() => {
                    this.isLoading = false;
                });
            },

            emitProceedToPayment() {
                window.EventBus.$emit('open-payment-form', {
                    booking: { ...this.clientCredentials.booking }
                });

                this.close();
            },
        },
    }
</script>
