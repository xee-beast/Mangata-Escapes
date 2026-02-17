<template>
<button @click.prevent="$emit('click')" class="button">
	<slot />
</button>
</template>

<style lang="scss">
// @import "~bulma/sass/elements/button";
</style>
