<template>
    <div>
        <button @click="show = true" class="button is-medium is-fat is-rounded is-outlined is-black">GROUP LEADER</button>
        <modal :is-active="show" @hide="close">
            <div class="form-container">
                <div class="form-header" v-if="!detailsVisible">
                    <div class="form-title">{{ group.bride_first_name }} & {{ group.groom_first_name }} - Booking Details</div>
                    <button type="button" class="modal-close-booking" aria-label="close" @click="hideModal">
                        <span class="icon"><i class="fas fa-times"></i></span>
                    </button>
                </div>
                <div :class="{ 'form-content': !detailsVisible }">
                    <div v-if="!detailsVisible">
                        <div v-if="lostPassword">
                            <div v-if="!passwordSent">
                                <div class="field">
                                    <label class="label">Email</label>
                                    <p class="help for-label">Enter your email address and we will resend your password.</p>
                                    <div class="control">
                                        <input type="text" v-model="resendPassword.email" class="input is-warm-gray is-warm-gray-border" :class="{ 'is-danger': ('email' in resendPasswordErrors) }">
                                    </div>
                                    <p v-if="('email' in resendPasswordErrors)" class="help is-danger">{{ resendPasswordErrors['email'][0] }}</p>
                                </div>
                                <button @click="sendPassword" class="button is-outlined is-dark" :class="{ 'is-loading': isLoading }">Send</button>
                            </div>
                            <div v-else>
                                <div class="field">
                                    <p>Your password has been sent to <b>{{ resendPassword.email }}</b>.</p>
                                </div>
                                <button @click="back" class="button is-outlined is-dark">Back</button>
                            </div>
                        </div>
                        <div v-else>
                            <div class="field">
                                <label class="label">Email</label>
                                <div class="control">
                                    <input type="text" v-model="email" class="input is-warm-gray is-warm-gray-border" :class="{ 'is-danger': ('email' in formErrors)}">
                                </div>
                                <p v-if="('email' in formErrors)" class="help is-danger">{{ formErrors['email'][0] }}</p>
                            </div>
                            <div class="field">
                                <label class="label">Password <a @click.prevent="lostPassword = true" class="is-size-6 has-text-weight-light has-text-link is-pulled-right">Can't find your password?</a></label>
                                <div class="control">
                                    <input type="text" v-model="password" class="input is-warm-gray is-warm-gray-border" :class="{ 'is-danger': ('password' in formErrors)}">
                                </div>
                                <p v-if="('password' in formErrors)" class="help is-danger">{{ formErrors['password'][0] }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="booking-details-modal modal-booking-content">
                        <div class="modal-booking-header">
                            <img :src="logoUrl" alt="Barefoot Bridal" class="modal-booking-logo">
                        </div>
                        <div class="modal-booking-title">
                            <h2>{{ group.bride_first_name }} & {{ group.groom_first_name }} - Booking Details</h2>
                            <button type="button" class="modal-close-booking" aria-label="close" @click="hideModal">
                                <span class="icon"><i class="fas fa-times"></i></span>
                            </button>
                        </div>
                        <div class="columns is-2 is-multiline mb-4 nights-box">
                            <div class="column nights">
                                <div class="stat-card">
                                    <span class="heading is-size-6 has-text-weight-bold has-text-grey-dark stat-label">TOTAL NIGHTS:</span>
                                    <span class="title is-2 has-text-weight-bold has-text-black">{{ totalNights }}</span>
                                </div>
                            </div>
                            <div class="column booking">
                                <div class="stat-card">
                                    <span class="heading is-size-6 has-text-weight-bold has-text-grey-dark stat-label">TOTAL BOOKINGS:</span>
                                    <span class="title is-2 has-text-weight-bold has-text-black">{{ totalActiveBookings }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table-striped-custom">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Guests</th>
                                        <th>Accommodations & Dates</th>
                                        <th>Reservation</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="bookingsDetails.length === 0">
                                        <td colspan="5" class="text-center text-muted">No records found...</td>
                                    </tr>
                                    <tr v-else v-for="(booking, index) in bookingsDetails" :key="index">
                                        <td>{{ booking.order }}</td>
                                        <td>
                                            <div v-for="client in booking.clients" :key="client.id">
                                                <div v-for="guest in client.guests" :key="guest.id" :class="{ 'text-strikethrough': guest.deleted_at }" class="mb-2">
                                                    {{ guest.first_name }} {{ guest.last_name }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div v-for="(roomBlock, i) in booking.room_blocks" :key="i" class="mb-2">
                                                {{ roomBlock.room.name }} <br/>
                                                {{ $moment.utc(roomBlock.pivot.check_in).format('MMM DD') }} - {{ $moment.utc(roomBlock.pivot.check_out).format('MMM DD, YYYY') }}
                                            </div>
                                        </td>
                                        <td>
                                            <div v-for="client in booking.clients" :key="client.id">
                                               {{ client.reservation_code }}
                                            </div>
                                        </td>
                                        <td>
                                            <span v-if="booking.deleted_at" class="badge-cancelled">Cancelled</span>
                                            <span v-else-if="booking.confirmed_at && hasPendingChanges(booking)" class="badge-pending">Pending</span>
                                            <span v-else-if="booking.confirmed_at" class="badge-confirmed">Confirmed</span>
                                            <span v-else class="badge-pending-confirmation">Pending Confirmation</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div v-if="!lostPassword && !detailsVisible" class="form-footer">
                    <div class="columns is-mobile">
                        <div class="column"></div>
                        <div class="column is-narrow">
                            <button @click="viewDetails" class="button is-dark is-outlined" :class="{ 'is-loading': isLoading }">View Details</button>
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
        },
        data() {
            return {
                show: false,
                isLoading: false,
                email: null,
                password: null,
                formErrors: {},
                lostPassword: false,
                resendPassword: {
                    email: null,
                    group_id: this.group.id,
                },
                resendPasswordErrors: {},
                passwordSent: false,
                bookingsDetails: [],
                groupDetails: {},
                detailsVisible: false,
                totalActiveBookings: 0,
                totalNights: 0,
            }
        },
        computed: {
          logoUrl() {
              return (window.assetUrl || '') + 'img/logo-slogan.png';
          }
        },
        methods: {
            close() {
                if (this.lostPassword) {
                    Object.assign(this.$data, this.$options.data.apply(this));
                }

                this.show = false;
            },
            hideModal() {
                this.show = false;
                this.detailsVisible = false;
            },
            sendPassword() {
                this.isLoading = true;
                this.resendPasswordErrors = {};

                let request = this.$http.post(`/groups/${this.group.id}/resend-password`, this.resendPassword)
                    .then((response) => {
                        this.passwordSent = true;
                    })
                    .catch(error => {
                        if (error.response.status == 422) {
                            this.resendPasswordErrors = error.response.data.errors;
                        }
                    });

                request.then(() => {
                    this.isLoading = false;
                })
            },

            back() {
                this.lostPassword = false;
            },

            viewDetails() {
                this.formErrors = {};
                this.isLoading = true;

                let request = this.$http.post(`/groups/${this.group.id}/booking-details`, {
                        group_id: this.group.id,
                        email: this.email,
                        password: this.password,
                    })
                    .then(response => {
                        this.groupDetails = response.data.group;
                        this.bookingsDetails = response.data.bookings;
                        this.detailsVisible = true;
                        let activeBookings = this.bookingsDetails.filter(booking => !booking.deleted_at);
                        this.totalActiveBookings = activeBookings.length;
                        this.totalNights = activeBookings.reduce((total, booking) => {
                            return total + booking.room_blocks.reduce((roomTotal, roomBlock) => {
                                return roomTotal + this.$moment.utc(roomBlock.pivot.check_out).diff(this.$moment.utc(roomBlock.pivot.check_in), 'days');
                            }, 0);
                        }, 0);
                    })
                    .catch(error => {
                        if (error.response.status == 422) {
                            this.formErrors = error.response.data.errors;
                        }
                    });

                request.then(() => {
                    this.isLoading = false;
                });
            },

            hasPendingChanges(booking) {
                return booking.tracked_changes?.filter(change => change.confirmed_at === null).length > 0;
            },
        }
    }
</script>
