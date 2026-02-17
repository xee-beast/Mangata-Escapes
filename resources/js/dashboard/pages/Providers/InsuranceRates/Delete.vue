<template>
<modal @hide="close" title="Delete Insurance Rates" :is-active="true">
	<p>
		Are you sure you want to delete <span class="has-text-weight-semibold">{{ insuranceRate.name }}</span>?
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
		insuranceRate: {
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

			let request = this.$http.delete('/providers/' + this.$route.params.provider + '/insurance-rates/' + this.insuranceRate.id)
				.then(response => {
					this.$emit('deleted', this.insuranceRate);
					this.$store.commit('notification', {
						type: 'success',
						message: this.insuranceRate.name + ' have been deleted.'
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
