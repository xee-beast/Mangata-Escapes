<template>
    <div>
        <button @click="show = true" class="button is-medium is-rounded is-outlined is-black custom-booking-button-class">VIEW YOUR INVOICE</button>
        <modal :is-active="show" @hide="close">
            <div class="form-container">
                <div class="form-header">
                    <div class="form-title">View Invoice</div>
                </div>
                <div class="form-content">
                    <lost-code v-if="lostCode" @sent="lostCode = false" return-text="Back" />
                    <div v-else-if="!confirmed">
                        <div class="field">
                            <p>
                                The invoice will be available for this booking once it has been confirmed with the provider.
                                <br>
                                Please allow up to 3 business days to process your booking. We appreciate your patience.
                            </p>
                        </div>
                        <button @click="confirmed = true" class="button is-outlined is-dark">Back</button>
                    </div>
                    <div v-else-if="message">
                        <p class="is-size-5 mb-10" v-html="message"></p>
                    </div>
                    <template v-else>
                        <client-form v-model="invoice.booking" :error-bag="invoiceErrors" @codeLost="lostCode = true" />
                        <form v-if="!invoice.sendEmail" ref="form" :action="`/invoice`" target="_blank" method="POST">
                            <input type="hidden" name="_token" :value="csrfToken">
                            <input type="hidden" name="booking[email]" :value="invoice.booking['email']">
                            <input type="hidden" name="booking[code]" :value="invoice.booking['code']">
                            <input type="hidden" name="sendEmail" :value="invoice.sendEmail">
                        </form>
                    </template>
                </div>
                <div v-if="!lostCode && confirmed && !message" class="form-footer">
                    <div class="columns is-mobile">
                        <div class="column"></div>
                        <div class="column is-narrow">
                            <button @click="next(true)" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }">Send Invoice</button>
                            <button @click="next(false)" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }">View Invoice</button>
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
            csrfToken: {
                type: String,
                required: true
            }
        },
        data() {
            return {
                show: false,
                isLoading: false,
                invoice: {
                    booking: {
                        email: null,
                        code: null
                    },
                    validate: true,
                    sendEmail: false
                },
                invoiceErrors: {},
                message: null,
                confirmed: true,
                lostCode: false
            }
        },
        methods: {
            close() {
                if (this.lostCode) {
                    Object.assign(this.$data, this.$options.data.apply(this));
                }

                this.show = false;
                this.invoice.sendEmail = false;
                this.message = null;
            },
            next(sendEmail) {
                if(sendEmail) this.invoice.sendEmail = true; 
                this.invoiceErrors = {};
                this.message = null;
                this.isLoading = true;

                let request = this.$http.post(`/individual-bookings/invoice`, this.invoice)
                    .then(() => {
                        if (!this.invoice.sendEmail) {
                          this.$refs.form.submit();
                        }

                        Object.assign(this.$data, this.$options.data.apply(this));
                    })
                    .catch(error => {
                        if (error.response.status == 422) {
                            this.invoiceErrors = error.response.data.errors;
                        }

                        if (error.response.status == 403) {
                            this.confirmed = false;
                        }

                        if (error.response.status == 423) {
                            this.message = error.response.data.message;
                        }
                    });

                request.then(() => {
                    this.isLoading = false;
                    this.invoice.sendEmail = false;
                });
            }
        }
    }
</script>
