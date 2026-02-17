<template>
	<modal @hide="close" title="Guest Changes" :is-active="true">
		<table class="table is-fullwidth is-size-6">
			<thead>
				<tr>
					<th width="30%"></th>
					<th width="35%">Before</th>
					<th width="35%">After</th>
				</tr>
			</thead>
			<guest-change v-for="(change, key) in changes" :key="key" :change="change" :index="key" />
    </table>
		<template v-slot:footer>
			<div class="field is-grouped">
				<control-button @click="close" :disabled="isLoading">Close</control-button>
				<control-button @click="revert" type="submit" class="is-inverted is-primary" :class="{ 'is-loading': isLoading }">Cancel</control-button>
				<control-button @click="confirm" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Confirm</control-button>
			</div>
		</template>
	</modal>
</template>

<script>
    import GuestChange from '@dashboard/pages/Groups/Bookings/GuestChange';
    import ControlButton from '@dashboard/components/form/controls/Button';
    import Modal from '@dashboard/components/Modal';

	export default {
		components: {
			GuestChange,
			ControlButton,
			Modal,
		},
		props: {
			booking: {
				type: Object,
				required: true
			},
        group: {
                type: Object,
                required: true
            },
        guestChangeId: {
                type: [String, Number],
                default: null
            }
	    },
		data() {
			return {
				changes: [],
				isLoading: false,
			}
		},
		created() {
			this.fetchData();
		},
		methods: {
			fetchData() {
				const url = this.guestChangeId
					? `/groups/${this.group.id}/bookings/${this.booking.id}/guest-changes/${this.guestChangeId}`
					: `/groups/${this.group.id}/bookings/${this.booking.id}/guest-changes`;

				this.$http.get(url)
					.then(response => {
						this.changes = response.data;
					});
			},
			revert() {
				this.isLoading = true;

				const url = this.guestChangeId
					? `/groups/${this.group.id}/bookings/${this.booking.id}/revert-guest-changes/${this.guestChangeId}`
					: `/groups/${this.group.id}/bookings/${this.booking.id}/revert-guest-changes`;

				let request = this.$http.patch(url)
					.then(() => {
						this.$emit('resolvedChanges', this.booking.id, this.guestChangeId);

						this.$store.commit('notification', {
							type: 'success',
							message: 'The guest changes have been reverted.'
						});
					}).catch((error) => {
					});

				request.then(() => {
					this.isLoading = false;
				});
			},
			confirm() {
				this.isLoading = true;

				const url = this.guestChangeId
					? `/groups/${this.group.id}/bookings/${this.booking.id}/confirm-guest-changes/${this.guestChangeId}`
					: `/groups/${this.group.id}/bookings/${this.booking.id}/confirm-guest-changes`;

				let request = this.$http.patch(url)
					.then(() => {
						this.$emit('resolvedChanges', this.booking.id, this.guestChangeId);

						this.$store.commit('notification', {
							type: 'success',
							message: 'The guest changes have been confirmed.'
						});
					  
            window.open(`/groups/${this.group.id}/bookings/${this.booking.id}`, '_blank');
					});

				request.then(() => {
					this.isLoading = false;
				});
			},
			close() {
				this.$emit('canceled', this.booking.id, this.guestChangeId);
			},
		}
	}
</script>
