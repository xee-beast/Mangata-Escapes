<template>
<modal @hide="close" title="Delete Agent" :is-active="true">
	<p>
		Are you sure you want to remove <span class="has-text-weight-semibold">{{ agent.firstName }} {{ agent.lastName }}</span> from your agents?
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
		agent: {
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

			let request = this.$http.delete('/agents/' + this.agent.id)
				.then(response => {
					this.$emit('deleted', this.agent);
					this.$store.commit('notification', {
						type: 'success',
						message: this.agent.firstName + ' ' + this.agent.lastName + ' has been removed from agents.'
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
