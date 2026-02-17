<template>
	<modal :is-active="isActive" title="Attrition" @hide="$emit('hide')">
		<div v-if="loading" class="has-text-centered">
			<p>Loading...</p>
		</div>
		<div v-else-if="group && group.id">
			<div v-if="group.attritionImage && group.attritionImage.storagePath" class="has-text-centered mb-4">
				<img :src="group.attritionImage.storagePath" class="image" style="max-width: 100%; height: auto;" />
			</div>
			<div v-else class="notification is-info is-light">
				<p>No attrition chart uploaded.</p>
			</div>
			<div v-if="group.groupAttritionDueDates && Array.isArray(group.groupAttritionDueDates) && group.groupAttritionDueDates.length > 0" class="mt-4">
				<table class="table is-fullwidth">
					<thead>
						<tr>
							<th>Attrition Due Dates</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(dueDate, index) in group.groupAttritionDueDates" :key="index">
							<td>{{ $moment(dueDate.date).format('MM/DD/YYYY') }}</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div v-else-if="group.groupAttritionDueDates !== undefined" class="notification is-info is-light mt-4">
				<p>No attrition due dates configured.</p>
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
	name: 'AttritionModal',
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
	},
}
</script>
