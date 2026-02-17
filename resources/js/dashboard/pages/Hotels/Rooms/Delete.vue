<template>
<modal @hide="close" title="Delete Room" :is-active="true">
	<p>
		Are you sure you want to remove <span class="has-text-weight-semibold">{{ room.name }}</span> from {{ hotel.name }}?
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
		hotel: {
			type: Object,
			required: true
		},
		room: {
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

			let request = this.$http.delete('/hotels/' + this.hotel.id + '/rooms/' + this.room.id)
				.then(response => {
					this.$emit('deleted', this.room);
					this.$store.commit('notification', {
						type: 'success',
						message: this.room.name + ' has been deleted.'
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
