<template>
<div :class="{ 'menu-is-expanded': menuIsExpanded }">
	<div class="layout">
		<navbar />
		<nav-menu />
	</div>
	<div class="page">
		<template v-if="!error">
			<breadcrumbs />
            <staff-message />
			<router-view />
			<loader />
		</template>
		<error v-else v-bind="error" />
	</div>
	<notifications />
</div>
</template>

<script>
import Breadcrumbs from '@dashboard/components/Breadcrumbs';
import Error from '@dashboard/pages/Error';
import Loader from '@dashboard/components/Loader';
import Navbar from '@dashboard/components/layout/Navbar';
import NavMenu from '@dashboard/components/layout/NavMenu';
import Notifications from '@dashboard/components/notification/Notifications';
import StaffMessage from '@dashboard/components/StaffMessage';

export default {
	components: {
		Breadcrumbs,
		Error,
		Loader,
		Navbar,
		NavMenu,
		Notifications,
        StaffMessage
	},
	created() {
		this.$http.get('/dashboard')
			.then(response => {
				this.$store.commit('dashboard', response.data.dashboard);
				this.$store.commit('user', response.data.user);

				setInterval(() => {
					this.$http({
						baseURL: process.env.MIX_AUTH_URL,
						url: 'refresh-token'
					});
				}, 5400000);

			});
	},
	computed: {
		menuIsExpanded() {
			return this.$store.state.menuIsExpanded;
		},
		error() {
			return this.$store.state.error;
		}
	}
}
</script>

<style lang="scss">
.page {
    position: relative;
    margin-top: var(--navbar-height, 3.25rem);
    padding: 1rem;
    min-height: calc(100vh - var(--navbar-height, 3.25rem));
    height: calc(100vh - var(--navbar-height, 3.25rem));

    @include tablet {
        margin-left: $menu-width;
        transition: all 0.5s;
    }
}

.menu-is-expanded {
    .page {
        @include tablet {
            margin-left: $menu-width-expanded;
        }
    }
}
</style>
