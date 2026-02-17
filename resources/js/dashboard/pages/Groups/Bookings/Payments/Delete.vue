<template>
<modal @hide="close" title="Cancel Payment" :is-active="true">
	<form-field label="Was the card declined?" :errors="deletePaymentErrors.cardDeclined">
		<control-radio v-model="deletePayment.cardDeclined"  :options="[{text: 'Yes', value: true}, {text: 'No', value: false}]" />
	</form-field>
	<p>Note: By choosing yes, a card declined email will be sent to {{ payment.bookingClient.firstName }} {{ payment.bookingClient.lastName }} ({{ payment.bookingClient.client.email }}).</p>
	<template v-slot:footer>
		<div class="field is-grouped">
			<control-button @click="close" :disabled="isLoading">Cancel</control-button>
			<control-button @click="confirm" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Submit</control-button>
		</div>
	</template>
</modal>
</template>

<script>
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlRadio from '@dashboard/components/form/controls/Radio';
import FormField from '@dashboard/components/form/Field';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlRadio,
		FormField,
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
			deletePayment: {
				cardDeclined: false
			},
			deletePaymentErrors: {},
			isLoading: false
		}
	},
	methods: {
		confirm() {
			this.isLoading = true;

			let request = this.$http.delete(
					`/groups/${this.$route.params.group}/bookings/${this.$route.params.booking}/payments/${this.payment.id}`, {
						data: this.deletePayment
					})
				.then(response => {
					this.$emit('deleted', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The payment has been cancelled.'
					});
				})
				.catch(() => null);

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
