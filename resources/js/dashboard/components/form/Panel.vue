<template>
<div class="panel">
	<div class="panel-head columns is-vcentered is-mobile">
		<div class="column">
			<label v-if="label" class="label">{{ label }}</label>
		</div>
		<div class="panel-actions column is-narrow">
			<slot name="action" />
		</div>
	</div>
	<div class="panel-body">
		<slot />
	</div>
</div>
</template>

<script>
export default {
	props: {
		label: String
	}
}
</script>

<style lang="scss">
.panel {
    margin-bottom: 0.75rem;

    &:not(.is-borderless) {
        padding: 0.5rem;
        border: 1px solid $grey-lighter;
        border-radius: $radius;
    }

    .panel-head {
        margin-bottom: 0;
    }

    .panel-body {
        .columns {
            margin-bottom: 0;
        }
    }
}
</style>
