<template>
<modal @hide="close" title="Delete Accommodation" :is-active="true">
	<p>
		Are you sure you want to remove the <span class="has-text-weight-semibold">{{ accomodation.name }}</span> accommodation from this group?
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
		accomodation: {
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

			let request = this.$http.delete('/groups/' + this.$route.params.group + '/accomodations/' + this.accomodation.id)
				.then(response => {
					this.$emit('deleted', this.accomodation);
					this.$store.commit('notification', {
						type: 'success',
						message: this.accomodation.name + ' has been removed.'
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
