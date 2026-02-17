<template>
<aside v-if="modules" class="menu">
	<template v-if="modules.dashboard.length">
		<p class="menu-label">
			<span class="menu-label-icon"><i class="fas fa-tachometer-alt"></i></span>
			<span class="menu-label-text">Dashboard</span>
		</p>

		<ul class="menu-list">
			<li v-if="modules.dashboard.includes('home')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'home' }" exact>
					<span class="menu-item-icon"><i class="fas fa-home"></i></span>
					<span class="menu-item-text">Home</span>
				</router-link>
			</li>
		</ul>
	</template>

	<template v-if="modules.security.length">
		<p class="menu-label">
			<span class="menu-label-icon"><i class="fas fa-shield-alt"></i></span>
			<span class="menu-label-text">Security</span>
		</p>

		<ul class="menu-list">
			<li v-if="modules.security.includes('users')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'users' }">
					<span class="menu-item-icon"><i class="fas fa-users"></i></span>
					<span class="menu-item-text">Users</span>
				</router-link>
			</li>
			<li v-if="modules.security.includes('roles')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'roles' }">
					<span class="menu-item-icon"><i class="fas fa-user-shield"></i></span>
					<span class="menu-item-text">Roles</span>
				</router-link>
			</li>
		</ul>
	</template>

	<template v-if="modules.travel.length">
		<p class="menu-label">
			<span class="menu-label-icon"><i class="fas fa-globe-americas"></i></span>
			<span class="menu-label-text">Travel</span>
		</p>

		<ul class="menu-list">
			<li v-if="modules.travel.includes('transfers')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'transfers' }">
					<span class="menu-item-icon"><i class="fas fa-bus-alt"></i></span>
					<span class="menu-item-text">Transfers</span>
				</router-link>
			</li>
			<li v-if="modules.travel.includes('providers')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'providers' }">
					<span class="menu-item-icon"><i class="fas fa-archive"></i></span>
					<span class="menu-item-text">Suppliers</span>
				</router-link>
			</li>
			<li v-if="modules.travel.includes('agents')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'agents' }">
					<span class="menu-item-icon"><i class="fas fa-id-card-alt"></i></span>
					<span class="menu-item-text">Agents</span>
				</router-link>
			</li>
			<li v-if="modules.travel.includes('destinations')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'destinations' }">
					<span class="menu-item-icon"><i class="fas fa-map-marked-alt"></i></span>
					<span class="menu-item-text">Destinations</span>
				</router-link>
			</li>
			<li v-if="modules.travel.includes('hotels')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'hotels' }">
					<span class="menu-item-icon"><i class="fas fa-building"></i></span>
					<span class="menu-item-text">Hotels</span>
				</router-link>
			</li>
			<li v-if="modules.travel.includes('airports')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'airports' }">
					<span class="menu-item-icon"><i class="fas fa-plane-departure"></i></span>
					<span class="menu-item-text">Airports</span>
				</router-link>
			</li>
			<li v-if="modules.travel.includes('airlines')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'airlines' }">
					<span class="menu-item-icon"><i class="fas fa-plane"></i></span>
					<span class="menu-item-text">Airlines</span>
				</router-link>
			</li>
		</ul>
	</template>

	<!-- as per sharon we do not have a crm module in this cloned website-->
	<!-- <template v-if="modules.crm.length">
		<p class="menu-label">
			<span class="menu-label-icon"><i class="fas fa-chalkboard-teacher"></i></span>
			<span class="menu-label-text">CRM</span>
		</p>

		<ul class="menu-list">
			<li v-if="modules.crm.includes('leads')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'leads' }">
					<span class="menu-item-icon"><i class="fas fa-chart-line"></i></span>
					<span class="menu-item-text">Leads</span>
				</router-link>
			</li>
			<li v-if="modules.crm.includes('brands')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'brands' }">
					<span class="menu-item-icon"><i class="fas fa-tag"></i></span>
					<span class="menu-item-text">Brands</span>
				</router-link>
			</li>
		</ul>
	</template> -->
	
	<template v-if="modules.bookings.length">
		<p class="menu-label">
			<span class="menu-label-icon"><i class="fas fa-book-open"></i></span>
			<span class="menu-label-text">Bookings</span>
		</p>

		<ul class="menu-list">
			<li v-if="modules.bookings.includes('groups')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'groups' }">
					<span class="menu-item-icon"><i class="fas fa-handshake"></i></span>
					<span class="menu-item-text">Groups</span>
				</router-link>
			</li>
			<li v-if="modules.bookings.includes('individual-bookings')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'individual-bookings' }">
					<span class="menu-item-icon"><i class="fas fa-suitcase-rolling"></i></span>
					<span class="menu-item-text">Individual Bookings</span>
				</router-link>
			</li>
			<li v-if="modules.bookings.includes('deleted-groups')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'trash' }">
					<span class="menu-item-icon"><i class="fas fa-trash"></i></span>
					<span class="menu-item-text">Deleted Groups</span>
				</router-link>
			</li>
			<li v-if="modules.bookings.includes('unpaid-bookings')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'unpaid-bookings' }">
					<span class="menu-item-icon"><i class="fab fa-creative-commons-nc"></i></span>
					<span class="menu-item-text">Unpaid Bookings</span>
				</router-link>
			</li>
			<li v-if="modules.bookings.includes('pending')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'pending' }">
					<span class="menu-item-icon"><i class="fas fa-tasks"></i></span>
					<span class="menu-item-text">To Do</span>
				</router-link>
			</li>
			<li v-if="modules.bookings.includes('calendar')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'calendar' }">
					<span class="menu-item-icon"><i class="fas fa-calendar-alt	"></i></span>
					<span class="menu-item-text">Calendar</span>
				</router-link>
			</li>
			<li v-if="modules.bookings.includes('event-types')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'event-types' }">
					<span class="menu-item-icon"><i class="fas fa-palette"></i></span>
					<span class="menu-item-text">Event Types</span>
				</router-link>
			</li>
			<li v-if="modules.bookings.includes('faqs')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'faqs' }">
					<span class="menu-item-icon"><i class="fas fa-question"></i></span>
					<span class="menu-item-text">FAQs</span>
				</router-link>
			</li>
			<li v-if="modules.bookings.includes('notifications')" @click.prevent="closeMenu" class="menu-item">
				<router-link :to="{ name: 'notifications' }">
					<span class="menu-item-icon"><i class="fas fa-envelope"></i></span>
					<span class="menu-item-text">Notifications</span>
				</router-link>
			</li>
		</ul>
	</template>

	<ul class="profile menu-list">
		<li class="menu-item">
			<form ref="logoutForm" :action="authUrl + '/logout'" method="POST">
				<input type="hidden" name="_token" :value="csrfToken">
				<a @click.prevent="logout">
					<span class="menu-item-icon"><i class="fas fa-sign-out-alt"></i></span>
					<span class="menu-item-text is-capitalized">Logout</span>
				</a>
			</form>
		</li>
	</ul>
</aside>
</template>

<script>
export default {
	name: 'nav-menu',
	computed: {
		modules() {
			return this.$store.state.dashboard.modules;
		},
		currentPage() {
			return this.$route.path;
		},
		csrfToken() {
			return this.$store.state.csrfToken;
		},
		authUrl() {
			return process.env.MIX_AUTH_URL;
		}
	},
	methods: {
		closeMenu() {
			if (!(window.innerWidth > 768)) {
				this.$store.commit('expandMenu', false);
			}
		},
		logout() {
			this.$refs.logoutForm.submit();
		}
	}
}
</script>

<style lang="scss">
.menu {
    position: fixed;
    height: calc(100vh - var(--navbar-height, 3.25rem));
    background-color: $white;
    transition: all 0.5s;
    overflow: auto;

    .menu-label {
        padding: 0 0.5rem;

        @include tablet {
            display: none;
        }

				.menu-label-icon {
					color: $charcoal;
					font-size: 18px;
					font-weight: 700;
				}

				.menu-label-text {
					color: $charcoal;
					font-size: 16px;
					font-weight: 700;
				}
    }

    .menu-list {
        .menu-item {
            a {
                &.is-active {
                    &:hover {
                        color: $menu-item-active-hover-color;
                    }
                }

                @include tablet {
                    .menu-item-icon {
											  color: $charcoal;
                        width: 100%;
                        display: inline-flex;
                        justify-content: center;
                    }

                    .menu-item-text {
                        display: none;
												color: $charcoal;
                    }
                }
            }

        }
    }

    @include tablet {
        box-shadow: 0.75rem 0 0.5rem -0.5rem rgba(0, 0, 0, 0.075);
    }

    @include mobile {
        width: 100vw !important;
        margin-left: -100vw;
        z-index: 20;
    }

    .profile {
        position: static !important;
        bottom: 0;
        width: 100%;
        border-top: 0.125rem solid $secondary;
        display: none;

        a {
            background-color: $menu-item-active-background-color;
            color: $menu-item-active-hover-color;
        }

        @include mobile {
            display: block;
        }
    }
}

.menu-is-expanded {
    .menu {
        width: $menu-width-expanded;
        padding-top: 0.75rem;

        @include tablet {
            .menu-label {
                display: block;
            }

            .menu-list .menu-item a {
                .menu-item-icon {
                    display: inline;
                    width: auto;
                }

                .menu-item-text {
                    display: inline;
                }
            }
        }

        @include mobile {
            margin-left: 0;
        }
    }
}
</style>
