<template>
	<card title="Bookings & Payments">
		<template v-if="bookings">
			<data-filters>
				<template v-slot:left>
					<data-filter v-if="meta.total > 10">
						<pagination-filter v-model="filters.paginate" :exclude-options="[100]" @input="filterData()" />
					</data-filter>
				</template>
				<data-filter>
					<form-field>
						<div class="control">
							<input v-model="filters.search" @input="debouncedSearch" type="text" class="input is-small" placeholder="Search groups or bookings..." />
						</div>
					</form-field>
				</data-filter>
				<data-filter>
					<form-field>
						<control-select v-model="filters.provider" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Suppliers' }, ...providers ]" default-value="" />
					</form-field>
				</data-filter>
				<data-filter>
					<form-field>
						<control-select v-model="filters.agent" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Agents' }, ...agents ]" default-value="" />
					</form-field>
				</data-filter>
				<data-filter>
					<form-field>
						<control-select v-model="filters.only" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Pending Bookings' }, { value: 'bookings', text: 'Only Pending Bookings' }, { value: 'changes', text: 'Only Bookings With Pending Admin Changes' }, { value: 'guestChanges', text: 'Only Bookings With Pending Guest Changes' }, { value: 'payments', text: 'Only Bookings With Pending Payments' } ]" default-value="" />
					</form-field>
				</data-filter>
			</data-filters>
      <data-filters>
        <data-filter>
          <form-field>
            <label class="checkbox">
              <input type="checkbox" v-model="filters.testGroups" true-value="true" false-value="false" @change="filterData()">
                Exclude Test Groups
            </label>
          </form-field>
        </data-filter>
        <data-filter>
          <form-field>
            <control-select v-model="filters.sort" @input="filterData()" class="is-small" :options="[ { value: 'activity', text: 'Sort by Recent Activity' }, { value: 'alphabetical', text: 'Sort Alphabetically' } ]" default-value="activity" />
          </form-field>
        </data-filter>
      </data-filters>
			<data-table class="is-size-6" table-class="is-fullwidth" :columns="tableColumns">
				<template v-if="bookings.length">
						<tr v-for="booking in bookings" :key="booking.id">
							<th>
								<template v-if="booking.group">
									{{ booking.group.brideLastName }} & {{ booking.group.groomLastName }}
								</template>
								<template v-else>
									{{ booking.reservationLeaderFirstName }} {{ booking.reservationLeaderLastName }}
								</template>
							</th>
							<th v-if="filters.only == 'bookings'">
								{{ booking.order }}
							</th>
							<td>
								<template v-if="booking.group">
									{{ booking.group.provider.abbreviation }} / {{ booking.group.providerId }}
								</template>
								<template v-else>
									{{ booking.provider ? booking.provider.abbreviation : '-' }} / {{ booking.providerId ? booking.providerId : '-' }}
								</template>
							</td>
							<td>
								<template v-if="booking.group">
									{{ $moment(booking.group.eventDate).format('MMMM Do, YYYY') }}
								</template>
								<template v-else>
									{{ $moment(booking.checkIn).format('MMMM Do, YYYY') }}
								</template>
							</td>
							<td>
								<template v-if="booking.group">
									{{ booking.group.agent.firstName }} {{ booking.group.agent.lastName }}
								</template>
								<template v-else>
									{{ booking.agent ? booking.agent.firstName + ' ' + booking.agent.lastName : '-' }}
								</template>
							</td>
							<td>
								{{ getBookingStatus(booking) }}
								<br>
								<span v-if="booking.pendingPayments">{{ `${booking.pendingPayments} Pending Payments.` }}</span>
								<br>
								<a v-if="booking.pendingChanges" @click.prevent="addShowConfirmChanges(booking.id)" class="has-text-info-mauve">Room #{{ booking.order }} has admin changes since {{ booking.pendingChanges.since }}</a>
								<br v-if="booking.pendingChanges && hasPendingGuestChanges(booking)">
								<confirm-changes v-if="showConfirmChanges.includes(booking.id)" :booking="booking" :group="booking.group" @resolvedChanges="resolvedChanges" @canceled="removeShowConfirmChanges(booking.id)" />
								<template v-if="hasPendingGuestChanges(booking)">
									<template v-for="(guestChange, index) in getGuestChangesForBooking(booking)">
										<a @click.prevent="addShowGuestChanges(booking.id, guestChange.id)" class="has-text-info-mauve">
											Room #{{ booking.order }} has guest changes since {{ guestChange.since }}
										</a>
										<br v-if="index < getGuestChangesForBooking(booking).length - 1">
										<guest-changes-modal v-if="isGuestChangeModalOpen(booking.id, guestChange.id)" :booking="booking" :group="booking.group" :guest-change-id="guestChange.id" @resolvedChanges="resolvedGuestChanges" @canceled="removeShowGuestChanges" />
									</template>
								</template>
							</td>
							<td>
								{{ booking.notes }}
							</td>
							<td>
								{{ booking.group ? (booking.group.fit ? 'Group FIT' : '') : 'Individual FIT' }}
							</td>
							<td>
								<template v-if="booking.group">
									<a v-if="booking.can.view || booking.can.update" class="table-action" @click.prevent="show(booking.group.id, booking.id)">
										<i class="fas fa-info-circle"></i>
									</a>
									<a v-if="booking.can.view || booking.can.update" class="table-action" :href="`/groups/${booking.group.id}/bookings/${booking.id}`" target="_blank">
										<i class="fas fa-external-link-alt"></i>
									</a>
								</template>
								<template v-else>
									<a v-if="booking.can.view || booking.can.update" class="table-action" @click.prevent="show(null, booking.id)">
										<i class="fas fa-info-circle"></i>
									</a>
									<a v-if="booking.can.view || booking.can.update" class="table-action" :href="`/individual-bookings/${booking.id}`" target="_blank">
										<i class="fas fa-external-link-alt"></i>
									</a>
								</template>
							</td>
						</tr>
					</template>
					<tr v-else>
						<td>No records found...</td>
					</tr>
			</data-table>
			<paginator v-if="meta.total > 10" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from" :to="meta.to" :total="meta.total" />
		</template>
	</card>
</template>
<script>
	import Card from '@dashboard/components/Card';
	import ConfirmChanges from '@dashboard/pages/Groups/Bookings/ConfirmChanges';
	import GuestChangesModal from '@dashboard/pages/Groups/Bookings/GuestChangesModal';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import DataFilter from '@dashboard/components/table/Filter';
	import DataFilters from '@dashboard/components/table/Filters';
	import DataTable from '@dashboard/components/table/Table';
	import FormField from '@dashboard/components/form/Field';
	import PaginationFilter from '@dashboard/components/pagination/Filter';
	import Paginator from '@dashboard/components/pagination/Paginator';

	export default {
		components: {
			Card,
			ConfirmChanges,
			GuestChangesModal,
			ControlSelect,
			DataFilter,
			DataFilters,
			DataTable,
			FormField,
			PaginationFilter,
			Paginator,
		},

		data() {
			return {
				bookings: [],
				meta: {},
				providers: [],
				agents: [],
				filters: {
					paginate: 10,
					page: 1,
					search: '',
					provider: '',
					agent: '',
					only: '',
					sort: 'activity',
          testGroups: 'true'
				},
				showConfirmChanges: [],
				showGuestChanges: []
			}
		},

		created() {
			this.filters = Object.assign({}, this.filters, this.$route.query);
			this.fetchData();
		},

		computed: {
			query() {
				return {
					...this.filters
				}
			},

			tableColumns() {
				const columns = ['Group / Reservation Leader', 'Supplier / ID', 'Date', 'Travel Agent', 'Status', 'Notes', 'Type', 'Actions'];

				if (this.filters.only === 'bookings') {
					columns.splice(1, 0, 'Room #');
				}

				return columns;
			}
		},

		methods: {
			fetchData() {
				this.$http.get('/pending', {
						params: this.query
					})
					.then(response => {
						this.bookings = response.data.data;
						this.meta = response.data.meta;
						this.providers = response.data.providers;
						this.agents = response.data.agents;


						this.setBreadcrumbs();
					});
			},

			filterData(page = '1') {
				this.$set(this.filters, 'page', page);

				if (JSON.stringify(this.$route.query) !== JSON.stringify(this.filters)) {
					this.$router.replace({
						query: this.filters
					});

					this.fetchData();
				}
			},

			setBreadcrumbs() {
				this.$store.commit('breadcrumbs', [
					{
						label: 'Dashboard',
						route: 'home'
					},
					{
						label: 'To Do',
						route: 'pending'
					}
				]);
			},

			getBookingStatus(booking) {
				let status = '';

				if (booking.deletedAt) {
					status = status + `Cancelled ${this.$moment(booking.deletedAt).calendar().toLowerCase()}.`;
				} else if (booking.confirmedAt) {
					status = status + `Confirmed ${this.$moment(booking.confirmedAt).calendar().toLowerCase()}.`;
				} else {
					status = status + `Pending since ${this.$moment(booking.createdAt).calendar().toLowerCase()}.`;
				}

				if ((booking.group && booking.group.fit) || !booking.group) {
					booking.clients.forEach((client) => {
						if (client.acceptedFitQuote) {
							status = status + ` ${client.firstName} ${client.lastName} has accepted their FIT quote.`;
						} else if (client.pendingFitQuote) {
							status = status + ` ${client.firstName} ${client.lastName}'s FIT quote is pending.`;
						} else if (client.discardedFitQuote) {
							if (client.discardedFitQuote.isCancelled) {
								status = status + ` ${client.firstName} ${client.lastName}'s FIT quote was cancelled.`;
							} else {
								status = status + ` ${client.firstName} ${client.lastName}'s FIT quote has expired.`;
							}
						} else {
							status = status + ` ${client.firstName} ${client.lastName} has not received an FIT quote yet.`;
						}
					});	
				}

				return status;
			},

			show(group, id) {
				if (group) {
					this.$router.push({
						name: 'bookings.show',
						params: {
							group: group,
							id: id
						}
					});
				} else {
					this.$router.push({
						name: 'individual-bookings.show',
						params: {
							id: id
						}
					});
				}
			},

			addShowConfirmChanges(id) {
				this.showConfirmChanges.push(id);
			},

			removeShowConfirmChanges(id) {
				this.showConfirmChanges.splice(this.showConfirmChanges.indexOf(id), 1);
			},

			addShowGuestChanges(bookingId, guestChangeId) {
				this.showGuestChanges.push({ bookingId, guestChangeId });
			},

			removeShowGuestChanges(bookingId, guestChangeId) {
				this.showGuestChanges = this.showGuestChanges.filter(item =>
					!(item.bookingId === bookingId && item.guestChangeId === guestChangeId)
				);
			},

			resolvedChanges(id) {
				this.removeShowConfirmChanges(id);
				this.fetchData();
			},

			resolvedGuestChanges(bookingId, guestChangeId) {
				this.removeShowGuestChanges(bookingId, guestChangeId);
				this.fetchData();
			},

			hasPendingGuestChanges(booking) {
				return booking.guestChanges && Array.isArray(booking.guestChanges) && booking.guestChanges.length > 0;
			},

			getGuestChangesForBooking(booking) {
				if (!booking.guestChanges || !Array.isArray(booking.guestChanges)) return [];
				return booking.guestChanges;
			},

			getGuestChangesDate(booking) {
				const guestChanges = this.getGuestChangesForBooking(booking);
				return guestChanges.length > 0 ? guestChanges[0].since : '';
			},

			getGuestChangesCount(booking) {
				return this.getGuestChangesForBooking(booking).length;
			},

			isGuestChangeModalOpen(bookingId, guestChangeId) {
				return this.showGuestChanges.some(item => item.bookingId === bookingId && item.guestChangeId === guestChangeId);
			},

			debouncedSearch() {
				if (this.searchTimeout) {
					clearTimeout(this.searchTimeout);
				}
				this.searchTimeout = setTimeout(() => {
					this.filterData();
				}, 300);
			}
		},

		beforeDestroy() {
			if (this.searchTimeout) {
				clearTimeout(this.searchTimeout);
			}
		}
	}
</script>
