<template>
	<nav class="navbar is-fixed-top" ref="navbar">
		<div class="navbar-brand">
			<div class="brand-wrapper" style="display: flex; justify-content: center; align-items: center;">
				<a :href="appUrl" target="_blank" class="brand navbar-item">
					<img :src="mainLogo"  class="logo">
				</a>
			</div>
			<div v-if="showTitle" class="navbar-item card-header-title card-header-title-mobile">
				<a :href="couplesPage" target="_blank">
					{{ savedGroup.brideFirstName }} {{ savedGroup.brideLastName }} & {{ savedGroup.groomFirstName }} {{ savedGroup.groomLastName }}
				</a>
			</div>
			<a @click.prevent="toggleMenu" class="menu-toggle navbar-item">
				<menu-expand-icon v-if="!menuIsExpanded" class="menu-icon" title="Expand" />
				<menu-collapse-icon v-if="menuIsExpanded" class="menu-icon" title="Collapse" />
			</a>
		</div>
		<div class="navbar-menu">
			<div v-if="showTitle" class="navbar-start">
				<div class="navbar-item card-header-title card-header-title-desktop is-size-6 couple-info">
					<span>
						<a class="has-text-alabaster" style="display: inline !important;" :href="couplesPage" target="_blank">{{ savedGroup.brideFirstName }} {{ savedGroup.brideLastName }} & {{ savedGroup.groomFirstName }} {{ savedGroup.groomLastName }}</a> :
						<span v-if="savedGroup.fit">FIT -</span> {{ savedGroup.agent.firstName.toUpperCase() }} <template v-if="$moment().isAfter(savedGroup.cancellationDate)">{{ $moment().isAfter(savedGroup.dueDate) ? '- COG' : '- RRG' }}</template> - {{ savedGroup.provider.abbreviation }} {{ savedGroup.providerId }} - {{ savedGroup.destination.airportCode }} / <span v-if="savedGroup.hotels.length">
							<span v-for="(hotel, index) in savedGroup.hotels" :key="index">
								<template v-if="hotel.url">
									<a class="has-text-alabaster" :href="hotel.url" target="_blank">{{ hotel.name }}</a>
								</template>
								<template v-else>
									{{ hotel.name }}
								</template>
								<span v-if="index < savedGroup.hotels.length - 1"> & </span>
							</span>
						</span>
						<span v-else>
							Pending Hotel
						</span>
						- Wedding {{ $moment(savedGroup.eventDate).format('MM/DD/YYYY') }} - Balance {{ $moment(savedGroup.dueDate).format('MM/DD/YYYY') }} - Cancellation {{ $moment(savedGroup.cancellationDate).format('MM/DD/YYYY') }} <span v-if="savedGroup.dueDates.length > 0">- Other Due Dates {{ savedGroup.dueDates.map(dueDate => $moment(dueDate.date).format('MM/DD/YYYY')).join(', ') }}</span><i v-if="savedGroup.disableNotifications" class="fas fa-bell-slash ml-2"></i>
					</span>
				</div>
			</div>
			<div v-if="!showTitle" class="navbar-start">
				<div class="navbar-item">
					<a :href="appUrl" target="_blank">
						<img v-if="logo" :src="logo" alt="Logo" width="200" loading="lazy">
					</a>
				</div>
			</div>
			<div class="navbar-end search-section">
				<template>
					<form-field style="padding-top: 0px !important;">
						<control-input v-model="filters.search" class="is-small" @enter="filterData()" placeholder="Search Bookings" />
						<template v-slot:addon>
							<control-button @click="filterData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
						</template>
					</form-field>
					<form-field class="ml-2">
						<control-input v-model="filters.groupSearch" class="is-small" @enter="filterGroupData()" placeholder="Search Groups" />
						<template v-slot:addon>
							<control-button @click="filterGroupData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
						</template>
					</form-field>
					<!-- Search Leads removed - no CRM module in this cloned website
					<form-field class="ml-2">
						<control-input v-model="filters.newLeadsSearch" class="is-small" @enter="filterNewLeadsData()" placeholder="Search Leads" />
						<template v-slot:addon>
							<control-button @click="filterNewLeadsData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
						</template>
					</form-field>
				-->
				</template>
				<div class="navbar-item has-dropdown is-hoverable">
					<a class="navbar-link">{{ firstName }}</a>
					<div class="navbar-dropdown is-right">
						<a @click.pevent="$router.push({ name: 'account' })" class="navbar-item">Account</a>
						<form ref="logoutForm" :action="authUrl + '/logout'" method="POST">
							<input type="hidden" name="_token" :value="csrfToken">
							<a @click.prevent="logout" class="navbar-item">Logout</a>
						</form>
					</div>
				</div>
			</div>
		</div>
	</nav>
</template>

<script>
	import MenuCollapseIcon from 'vue-material-design-icons/Backburger';
	import MenuExpandIcon from 'vue-material-design-icons/Forwardburger';
	import DataFilters from '@dashboard/components/table/Filters';
	import DataFilter from '@dashboard/components/table/Filter';
	import FormField from '@dashboard/components/form/Field';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlButton from '@dashboard/components/form/controls/Button';

	export default {
		name: 'navbar',
		components: {
			MenuCollapseIcon,
			MenuExpandIcon,
			DataFilters,
			DataFilter,
			FormField,
			ControlInput,
			ControlButton,
		},
		data() {
			return {
				filters: {
					search: '',
          			groupSearch: '',
					newLeadsSearch: '',
				},
				showTitle: '',
				savedGroup: {},
				dueDates: [],
				resizeObserver: null,
			}
		},
		created() {
			this.filters = Object.assign({}, this.filters, this.$route.query);
			this.getTitle();
		},
		mounted() {
			this.updateNavbarHeight();
			this.resizeObserver = new ResizeObserver(this.updateNavbarHeight);
			this.resizeObserver.observe(this.$refs.navbar);
		},
		beforeDestroy() {
			if (this.resizeObserver) {
				this.resizeObserver.disconnect();
			}
		},
		computed: {
			appUrl() {
				return process.env.MIX_APP_URL;
			},
			authUrl() {
				return process.env.MIX_AUTH_URL;
			},
			csrfToken() {
				return this.$store.state.csrfToken;
			},
			firstName() {
				return this.$store.state.user.firstName;
			},
			logo() {
				return this.$store.state.dashboard.logo;
			},
      mainLogo() {
				return this.$store.state.dashboard.mainLogo;
      },
			menuIsExpanded() {
				return this.$store.state.menuIsExpanded;
			},
			couplesPage() {
				return `${process.env.MIX_GROUP_URL}/${this.savedGroup.slug}`;
			}
		},
		watch: {
			'$route': function (to, from) {
				this.getTitle();
			}
		},
		methods: {
			updateNavbarHeight() {
				if (this.$refs.navbar) {
					const height = this.$refs.navbar.offsetHeight;
					document.documentElement.style.setProperty('--navbar-height', `${height}px`);
				}
			},
			logout() {
				this.$refs.logoutForm.submit();
			},
			toggleMenu() {
				this.$store.commit('expandMenu', !this.menuIsExpanded);
			},
			filterData() {
				window.location.href = '/results?search=' + this.filters.search;
			},
			filterGroupData() {
				if (!this.filters.groupSearch || !this.filters.groupSearch.trim()) {
					return;
				}

				const query = {
					search: this.filters.groupSearch,
					page: 1
				};

        this.$router.push({
          name: 'groups',
          query: query
        });
			},
			filterNewLeadsData() {
				if (!this.filters.newLeadsSearch || !this.filters.newLeadsSearch.trim()) {
					return;
				}

				const query = {
					search: this.filters.newLeadsSearch,
					page: 1
				};

				this.$router.push({
					name: 'leads',
					query: query
				});
			},
			getTitle() {
				this.showTitle = false;

				if (this.$route.path.includes('groups') && Object.keys(this.$route.params).length > 0) {
					this.showTitle = false;

					this.$http.get('/groups/' + (this.$route.name == 'groups.show' ? this.$route.params.id : this.$route.params.group))
						.then(response => {
							this.savedGroup = response.data.data;
							this.dueDates = {
								dueDate: this.$moment(this.savedGroup.dueDate).toDate(),
								cancellationDate: this.$moment(this.savedGroup.cancellationDate).toDate(),
								other: this.savedGroup.dueDates.map(dueDate => ({
									date: this.$moment(dueDate.date).toDate(),
									amount: dueDate.amount,
									type: dueDate.type
								}))
							};

							this.showTitle = true;
						}).catch(error => {
							if (error.response.status === 403) {
								this.$store.commit('error', {
									status: 403,
									message: error.response.statusText
								});
							}
						});
				}
			}
		}
	}
</script>

<style lang="scss">
  .navbar-menu {
    flex-shrink: unset;
  }
	.navbar {
    box-shadow: 0 0.75rem 0.5rem -0.5rem rgba(0, 0, 0, 0.075);

    .ml-2{
      margin-left: 1rem !important;
    }

    .navbar-brand {
			.brand {
				padding: 0.5rem;
				width: auto;
                min-width: $menu-width;
				transition: width 0.5s, padding 0.25s;
                display: flex;
                justify-content: center;
                align-items: center;

                @include mobile {
                    justify-content: flex-start;
                }

				&:hover {
					padding: 0.25rem;
				}

				.logo {
                    max-height: calc(var(--navbar-height) - 0.5rem);
                    width: auto;
                    object-fit: contain;
                    padding-top: 10px;
                    padding-bottom: 10px;

                    @include mobile {
                        max-height: 40px;
                        height: 40px;
                        width: 40px;
                        padding-top: 0;
                        padding-bottom: 0;
                    }
				}
			}

			.menu-toggle .menu-icon svg {
				vertical-align: middle;
			}

			@include mobile {
				justify-content: space-between;

			}
    }
    .navbar-dropdown {
        border: 1px solid $secondary;
        padding: 5px;
		border-radius: 5px !important;	
		margin-right: 10px !important;
		box-shadow: 0 0.75rem 0.5rem -0.5rem rgba(0, 0, 0, 0.075);

        .navbar-item {
            border-radius: 10px;
        }
    }

    .field {
        padding-top: 13px;
    }

    .search-section {
        .button {
            background-color: #995C64 !important;
            border-color: transparent !important;
            color: white !important;

            &:hover,
            &:focus,
            &:active {
                background-color: #995C64 !important;
                border-color: transparent !important;
                color: white !important;
                box-shadow: none !important;
            }

            i,
            .icon {
                color: white !important;
            }
        }
    }
	}

	.menu-is-expanded {
		.navbar .navbar-brand .brand.navbar-item {
			width: $menu-width-expanded;

			@include mobile {
				width: 150px;
			}
		}
	}
</style>
