<template>
<div v-if="!readonly">
	<label v-for="option in options" class="radio">
		<input v-model="checked" :disabled="disabled" type="radio" :value="option.value" :checked="checked == option.value">
		{{ option.text }}
	</label>
</div>
<input v-else type="text" :value="options.find(option => option.value == value).text" class="input is-static" readonly />
</template>

<script>
export default {
	props: {
		value: null,
		readonly: {
			type: Boolean,
			default: false
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		options: {
			type: Array,
			required: true
		},
		default: null,
		controlClass: String
	},
	computed: {
		checked: {
			get() {
				return this.value != null ? this.value : this.default;
			},
			set(newValue) {
				this.$emit('input', newValue);
			}
		}
	}
}
</script>
