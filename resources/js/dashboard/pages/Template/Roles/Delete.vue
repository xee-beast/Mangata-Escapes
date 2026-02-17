<template>
<modal @hide="close" title="Delete Role" :is-active="true">
	<p>
		Are you sure you want to delete the <span class="has-text-weight-semibold">{{ role.name }}</span> role?
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
		role: {
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

			let request = this.$http.delete('/roles/' + this.role.id)
				.then(response => {
					this.$emit('deleted', this.role);
					this.$store.commit('notification', {
						type: 'success',
						message: this.role.name + ' has been deleted.'
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
