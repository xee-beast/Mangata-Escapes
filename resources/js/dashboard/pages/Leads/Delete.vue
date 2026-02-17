<template>
	<modal @hide="close" title="Remove Lead" :is-active="true">
		<p>
			Are you sure you want to remove <span class="has-text-weight-semibold">{{ lead.name }}</span> from your leads?
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
			lead: {
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

				let request = this.$http.delete('/leads/' + this.lead.id)
					.then(response => {
						this.$emit('deleted', this.lead);

						this.$store.commit('notification', {
							type: 'success',
							message: 'The lead has been deleted.'
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
