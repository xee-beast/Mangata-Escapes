<template>
<modal @hide="close" title="Confirm Payment" :is-active="true">
	<p>
		Are you sure you want to confirm a payment of ${{ payment.amount }} for {{ payment.card.name || (payment.bookingClient.firstName + ' ' + payment.bookingClient.lastName) }}?
        <br>
        <br>
	</p>
    <div v-if="payment.amount > 0" class="control">
        <label class="checkbox">
            <input type="checkbox" v-model="sendEmail">
            Send an email to {{ this.payment.bookingClient.client.email }} confirming this transaction.
        </label>
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
		payment: {
			type: Object,
			required: true
		}
	},
	data() {
		return {
            sendEmail: false,
			isLoading: false
		}
	},
	methods: {
		confirm() {
			this.isLoading = true;

			let request = this.$http.patch(
					`/groups/${this.$route.params.group}/bookings/${this.$route.params.booking}/payments/${this.payment.id}/confirm`,
                    { 'sendEmail': this.sendEmail }
                )
				.then(response => {
					this.$emit('confirmed', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: `A payment of ${this.payment.amount} has been confirmed for ${this.payment.card.name || (this.payment.bookingClient.firstName+" "+this.payment.bookingClient.lastName)}`
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
