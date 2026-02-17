<template>
<textarea @input="$emit('input', $event.target.value)" class="textarea" :readonly="readonly" :data-v-model="modelPath">{{ value }}</textarea>
</template>

<script>
export default {
	props: {
		value: String,
		readonly: {
			type: Boolean,
			default: false
		},
		modelPath: {
			type: String,
			default: ''
		}
	}
}
</script>

<style lang="scss">
// @import "~bulma/sass/form/input-textarea";
</style>
