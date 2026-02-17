<template>
	<modal @hide="close" title="Confirm Booking" :is-active="true">
		<div style="margin-bottom: 10px" v-if="minimumDepositCheck">
			<p><b>Note: The total payments for this booking do not meet the minimum deposit required for all clients.</b></p>
		</div>
		<p>
			Before confirming make sure that:
		</p>
		<ul style="list-style: inside disc;">
			<template v-if="booking.provider">
				<li>The booking has been confirmed with {{ booking.provider.name }}.</li>
				<li>All the information matches the information at {{ booking.provider.name }}.</li>
			</template>
			<template v-else>
				<li>The booking has been confirmed with the provider.</li>
				<li>All the information matches the information at the provider.</li>
			</template>
		</ul>
		<br>
		<p>
			<label class="checkbox">
					<input type="checkbox" v-model="sendEmail">
					Send a confirmation email to the following clients:
			</label>
		</p>
		<ul style="list-style: inside disc;">
			<li v-for="client in booking.clients">{{ client.firstName }} {{ client.lastName }}: {{ client.client.email }}</li>
		</ul>
		<template v-slot:footer>
			<div class="field is-grouped">
				<control-button @click="close" :disabled="isLoading">Cancel</control-button>
				<control-button @click="confirm" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Confirm</control-button>
			</div>
		</template>
	</modal>
</template>

<script>
	import ControlButton from '@dashboard/components/form/controls/Button';
	import Modal from '@dashboard/components/Modal';

	export default {
		components: {
			ControlButton,
			Modal,
		},
		props: {
			booking: {
				type: Object,
				required: true
			},
		},
		data() {
			return {
				sendEmail: false,
				isLoading: false,
				minimumDepositCheck: false,
			}
		},
		mounted() {
			if (this.booking.totalPayments < this.booking.minimumDeposit) {
				this.minimumDepositCheck = true;
			}
		},
		methods: {
			confirm() {
				this.isLoading = true;

				let request = this.$http.patch('/individual-bookings/' + this.booking.id + '/confirm', {
						'sendEmail': this.sendEmail
					}).then(response => {
						this.$emit('confirmed', this.booking);

						this.$store.commit('notification', {
							type: 'success',
							message: 'The booking has been confirmed.'
						});
					});

				request.then(() => {
					this.isLoading = false;
				});
			},
			close() {
				this.$emit('canceled');
			}
		}
	}
</script>
