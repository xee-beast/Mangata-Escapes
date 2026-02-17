<template>
<control-select class="is-small" :value="value" @input="repaginate" :options="filteredOptions" />
</template>

<script>
import ControlSelect from '@dashboard/components/form/controls/Select';

export default {
	components: {
		ControlSelect
	},
	props: {
		value: {
			type: [String, Number],
			default: 10
		},
		excludeOptions: {
			type: Array,
			default: () => []
		}
	},
	data() {
		return {
			options: [{
					value: 10,
					text: 'Show 10 per page'
				},
				{
					value: 25,
					text: 'Show 25 per page'
				},
				{
					value: 50,
					text: 'Show 50 per page'
				},
				{
					value: 100,
					text: 'Show 100 per page'
				}
			]
		}
	},
	computed: {
		filteredOptions() {
			return this.options.filter(opt => !this.excludeOptions.includes(opt.value));
		}
	},
	methods: {
		repaginate(amount) {
			this.$emit('input', amount);
		}
	}
}
</script>
