<template>
<div class="columns data-filters">
	<div class="column is-narrow">
		<div class="columns is-variable is-1 is-multiline">
			<slot name="left" />
		</div>
	</div>
	<div class="column">
		<div class="columns is-variable is-1 is-multiline">
			<div class="column is-hidden-mobile"></div>
			<slot />
		</div>
	</div>
</div>
</template>

<style lang="scss">
.data-filters {
    &:not(:last-child) {
        margin-bottom: 0;
    }

    > .column:last-child > .columns.is-multiline {
        justify-content: flex-end;
    }
}
</style>
