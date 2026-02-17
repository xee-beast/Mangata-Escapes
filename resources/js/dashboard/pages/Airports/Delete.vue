<template>
<modal @hide="close" title="Delete Airport" :is-active="true">
	<p>
		Are you sure you want to delete this airport, <span class="has-text-weight-semibold">{{ airport.airport_code }}</span>?
		This can create problems for destinations and groups that are using this airport!
	</p>
	<template v-slot:footer>
		<div class="field is-grouped">
			<control-button @click="close" :disabled="isLoading">Cancel</control-button>
			<control-button @click="confirm" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }" :disabled="isLoading">Yes</control-button>
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
		airport: {
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
			let request = this.$http.delete('/airports/' + this.airport.id)
				.then(response => {
					this.$emit('deleted');
					this.$store.commit('notification', {
						type: 'success',
						message: this.airport.airport_code + ' has been deleted.'
					});
				}).catch((error) => {
					this.isLoading = false;
				}).then(() => {
					this.isLoading = false;
				});
		},
		close() {
			this.$emit('canceled');
		}
	}
}
</script>
