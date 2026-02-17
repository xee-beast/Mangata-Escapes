<template>
    <div>
        <div class="field">
            <label class="label">Email</label>
            <div class="control">
                <input type="text" v-model="booking.email" class="input is-warm-gray is-warm-gray-border" :class="{ 'is-danger': ('booking.email' in errorBag)}">
            </div>
            <p v-if="('booking.email' in errorBag)" class="help is-danger">{{ errorBag['booking.email'][0] }}</p>
        </div>
        <div class="field">
            <label class="label">Booking Reservation Code <a @click.prevent="$emit('codeLost')" class="is-size-6 has-text-weight-light has-text-link is-pulled-right">Can't find your code?</a></label>
            <div class="control">
                <input type="text" v-model="booking.code" class="input is-warm-gray is-warm-gray-border" :class="{ 'is-danger': ('booking.code' in errorBag)}">
            </div>
            <p v-if="('booking.code' in errorBag)" class="help is-danger">{{ errorBag['booking.code'][0] }}</p>
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
            errorBag: {
                type: Object,
                default: () => ({})
            }
        },
        computed: {
            booking: {
                get() {
                    return this.value;
                },
                set(value) {
                    this.$emit('input', value);
                }
            }
        }
    }
</script>
