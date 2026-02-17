<template>
<card title="Accommodations">
	<template v-slot:action>
		<create-accomodation v-if="can.create && !group.deletedAt" @created="fetchData()" :group="group" :hotels="hotels"
			button-class="is-outlined is-primary is-inverted" />

		<template v-if="!!group && !!group.can && group.can.delete && group.deletedAt">
			<a @click.prevent="showRestore = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-trash-restore"></i></span>
				<span>Restore Group</span>
			</a>
			<restore-group v-if="showRestore" :group="group" @restored="restored" @canceled="showRestore = false" />
		</template>			
	</template>

	<template v-if="accomodations">
		<data-table class="is-size-6" table-class="is-fullwidth is-bordered" :columns="tableColumns">
			<template v-for="hotel in accomodations">
                <template v-if="hotel.rooms.length">
                    <tr :key="`hotel${hotel.id}`">
                        <th v-if="group.fit" colspan="1" class="has-background-primary has-text-white">{{ hotel.name }}</th>
                        <th v-else colspan="5" class="has-background-primary has-text-white">{{ hotel.name }}</th>
                        <th v-if="hotel.rooms" colspan="1" class="has-background-primary has-text-white">
                            <a v-if="hotel.rooms.every((r)=> !r.booked ) && !group.deletedAt" class="table-action" @click.prevent="hotelToggleActive(hotel)">
                                <i :class="['fas', isHotelActive(hotel) ? 'fa-check-circle' : 'fa-window-close']"></i>
                            </a>
                        </th>
                    </tr>
                    <template v-for="room in hotel.rooms">
                        <template v-if="room.splitDate">
                            <tr :key="`room${room.id}`">
                                <th rowspan="2">{{ room.name }}</th>
                                <td rowspan="2">{{ room.inventory || '&infin;' }}</td>
                                <td rowspan="2">{{ room.inventory ? (room.inventory - room.booked) : '&infin;' }}{{ room.soldOut ? ' (sold out)' : '' }}</td>
                                <td>{{ $moment(room.startDate).format('MMMM DD') }} - {{ $moment(room.splitDate).format('MMMM DD') }}</td>
                                <td>
                                    <ul style="list-style: disc outside; margin-left: 0.5rem;">
                                        <li v-for="(rate, rateIndex) in room.rates" :key="`room${room.id}rate${rateIndex}`">
                                            {{ pax[rate.occupancy] }}: ${{ rate.rate }}
                                        </li>
                                        <li v-for="(childRate, childRateIndex) in room.childRates" :key="`room${room.id}crate${childRateIndex}`">
                                            Children {{ childRate.from ? ('from ' + childRate.from + ' to ') : 'up to ' }}{{ childRate.to }}:
                                            {{ childRate.rate != 0 ? "$" + childRate.rate : 'Free' }}
                                        </li>
                                    </ul>
                                </td>
                                <td rowspan="2">
                                    <a v-if="!group.deletedAt" class="table-action" @click.prevent="show(room.id)" title="View Details">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                    <a v-if="!room.booked && !group.deletedAt" class="table-action" @click.prevent="toggleVisibility(room)" :title="room.isVisible ? 'Hide from couples' : 'Show to couples'">
                                        <i :class="['fas', room.isVisible ? 'fa-eye' : 'fa-eye-slash']"></i>
                                    </a>
                                    <a v-if="!room.booked && !group.deletedAt" class="table-action" @click.prevent="roomToggleActive(room)" :title="room.is_active ? 'Deactivate' : 'Activate'">
                                        <i :class="['fas', room.is_active ? 'fa-check-circle' : 'fa-window-close']"></i>
                                    </a>
                                    <a v-if="!room.booked && !room.hasBooking && !group.deletedAt" class="table-action" @click.prevent="deleteAccomodation = room" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr :key="`splitRoom${room.id}`">
                                <td>{{ $moment(room.splitDate).format('MMMM DD') }} - {{ $moment(room.endDate).format('MMMM DD') }}</td>
                                <td>
                                    <ul style="list-style: disc outside; margin-left: 0.5rem;">
                                        <li v-for="(rate, rateIndex) in room.rates" :key="`sroom${room.id}rate${rateIndex}`">
                                            {{ pax[rate.occupancy] }}: ${{ rate.splitRate }}
                                        </li>
                                        <li v-for="(childRate, childRateIndex) in room.childRates" :key="`sroom${room.id}crate${childRateIndex}`">
                                            Children {{ childRate.from ? ('from ' + childRate.from + ' to ') : 'up to ' }}{{ childRate.to }}:
                                            {{ childRate.splitRate != 0 ? "$" + childRate.splitRate : 'Free' }}
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr :key="`room${room.id}`">
                                <th>{{ room.name }}</th>
                                <td v-if="!group.fit">{{ room.inventory || '&infin;' }}</td>
                                <td v-if="!group.fit">{{ room.inventory ? (room.inventory - room.booked) : '&infin;' }}{{ room.soldOut ? ' (sold out)' : '' }}</td>
                                <td v-if="!group.fit">{{ room.startDate ? $moment(room.startDate).format('MMMM DD') : '' }} - {{ room.endDate ? $moment(room.endDate).format('MMMM DD') : '' }}</td>
                                <td v-if="!group.fit">
                                    <ul style="list-style: disc outside; margin-left: 0.5rem;">
                                        <li v-for="(rate, rateIndex) in room.rates" :key="`room${room.id}rate${rateIndex}`">
                                            {{ pax[rate.occupancy] }}: ${{ rate.rate }}
                                        </li>
                                        <li v-for="(childRate, childRateIndex) in room.childRates" :key="`room${room.id}crate${childRateIndex}`">
                                            Children {{ childRate.from ? ('from ' + childRate.from + ' to ') : 'up to ' }}{{ childRate.to }}:
                                            {{ childRate.rate != 0 ? "$" + childRate.rate : 'Free' }}
                                        </li>
                                    </ul>
                                </td>
                                <td>
                                    <a v-if="!group.deletedAt && (!group.fit || (group.fit && !room.adultsOnly))" class="table-action" @click.prevent="show(room.id)" title="View Details">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                    <a v-if="!room.booked && !group.deletedAt" class="table-action" @click.prevent="toggleVisibility(room)" :title="room.isVisible ? 'Hide from couples' : 'Show to couples'">
                                        <i :class="['fas', room.isVisible ? 'fa-eye' : 'fa-eye-slash']"></i>
                                    </a>
                                    <a v-if="!room.booked && !group.deletedAt" class="table-action" @click.prevent="roomToggleActive(room)" :title="room.is_active ? 'Deactivate' : 'Activate'">
                                        <i :class="['fas', room.is_active ? 'fa-check-circle' : 'fa-window-close']"></i>
                                    </a>
                                    <a v-if="!room.booked && !room.hasBooking && !group.deletedAt" class="table-action" @click.prevent="deleteAccomodation = room" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </template>
                </template>
			</template>
		</data-table>

		<delete-accomodation v-if="deleteAccomodation" :accomodation="deleteAccomodation" @deleted="deleted" @canceled="deleteAccomodation = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import CreateAccomodation from '@dashboard/pages/Groups/Accomodations/Create';
import DataTable from '@dashboard/components/table/Table';
import DeleteAccomodation from '@dashboard/pages/Groups/Accomodations/Delete';
import RestoreGroup from '@dashboard/pages/Groups/Restore';

export default {
	components: {
		Card,
		CreateAccomodation,
		DataTable,
		DeleteAccomodation,
		RestoreGroup
	},
	data() {
		return {
			accomodations: [],
			hotels: [],
			group: {},
			can: {},
			deleteAccomodation: null,
			pax: {
				1: 'Single',
				2: 'Double',
				3: 'Triple',
				4: 'Quad',
				5: 'Penta',
				6: 'Hexa'
			},
			showRestore: false,
		}
	},
	created() {
		this.fetchData();
	},
	computed: {
		tableColumns() {
			let columns = ['Room'];

			if (!this.group.fit) {
				columns.push('Inventory', 'Available', 'Dates', 'Rates');
			}

			columns.push('Actions');
			return columns;
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/groups/' + this.$route.params.group + '/accomodations')
				.then(response => {
					this.accomodations = response.data.data;
					this.hotels = response.data.hotels;
					this.can = response.data.can;
					this.group = response.data.group;

					this.setBreadcrumbs();
				});
		},
		setBreadcrumbs() {
			this.$store.commit('breadcrumbs', [{
					label: 'Dashboard',
					route: 'home'
				},
				{
					label: !this.group.deletedAt ? 'Groups' : 'Deleted Groups',
					route: !this.group.deletedAt ? 'groups' : 'trash'
				},
				{
					label: this.group.brideLastName + ' & ' + this.group.groomLastName,
					route: 'groups.show',
					params: {
						id: this.group.id
					}
				},
				{
					label: 'Accommodations',
					route: 'accomodations'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'accomodations.show',
				params: {
					group: this.$route.params.group,
					id: id
				}
			});
		},
		deleted() {
			this.deleteAccomodation = null;
			this.fetchData();
		},
		restored() {
			window.location.href = '/groups/' + this.group.id;
		},
        roomToggleActive(room) {
            this.$http
                .patch(`/groups/${this.group.id}/accomodations/${room.id}/room-toggle-active`)
                .then((response) => {
                    room.is_active = response.data.is_active;
                    const message = room.is_active
                    ? 'The room has been activated successfully.'
                    : 'The room has been deactivated successfully.';
                    this.$store.commit('notification', {
                        type: 'success',
                        message: message
                    });
                    this.fetchData();
                })
                .catch((error) => {
                    //
                });
        },
        hotelToggleActive(hotelblock) {
             this.$http
                 .patch(`/groups/${this.group.id}/accomodations/${hotelblock.id}/hotel-toggle-active`)
                 .then((response) => {
                     const message = response.data.is_active
                     ? 'The hotel has been activated successfully.'
                     : 'The hotel has been deactivated successfully.';
                     this.$store.commit('notification', {
                         type: 'success',
                         message: message
                     });
                     this.fetchData();
                 })
                 .catch((error) => {
                     //
                 });
         },
        isHotelActive(hotel) {
                return hotel.rooms.some(room => room.is_active);
        },
        toggleVisibility(room) {
            this.$http
                .patch(`/groups/${this.group.id}/accomodations/${room.id}/toggle-visibility`)
                .then((response) => {
                    room.isVisible = response.data.is_visible;
                    const message = room.isVisible
                        ? 'The accommodation is now visible to couples.'
                        : 'The accommodation is now hidden from couples.';
                    this.$store.commit('notification', {
                        type: 'success',
                        message: message
                    });
                })
                .catch((error) => {
                    this.$store.commit('notification', {
                        type: 'danger',
                        message: 'Failed to update accommodation visibility.'
                    });
                });
        },
	}
}
</script>
