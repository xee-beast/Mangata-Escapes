<template>
<input v-model="inputValue"  @blur="$emit('blur')" @keyup.enter="$emit('enter')" class="input" :class="{'is-static': readonly}" :readonly="readonly">
</template>

<script>
export default {
	props: {
		value: null,
		readonly: {
			type: Boolean,
			default: false
		}
	},
	computed: {
		inputValue: {
			get() {
				return this.value;
			},
			set(newValue) {
				this.$emit('input', newValue)
			}
		}
	}
}
</script>

<style lang="scss">
// @import "~bulma/sass/form/input-textarea";

.is-narrow {
    width: auto;
}
</style>
