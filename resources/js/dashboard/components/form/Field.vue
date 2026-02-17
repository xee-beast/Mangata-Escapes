<template>
<div class="field">
	<label v-if="label" class="label" :class="{ 'is-required': required }">{{ label }}</label>
	<div class="field-body">
		<div class="field" :class="{'has-addons': $slots.addon}">
			<div class="control">
				<slot />
			</div>
			<div v-if="$slots.addon" class="control">
				<slot name="addon" />
			</div>
		</div>
	</div>
	<p v-if="errors.length" class="help is-danger">{{ errors[0] }}</p>
</div>
</template>

<script>
export default {
	props: {
		label: String,
		errors: {
			type: Array,
			default: () => []
		},
		required: {
			type: Boolean,
			default: false
		}
	}
}
</script>

<style lang="scss">
// @import "~bulma/sass/form/tools";

// .field .control [disabled] {
//     cursor: auto;
// }
.field {
    .label {
        min-height: 24px;
    }
}
</style>
