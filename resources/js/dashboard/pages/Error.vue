<template>
<div class="error">
	<div class="error-status">{{ status }}</div>
	<div class="error-message is-uppercase">{{ message }}</div>
	<router-link :to="{name: 'home'}" class="button is-link is-outlined is-uppercase">Go to the Home Page</router-link>
</div>
</template>

<script>
export default {
	props: {
		status: {
			type: [String, Number],
			default: 'Oops!'
		},
		message: {
			type: String,
			default: 'Something has gone wrong!'
		}
	},
	watch: {
		$route(to, from) {
			this.$store.commit('error', null);
		}
	}
}
</script>

<style lang="scss">
.error {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    height: 100%;
    width: 100%;
    align-items: center;
    justify-content: center;

    .error-status {
        font-size: 10rem;
        line-height: normal;
    }

    .error-message {
        padding: 0 4rem;
        margin-bottom: 0.5rem;
        border-bottom: 1px solid $secondary;
        font-size: 1.5rem;
        font-weight: bold;
    }
}
</style>
