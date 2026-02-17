<template>
<div v-if="breadcrumbs.length" class="breadcrumb-wrapper">
	<group-quick-actions v-if="isInGroup" />
  <div v-else></div>
	<nav class="breadcrumb is-right">
		<ul>
			<li v-for="(breadcrumb, index) in breadcrumbs" :class="{ 'is-active': index == (breadcrumbs.length - 1) }">
				<router-link :to="{ name: breadcrumb.route, params: breadcrumb.params }">{{ breadcrumb.label }}</router-link>
			</li>
		</ul>
	</nav>
</div>
</template>

<script>
import GroupQuickActions from '@dashboard/components/GroupQuickActions';

export default {
	components: {
		GroupQuickActions,
	},
	computed: {
		breadcrumbs() {
			return this.$store.state.breadcrumbs;
		},
		isInGroup() {
			return this.$route.path.includes('/groups/') && (this.$route.params.id || this.$route.params.group);
		}
	}
}
</script>

<style lang="scss" scoped>
.breadcrumb-wrapper {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 1.5rem;
}
</style>
