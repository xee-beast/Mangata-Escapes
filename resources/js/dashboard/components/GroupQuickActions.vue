<template>
	<div>
		<ul class="group-quick-actions">
			<li>
				<a @click.prevent="showAttritionModal = true" title="View Attrition">
					<i class="fas fa-chart-line"></i>
				</a>
			</li>
			<li>
				<a @click.prevent="showDueDatesModal = true" title="View Due Dates">
					<i class="fas fa-calendar-alt"></i>
				</a>
			</li>
			<li>
				<a :href="`/groups/${currentGroupId}/accommodations`" target="_blank" title="View Accommodations">
					<i class="fas fa-door-open"></i>
				</a>
			</li>
			<li class="has-badge">
				<a :href="`/groups/${currentGroupId}/bookings`" target="_blank" title="View Bookings">
					<i class="fas fa-book-open"></i>
				</a>
			</li>
		</ul>
		<attrition-modal :is-active="showAttritionModal" :group-id="currentGroupId" @hide="showAttritionModal = false" />
		<due-dates-modal :is-active="showDueDatesModal" :group-id="currentGroupId" @hide="showDueDatesModal = false" />
	</div>
</template>

<script>
import AttritionModal from '@dashboard/components/AttritionModal';
import DueDatesModal from '@dashboard/components/DueDatesModal';

export default {
	components: { AttritionModal, DueDatesModal },
	data() {
		return {
			showAttritionModal: false,
			showDueDatesModal: false,
		}
	},
	computed: {
		currentGroupId() {
			return this.$route.params.group || this.$route.params.id;
		},
	},
}
</script>

<style lang="scss" scoped>
.group-quick-actions {
	display: flex;
	align-items: center;
	gap: 0.2rem;
  margin-top: 1.5rem;
	list-style: none;

	li {
		a {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 2rem;
			height: 2rem;
			color: #495057;
			cursor: pointer;
			text-decoration: none;
			position: relative;
			transition: all 0.2s;

			&:hover {
				color: #000;
				background-color: rgba(0, 0, 0, 0.05);
				border-radius: 4px;
			}

			i {
				font-size: 1rem;
			}
		}
	}
}
</style>
