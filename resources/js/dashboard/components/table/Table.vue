<template>
<div class="table-container">
	<table class="table" :class="tableClass">
		<thead v-if="columns">
			<tr>
				<th v-for="column in columns">{{ column }}</th>
			</tr>
		</thead>
		<tbody>
			<slot />
		</tbody>
	</table>
</div>
</template>

<script>
export default {
	props: {
		columns: Array,
		tableClass: String
	}
}
</script>

<style lang="scss">
// @import "~bulma/sass/elements/table";

.table-container {
    padding-bottom: 3rem;
    margin-bottom: 0 !important;
}

.table-action {
    color: $charcoal !important;
    font-size: 1rem;
}
</style>
