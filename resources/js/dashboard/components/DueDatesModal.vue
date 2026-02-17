<template>
	<modal :is-active="isActive" title="Due Dates" @hide="$emit('hide')">
		<div v-if="loading" class="has-text-centered">
			<p>Loading...</p>
		</div>
		<div v-else-if="group && group.id">
			<div class="columns">
				<div class="column">
					<div class="box">
						<strong class="title is-6">Balance Due Date</strong>
						<p class="is-size-5" v-if="group.dueDate">{{ $moment(group.dueDate).format('MM/DD/YYYY') }}</p>
						<p class="has-text-grey" v-else>—</p>
					</div>
				</div>
				<div class="column">
					<div class="box">
						<strong class="title is-6">Cancellation Date</strong>
						<p class="is-size-5" v-if="group.cancellationDate">{{ $moment(group.cancellationDate).format('MM/DD/YYYY') }}</p>
						<p class="has-text-grey" v-else>—</p>
					</div>
				</div>
			</div>
			<div v-if="group.dueDates && Array.isArray(group.dueDates) && group.dueDates.length > 0" class="mt-4">
				<strong class="title is-5">Other Due Dates</strong>
				<table class="table is-fullwidth is-striped">
					<thead>
						<tr>
							<th>Date</th>
							<th>Amount</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(dueDate, index) in group.dueDates" :key="index">
							<td>{{ $moment(dueDate.date).format('MM/DD/YYYY') }}</td>
							<td>
								<span v-if="dueDate.amount !== null && dueDate.amount !== undefined">{{ formatAmount(dueDate.amount, dueDate.type) }}</span>
								<span v-else class="has-text-grey">—</span>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div v-else-if="group.dueDates !== undefined" class="notification is-info is-light mt-4">
				<p>No other due dates configured.</p>
			</div>
		</div>
		<div v-else class="notification is-warning">
			<p>Unable to load group data.</p>
		</div>
	</modal>
</template>

<script>
import Modal from '@dashboard/components/Modal';

export default {
	name: 'DueDatesModal',
	components: {
		Modal,
	},
	props: {
		isActive: {
			type: Boolean,
			default: false,
		},
		groupId: {
			type: [Number, String],
			required: true,
		},
	},
	data() {
		return {
			group: null,
			loading: false,
		};
	},
	watch: {
		isActive(newVal) {
			if (newVal) {
				this.loadGroup();
			}
		},
	},
	methods: {
		loadGroup() {
			this.loading = true;
			this.$http.get('/groups/' + this.groupId)
				.then(response => {
					this.group = response.data.data;
					this.loading = false;
				})
				.catch(error => {
					console.error('Failed to load group:', error);
					this.loading = false;
				});
		},
		formatAmount(amount, type) {
			if (!amount) return '—';

			switch (type) {
				case 'price':
					return `$${parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
				case 'percentage':
					return `${amount}%`;
				case 'nights':
					return `${amount} nights`;
				default:
					return amount;
			}
		},
	},
}
</script>
