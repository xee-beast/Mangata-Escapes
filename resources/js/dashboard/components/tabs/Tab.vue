<template>
<li :class="{ 'is-active': isActive }">
	<a @click.prevent="$emit('click')">
		<span v-if="icon" class="icon is-small"><i :class="icon"></i></span>
		<span class="is-capitalized">
			<slot />
		</span>
	</a>
</li>
</template>

<script>
export default {
	props: {
		icon: String,
		isActive: Boolean
	}
}
</script>
