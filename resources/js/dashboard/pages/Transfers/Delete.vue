<template>
<modal @hide="close" title="Delete Transfer Provider" :is-active="true">
	<p>
		Are you sure you want to delete this transfer provider, <span class="has-text-weight-semibold">{{ transfer.name }}</span>? <span v-if="transfer.groupsCount > 0">This transfer provider is associated with {{ transfer.groupsCount }} group(s). Deleting it will remove those associations.</span>
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
		transfer: {
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
			let request = this.$http.delete('/transfers/' + this.transfer.id)
				.then(response => {
					this.$emit('deleted');
					this.$store.commit('notification', {
						type: 'success',
						message: this.transfer.name + ' has been deleted.'
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
