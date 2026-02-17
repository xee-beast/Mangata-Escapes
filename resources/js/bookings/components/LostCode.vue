<template>
    <div v-if="!codeSent">
        <div class="field">
            <label class="label">Email</label>
            <p class="help for-label">Enter your email address and we will resend your booking reservation code.</p>
            <div class="control">
                <input type="text" v-model="sendCodeData.email" class="input" :class="{ 'is-danger': ('email' in sendCodeDataErrors) }">
            </div>
            <p v-if="('email' in sendCodeDataErrors)" class="help is-danger">{{ sendCodeDataErrors['email'][0] }}</p>
        </div>
        <button @click="sendCode" class="button is-outlined is-dark" :class="{ 'is-loading': isLoading }">Send</button>
    </div>
    <div v-else>
        <div class="field">
            <p>Your booking reservation code has been sent to <b>{{ sendCodeData.email }}</b>.</p>
        </div>
        <button @click="back" class="button is-outlined is-dark">{{ returnText }}</button>
    </div>
</template>

<script>
    export default {
        props: {
            returnText: {
                type: String,
                default: 'Back'
            }
        },
        data() {
            return {
                sendCodeData: {
                    email: null
                },
                sendCodeDataErrors: {},
                isLoading: false,
                codeSent: false
            }
        },
        methods: {
            sendCode() {
                this.isLoading = true;

                let request = this.$http.post(`/individual-bookings/resend-code`, this.sendCodeData)
                    .then((response) => {
                        this.codeSent = true;
                    })
                    .catch(error => {
                        if (error.response.status == 422) {
                            this.sendCodeDataErrors = error.response.data.errors;
                        }
                    });

                request.then(() => {
                    this.isLoading = false;
                });
            },
            back() {
                this.$emit('sent');
            }
        }
    }
</script>