<template>
    <div>
        <div class="field">
            <label class="label">Payment Authorization</label>
            <p class="help for-label">
                Check here if you understand that your balance will be automatically charged to the card you use for your deposit on the balance due date unless you contact us in writing at least 7 days before the balance due date.
            </p>
            <div class="control">
                <label class="checkbox">
                    <input type="checkbox" v-model="confirmation.accept">
                    Yes, I understand that payments will be automatically charged to the card I use for my deposit according to the payment structure of {{ getPaymentStructureText() }} unless I contact Barefoot Bridal in writing at least 3 business days prior via email at <a target="_blank" :href="`mailto:${groupsEmail}`" class="has-text-link">{{ groupsEmail }}</a> or text 866-822-7336.
                </label>
            </div>
            <p v-if="('confirmation.accept' in errors)" class="help is-danger">{{ errors['confirmation.accept'][0] }}</p>
        </div>
        <p class="is-size-7 has-text-justified">
            Notwithstanding anything contained in my Cardholder Agreement with the provider that is to the contrary,
            written notice of rejection or cancellation of these arrangements must be received in writing within the time limits stated in the <a target="_blank" :href="url" class="has-text-link">Terms & Conditions</a>.
            If not received, no charge-backs or cancellation will then be accepted.
            My signature on this charge confirmation form is an acknowledgement that I have received and read the <a target="_blank" :href="url" class="has-text-link">Terms & Conditions</a> and that I understand the Cancellation Policy,
            which details this company's policies on payments, cancellations and refunds for the travel arrangements I have made.
            You should review this document thoroughly before finalizing any travel arrangements. Barefoot Bridal cancellation fees are in addition to any supplier cancellation fees.
            I am aware of all cancellation policies and agree not to dispute or attempt to charge back any of the above signed for and acknowledged charges.
        </p>
        <br>
        <div class="field">
            <label class="label">Terms & Conditions Signature</label>
            <p class="help for-label">Type your name to authorize this transaction on your credit card and to indicate that you accept and acknowledge these <a target="_blank" :href="url" class="has-text-link">Terms & Conditions</a>.</p>
            <div class="control">
                <input type="text" v-model="confirmation.signature" :placeholder="client" class="input is-capitalized" :class="{ 'is-danger': ('confirmation.signature' in errors) }">
            </div>
            <p v-if="('confirmation.signature' in errors)" class="help is-danger">{{ errors['confirmation.signature'][0] }}</p>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            value: {
                type: Object,
                required: true
            },
            errors: {
                type: Object,
                default: () => ({})
            },
            client: {
                type: String,
                default: ''
            },
            booking: {
                type: Object,
                default: () => ({})
            },
            groupsEmail: {
                type: String,
                default: ''
            }
        },
        computed: {
            confirmation: {
                get() {
                    return this.value;
                },
                set(newConfirmation) {
                    this.$emit('input', newConfirmation);
                }
            },
            url() {
                return '/terms-conditions/' + this.booking.id;
            }
        },
        methods: {
            getPaymentStructureText() {
                if (!this.booking.booking_due_dates || this.booking.booking_due_dates.length === 0) {
                    return this.booking.balance_due_date ? `balance due on ${this.$moment.utc(this.booking.balance_due_date).format('MM/DD/YYYY')}` : 'my booking';
                }

                const paymentTexts = this.booking.booking_due_dates.map(dueDate => {
                    if (dueDate.type === 'nights') {
                        return `${parseInt(dueDate.amount)} night(s) due on ${this.$moment.utc(dueDate.date).format('MM/DD/YYYY')}`;
                    } else if (dueDate.type === 'percentage') {
                        return `${parseInt(dueDate.amount)}% due on ${this.$moment.utc(dueDate.date).format('MM/DD/YYYY')}`;
                    } else if (dueDate.type === 'price') {
                        return `$${dueDate.amount} due on ${this.$moment.utc(dueDate.date).format('MM/DD/YYYY')}`;
                    }
                }).filter(Boolean);

                let balanceText = '';
                if (this.booking.balance_due_date) {
                    balanceText = `balance due on ${this.$moment.utc(this.booking.balance_due_date).format('MM/DD/YYYY')}`;
                }

                const parts = [];
                if (paymentTexts.length > 0) {
                    parts.push(paymentTexts.join(', '));
                }
                if (balanceText) {
                    parts.push(balanceText);
                }

                return parts.join(' and ');
            }
        }
    }
</script>