<template>
    <div>
        <div class="field">
            <label class="label">Cardholder Name</label>
            <div class="control">
                <input type="text" v-model="card.name" class="input is-capitalized" :class="{ 'is-danger': ('card.name' in errorBag) }">
            </div>
            <p v-if="('card.name' in errorBag)" class="help is-danger">{{ errorBag['card.name'][0] }}</p>
        </div>
        <div class="field">
            <label class="label">Card Number</label>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input type="text" v-model="cardNumber" class="input" :class="{ 'is-danger': ('card.number' in errorBag) || ('card.type' in errorBag) || invalidCard }">
                    </div>
                    <div class="control">
                        <button class="button is-static">
                            <span class="icon">
                                <i class="fa-lg" :class="cardTypeClass"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <p v-if="('card.number' in errorBag) || ('card.type' in errorBag) || invalidCard" class="help is-danger">{{ [...(errorBag['card.number'] || []), ...(errorBag['card.type'] || [])][0] || 'The card number is not valid.' }}</p>
        </div>
        <div class="field">
            <div class="field-body">
                <div class="field">
                    <label class="label">Expiration</label>
                    <div class="field-body">
                        <div class="field">
                            <div class="select is-fullwidth" :class="{ 'is-danger': ('card.expMonth' in errorBag)}">
                                <select v-model="card.expMonth">
                                    <option :value="undefined" disabled>Month</option>
                                    <option v-for="month in 12" :key="month" :value="month.toString().padStart(2, 0)">{{ month.toString().padStart(2, 0) }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <div class="select is-fullwidth" :class="{ 'is-danger': ('card.expYear' in errorBag)}">
                                <select v-model="card.expYear">
                                    <option :value="undefined" disabled>Year</option>
                                    <option :value="new Date().getFullYear()">{{ new Date().getFullYear() }}</option>
                                    <option v-for="index in 19" :key="index" :value="new Date().getFullYear() + index">{{ new Date().getFullYear() + index }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <p v-if="('card.expMonth' in errorBag) || ('card.expYear' in errorBag)" class="help is-danger">{{ [...(errorBag['card.expMonth'] || []), ...(errorBag['card.expYear'] || [])][0] }}</p>
                </div>
                <div class="field">
                    <label class="label">{{ cardCode.name }}</label>
                    <div class="control">
                        <input type="text" v-model="card.code" class="input" :class="{ 'is-danger': ('card.code' in errorBag) }">
                    </div>
                    <p v-if="('card.code' in errorBag)" class="help is-danger">{{ errorBag['card.code'][0] }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import validate from 'card-validator';

    export default {
        props: {
            value: {
                type: Object,
                required: true
            },
            errorBag: {
                type: Object,
                default: () => ({})
            }
        },
        data() {
            return {
                cardTypeClass: 'fas fa-credit-card',
            }
        },
        computed: {
            card: {
                get() {
                    return this.value;
                },
                set(value) {
                    this.$emit('input', value);
                }
            },
            cardNumber: {
                get() {
                    return this.card.number || '';
                },
                set(number) {
                    this.$set(this.card, 'number', number);
                    var numberValidation = validate.number(this.card.number);

                    if (numberValidation.card != null && ['visa', 'mastercard', 'american-express', 'discover'].includes(numberValidation.card.type)) {
                        if (numberValidation.card.type == 'american-express') {
                            this.$set(this.card, 'type', 'amex');
                        } else {
                            this.$set(this.card, 'type', numberValidation.card.type);
                        }

                        this.cardTypeClass = `fab fa-cc-${this.card.type}`;
                    } else {
                        this.$set(this.card, 'type', null);
                        this.cardTypeClass = 'fas fa-credit-card';
                    }
                }
            },
            invalidCard() {
                return !(validate.number(this.cardNumber).isPotentiallyValid);
            },
            cardCode() {
                var numberValidation = validate.number(this.cardNumber);

                if (numberValidation.card != null) {
                    return numberValidation.card.code;
                } else {
                    return {
                        name: 'CVV',
                        size: 3
                    }
                }
            }
        }
    }
</script>
