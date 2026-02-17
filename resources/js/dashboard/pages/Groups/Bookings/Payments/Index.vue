<template>
<card title="Payments" :booking-status="booking.deletedAt ? 'cancelled' : 'active'">
	<template v-slot:action>
		<create-payment v-if="can.create" :booking="booking" :countries="countries" @created="fetchData()"
			button-class="is-outlined is-primary is-inverted" />
	</template>

	<template v-if="payments">
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Amount', 'Status', 'Client', 'Payment', 'Notes', 'Actions']">
			<template v-if="payments.length">
				<tr v-for="payment in payments" :key="payment.id">
					<td :class="{ 'is-striked': payment.cancelledAt, 'has-text-danger': (!payment.confirmedAt && !payment.cancelledAt) }">{{ parseFloat(payment.amount).toFixed(2) }}</td>
					<td>
						{{ getPaymentStatus(payment) }}
					</td>
					<td>{{ payment.card.name || (payment.bookingClient.firstName + ' ' + payment.bookingClient.lastName) }}</td>
					<td>
						<span class="is-capitalized">{{ payment.card.type }}</span> ending in {{ payment.card.lastDigits }}
					</td>
					<td>{{ payment.notes }}</td>
					<td>
						<a v-if="payment.can.view" class="table-action" @click="sendOtp(payment)">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="payment.can.confirm" class="table-action" @click.prevent="showConfirm = payment">
							<i class="fas fa-check"></i>
						</a>
						<a v-if="payment.can.delete" class="table-action" @click.prevent="deletePayment = payment">
							<i class="fas fa-window-close"></i>
						</a>
						<a v-if="payment.can.forceDelete" class="table-action" @click.prevent="forceDeletePayment = payment">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
			<tr v-else>
				<td>No records found...</td>
			</tr>
		</data-table>

		<data-table class="is-size-6">
			<tr>
				<th>Total</th>
				<td>
					${{ total.toFixed(2) }}
				</td>
			</tr>
			<tr>
				<th>Payments</th>
				<td>
					${{ totalPayments.toFixed(2) }}
				</td>
			</tr>
			<tr>
				<th>Balance</th>
				<td>
					${{ balance.toFixed(2) }}
				</td>
			</tr>
		</data-table>

		<modal :is-active="showOtpModal" title="Verify OTP" @hide="closeOtpModal">
			<div class="form-container">
				<form @submit.prevent="verifyOtp">
					<div class="form-content">
						<div class="field">
							<label class="label">Enter the OTP sent to your email.</label>
							<div class="control">
								<input type="text" class="input" v-model="otp" placeholder="Enter OTP" />
							</div>
							<p v-if="otpError" class="help is-danger">{{ otpError }}</p>
						</div>
					</div>
					<div class="form-footer" style="margin-top: 10px;">
						<div class="columns is-mobile">
							<div class="column"></div>
							<div class="column is-narrow">
								<button type="submit" class="button is-primary">Verify</button>
								<button @click="closeOtpModal" type="button" class="button is-light">Cancel</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</modal>

		<show-payment v-if="showPayment" :payment="showPayment" :show-sensitive="can.processPayments" @updated="updated"
			@canceled="showPayment = null" />
		<confirm-payment v-if="showConfirm" :payment="showConfirm" @confirmed="confirmed" @canceled="showConfirm = null" />
		<delete-payment v-if="deletePayment" :payment="deletePayment" @deleted="deleted" @canceled="deletePayment = null" />
		<force-delete-payment v-if="forceDeletePayment" :payment="forceDeletePayment" @forceDeleted="forceDeleted" @canceled="forceDeletePayment = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ConfirmPayment from '@dashboard/pages/Groups/Bookings/Payments/Confirm';
import CreatePayment from '@dashboard/pages/Groups/Bookings/Payments/Create';
import DataTable from '@dashboard/components/table/Table';
import DeletePayment from '@dashboard/pages/Groups/Bookings/Payments/Delete';
import ForceDeletePayment from '@dashboard/pages/Groups/Bookings/Payments/ForceDelete';
import ShowPayment from '@dashboard/pages/Groups/Bookings/Payments/Show';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		Card,
		Modal,
		ConfirmPayment,
		CreatePayment,
		DataTable,
		DeletePayment,
		ForceDeletePayment,
		ShowPayment,
	},
	data() {
		return {
			payments: [],
			booking: {},
			group: {},
			countries: [],
			can: {},
			showPayment: null,
			showConfirm: null,
			deletePayment: null,
			forceDeletePayment: null,
			payment: null,
			showOtpModal: false,
			otp: null,
			otpError: null,
		}
	},
	created() {
		this.fetchData();
	},
	computed: {
		total() {
			return parseFloat(this.booking.total);
		},
		totalPayments() {
			return this.payments.reduce((total, payment) => {
				return total + (payment.confirmedAt ? parseFloat(payment.amount) : 0);
			}, 0);
		},
		balance() {
			return this.total - this.totalPayments;
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/groups/' + this.$route.params.group + '/bookings/' + this.$route.params.booking + '/payments')
				.then(response => {
					this.payments = response.data.data;
					this.booking = response.data.booking;
					this.group = response.data.group;
					this.countries = response.data.countries;
					this.can = response.data.can;

					this.setBreadcrumbs();
				});
		},
		sendOtp(payment) {
			this.$http.get('/otp/send')
				.then(response => {
					if (response.data.success) {
						this.payment = payment;
						this.showOtpModal = true;
						this.otpError = null;
					} else {
						this.$store.commit('notification', {
							type: 'danger',
							message: 'Failed to send OTP.'
						});
					}
				})
				.catch(error => {
					this.$store.commit('notification', {
						type: 'danger',
						message: 'Failed to send OTP.'
					});
				});
		},
		verifyOtp() {
			this.otpError = null;

			this.$http.post('/otp/verify', {otp: this.otp})
				.then(response => {
					if (response.data.success) {
						this.showPayment = this.payment;
						this.closeOtpModal();
					} else {
						this.otpError = response.data.message || 'Invalid OTP.';
					}
				})
				.catch(error => {
					this.otpError = error.response.data.errors.otp[0];
				});
		},
		closeOtpModal() {
      this.showOtpModal = false;
      this.otp = null;
			this.payment = null;
			this.otpError = null;
    },
		setBreadcrumbs() {
			this.$store.commit('breadcrumbs', [{
					label: 'Dashboard',
					route: 'home'
				},
				{
					label: 'Groups',
					route: 'groups'
				},
				{
					label: `${ this.group.brideLastName } & ${ this.group.groomLastName }`,
					route: 'groups.show',
					params: {
						id: this.$route.params.group
					}
				},
				{
					label: 'Bookings',
					route: 'bookings'
				},
				{
					label: `#${ this.booking.order }`,
					route: 'bookings.show',
					params: {
						group: this.$route.params.group,
						id: this.$route.params.booking
					}
				},
				{
					label: 'Payments',
					route: 'payments'
				}
			]);
		},
		updated(updatedPayment) {
			this.showPayment = null;
			this.refreshPayments(updatedPayment);
		},
		confirmed(confirmedPayment) {
			this.showConfirm = null;
			this.refreshPayments(confirmedPayment);
		},
		deleted(deletedPayment) {
			this.deletePayment = null;
			this.refreshPayments(deletedPayment);
		},
		forceDeleted() {
			this.forceDeletePayment = null;
			this.fetchData();
		},
		refreshPayments(newPayment) {
			const paymentIndex = this.payments.findIndex(payment => payment.id == newPayment.id);
			this.payments.splice(paymentIndex, 1, {
				...this.payments[paymentIndex],
				...newPayment
			});
		},
		getPaymentStatus(payment) {
			if (payment.confirmedAt) {
				return `Confirmed ${this.$moment(payment.confirmedAt).calendar().toLowerCase()}.`;
			} else if (payment.cancelledAt) {
				return `${payment.cardDeclined ? 'Declined' : 'Cancelled'} ${this.$moment(payment.cancelledAt).calendar().toLowerCase()}.`;
			} else {
				return `Pending since ${this.$moment(payment.createdAt).calendar().toLowerCase()}.`
			}
		}
	}
}
</script>
