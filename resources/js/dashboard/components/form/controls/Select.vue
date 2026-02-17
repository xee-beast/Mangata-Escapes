<template>
<div v-if="!readonly" class="select">
	<select v-model="selected" :class="controlClass" :disabled="disabled" @blur="$emit('blur')" @change="$emit('change')">
		<option v-if="typeof defaultValue == 'undefined' || isNullable" :disabled="!isNullable"></option>
		<option v-for="option in options" :value="option.value" :disabled="option.disabled" :selected="option.selected">
			{{ option.text }}
		</option>
	</select>
</div>
<input v-else type="text" :value="options.find(option => option.value === value).text" class="input is-static" readonly />
</template>

<script>
export default {
	props: {
		value: null,
		options: {
			type: Array,
			required: true
		},
		defaultValue: null,
		isNullable: {
			type: Boolean,
			default: false
		},
		readonly: {
			type: Boolean,
			default: false
		},
		disabled: {
			type: Boolean,
			default: false
		},
		controlClass: String
	},
	computed: {
		selected: {
			get() {
				return this.value != null ? this.value : this.defaultValue;
			},
			set(newValue) {
				this.$emit('input', newValue);
			}
		}
	}
}
</script>

<style lang="scss">
// @import "~bulma/sass/form/select";
</style>
