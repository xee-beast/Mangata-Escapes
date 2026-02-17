<template>
	<modal @hide="close" title="Cancel Booking" :is-active="true">
		<p>
			Are you sure you want to cancel this booking?
			<br><br>
			This will affect the following guests:
		</p>
		<ul style="list-style: inside disc;">
			<template v-for="client in booking.clients">
				<li v-for="guest in client.guests">{{ guest.firstName }} {{ guest.lastName }}</li>
			</template>
		</ul>
		<div v-if="showProviderNotificationWarning" class="notification is-warning" style="margin-top: 1rem;">
      <p>Please make sure that the transfer provider <span v-html="transferProviderName"></span> has been notified about the cancellation.</p>
		</div>
		<template v-slot:footer>
			<div class="field is-grouped">
				<control-button @click="close" :disabled="isLoading">Cancel</control-button>
				<control-button @click="confirm" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Yes</control-button>
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
			}
		},
		data() {
			return {
				isLoading: false
			}
		},
		computed: {
			showProviderNotificationWarning() {
				const checkInDate = this.$moment(this.booking.checkIn);
				const daysUntilCheckIn = checkInDate.diff(this.$moment(), 'days');

				if (daysUntilCheckIn > 30 || daysUntilCheckIn < 0) {
					return false;
				}

        const hasGuestTransportation = this.booking.clients.some(client => client.guests.some(guest => guest.transportation && !guest.deleted_at));

        if (this.booking.transportation && hasGuestTransportation) {
          return true;
        }

        return false;
			},
			transferProviderName() {
        if (!this.booking.transfer) return '';

				return `<b style="text-transform: lowercase;">${this.booking.transfer?.name }</b>`;
			}
		},
		methods: {
			confirm() {
				this.isLoading = true;

				let request = this.$http.delete('/individual-bookings/' + this.booking.id)
					.then(response => {
						this.$emit('deleted', this.booking);

						this.$store.commit('notification', {
							type: 'success',
							message: 'The booking has been cancelled.'
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
