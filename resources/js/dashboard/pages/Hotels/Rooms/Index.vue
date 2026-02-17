<template>
<card :title="hotel.name + ' - Rooms'">
	<template v-slot:action>
		<create-room v-if="can.create && !hotel.deletedAt" @created="fetchData()" :hotel="hotel" button-class="is-outlined is-primary is-inverted" />

		<a v-if="hotel.can && hotel.can.update && hotel.deletedAt" @click.prevent="showEnable = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash-restore"></i></span>
			<span>Enable Hotel</span>
		</a>
		<enable-hotel v-if="showEnable" :hotel="hotel" @enabled="enabled" @canceled="showEnable = false" />
	</template>

	<template v-if="rooms">
		<data-table class="is-size-6" table-class="is-fullwidth"
			:columns="['Room', 'Max Occupants', 'Max Adults', 'Max Children', 'Ratio', 'Actions']">
			<template v-if="rooms.length">
				<tr v-for="room in rooms">
					<th>{{ room.name }}</th>
					<td>{{ room.maxOccupants }}</td>
					<td>{{ room.maxAdults ||  room.maxOccupants }}</td>
					<td>{{ room.maxChildren || 0 }}</td>
					<td>{{ room.adultsOnly ? 'Adults Only' : room.minAdultsPerChild + ' - ' + room.maxChildrenPerAdult }}</td>
					<td>
						<a v-if="(room.can.view || room.can.update) && !hotel.deletedAt" class="table-action" @click.prevent="show(room.id)">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="room.can.delete && !hotel.deletedAt" class="table-action" @click.prevent="deleteRoom = room">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
			<tr v-else>
				<td>No records found...</td>
			</tr>
		</data-table>

		<delete-room v-if="deleteRoom" :hotel="hotel" :room="deleteRoom" @deleted="deleted" @canceled="deleteRoom = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import CreateRoom from '@dashboard/pages/Hotels/Rooms/Create';
import DataTable from '@dashboard/components/table/Table';
import DeleteRoom from '@dashboard/pages/Hotels/Rooms/Delete';
import FormField from '@dashboard/components/form/Field';
import EnableHotel from '@dashboard/pages/Hotels/Enable';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		CreateRoom,
		DataTable,
		DeleteRoom,
		FormField,
		EnableHotel,
	},
	data() {
		return {
			hotel: {},
			rooms: [],
			meta: {},
			can: {},
			deleteRoom: null,
			showEnable: false,
		}
	},
	created() {
		this.fetchData();
	},
	methods: {
		fetchData() {
			this.$http.get('/hotels/' + this.$route.params.hotel + '/rooms')
				.then(response => {
					this.hotel = response.data.hotel;
					this.rooms = response.data.data;
					this.can = response.data.can;
					this.meta = response.data.meta;

					this.setBreadcrumbs();
				});
		},
		setBreadcrumbs() {
			this.$store.commit('breadcrumbs', [{
					label: 'Dashboard',
					route: 'home'
				},
				{
					label: 'Hotels',
					route: 'hotels'
				},
				{
					label: this.hotel.name,
					route: 'hotels.show',
					params: {
						id: this.hotel.id
					}
				},
				{
					label: 'Rooms',
					route: 'rooms'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'rooms.show',
				params: {
					id: id
				}
			});
		},
		deleted() {
			this.deleteRoom = null;
			this.fetchData();
		},
		enabled() {
			window.location.href = '/hotels/' + this.hotel.id;
		},
	}
}
</script>
