<template>
<modal @hide="close" title="Delete Event Type" :is-active="true">
	<p>
		Are you sure you want to delete this event type, <span class="has-text-weight-semibold">{{ event.name }}</span>?
		Any custom events using this type will also be deleted!
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
		event: {
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
			let request = this.$http.delete('/calendar-events/' + this.event.id)
				.then(response => {
					this.$emit('deleted');
					this.$store.commit('notification', {
						type: 'success',
						message: this.event.name + ' has been deleted.'
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
