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
		<p>Please make sure that the transfer provider(s) <span v-html="transferProviderName"></span> has been notified about the cancellation.</p>
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
		},
		group: {
			type: Object,
			required: false
		}
	},
	data() {
		return {
			isLoading: false
		}
	},
	computed: {
		showProviderNotificationWarning() {
			const eventDate = this.$moment(this.group.eventDate);
			const daysUntilEvent = eventDate.diff(this.$moment(), 'days');

			if (daysUntilEvent > 30 || daysUntilEvent < 0) {
				return false;
			}

      const hasGuestTransportation = this.booking.clients.some(client => client.guests.some(guest => guest.transportation && !guest.deleted_at));

			if (this.group.transportation && hasGuestTransportation) {
				return true;
			}

			return false;
		},

		transferProviderName() {
      if (!this.group?.airports) return '';

      const providers = this.group.airports.filter(airport => airport.transferProvider)
        .map(airport => `<b style="text-transform: lowercase;">${airport.transferProvider.name}</b>`)
        .filter((name, index, self) => self.indexOf(name) === index);

      if (providers.length === 0) return '';

      const head = providers.slice(0, -1).join(', ');
      const last = providers[providers.length - 1];

      return head ? `${head} and ${last}` : last;
		}
	},
	methods: {
		confirm() {
			this.isLoading = true;

			let request = this.$http.delete('/groups/' + this.$route.params.group + '/bookings/' + this.booking.id)
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
