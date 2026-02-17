<template>
	<modal @hide="close" title="Delete Payment" :is-active="true">
		<p>Are you sure you want to delete this payment. This action cannot be reversed.</p>
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
				isLoading: false
			}
		},
		methods: {
			confirm() {
				this.isLoading = true;

				let request = this.$http.delete(`/individual-bookings/${this.$route.params.id}/payments/${this.payment.id}/force`)
					.then(response => {
						this.$emit('forceDeleted');

						this.$store.commit('notification', {
							type: 'success',
							message: 'The payment has been deleted.'
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
