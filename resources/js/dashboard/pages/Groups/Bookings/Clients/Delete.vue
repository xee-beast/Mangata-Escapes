<template>
<modal @hide="close" title="Delete Client" :is-active="true">
	<p>
		Are you sure you want to remove <span class="has-text-weight-semibold">{{ client.firstName }} {{ client.lastName }}</span> from this booking?
	</p>
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
		client: {
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

			let request = this.$http.delete(`groups/${this.$route.params['group']}/bookings/${this.$route.params['booking']}/clients/${this.client.id}`)
				.then(response => {
					this.$emit('deleted', this.client);
					this.$store.commit('notification', {
						type: 'success',
						message: this.client.firstName + ' ' + this.client.lastName + ' has been deleted.'
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
