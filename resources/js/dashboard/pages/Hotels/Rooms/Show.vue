<template>
<card v-if="savedRoom" :title="hotel.name + ' - ' + savedRoom.name">
	<template v-slot:action>
		<div class="buttons">
			<a @click.prevent="navigateRoom('prev')" class="button is-outlined is-primary is-inverted" :disabled="!hasPrevRoom" :class="{ 'hidden': hotelRooms.length <= 1 }">
				<span class="icon"><i class="fas fa-chevron-left"></i></span>
			</a>
			<a @click.prevent="navigateRoom('next')" class="button is-outlined is-primary is-inverted" :disabled="!hasNextRoom" :class="{ 'hidden': hotelRooms.length <= 1 }">
				<span class="icon"><i class="fas fa-chevron-right"></i></span>
			</a>
			<a v-if="savedRoom.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-trash"></i></span>
			</a>
		</div>
		<delete-room v-if="showDelete" :hotel="hotel" :room="savedRoom" @deleted="deleted" @canceled="showDelete = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">Room</tab>
			<tab @click="setTab('beds')" :is-active="tabs.beds">Beds</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<form-field label="Name" :errors="roomErrors.name" :required="true">
			<control-input v-model="room.name" :class="{ 'is-danger': (roomErrors.name | []).length }" :readonly="readonly" />
		</form-field>
		<form-field label="Description" :errors="roomErrors.description">
			<control-textarea v-model="room.description" :class="{ 'is-danger': (roomErrors.description || []).length }" :readonly="readonly" />
		</form-field>
		<form-field label="Image" :errors="roomErrors.image">
			<image-uploader v-model="room.image" @errors="$set(roomErrors, 'image', $event)" :class="{ 'is-danger': (roomErrors.image || []).length }"
				is-single :disabled="readonly" />
		</form-field>
    <div class="columns in-form">
      <div class="column">
        <form-field label="Room Size" :errors="roomErrors.size" :required="true">
          <control-input v-model="room.size" :class="{ 'is-danger': (roomErrors.size || []).length }" :readonly="readonly" />
        </form-field>
      </div>
      <div class="column">
        <form-field label="Room View" :errors="roomErrors.view" :required="true">
          <control-input v-model="room.view" :class="{ 'is-danger': (roomErrors.view || []).length }" :readonly="readonly" />
        </form-field>
      </div>
    </div>
		<div class="columns in-form">
			<div class="column">
				<form-field label="Minimum Occupants" :errors="roomErrors.minOccupants" :required="true">
					<control-input v-model="room.minOccupants" type="number" min="1" max="20"
						:class="{ 'is-danger': (roomErrors.minOccupants || []).length }" :readonly="readonly" />
				</form-field>
			</div>
			<div class="column">
				<form-field label="Maximum Occupants" :errors="roomErrors.maxOccupants" :required="true">
					<control-input v-model="room.maxOccupants" type="number" min="1" max="20"
						:class="{ 'is-danger': (roomErrors.maxOccupants || []).length }" :readonly="readonly" />
				</form-field>
			</div>
		</div>
		<form-field label="Is this room for adults only?" :errors="roomErrors.adultsOnly">
			<control-radio v-model="room.adultsOnly" :options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]" :default="false"
				:class="{ 'is-danger': (roomErrors.adultsOnly || []).length }" :readonly="readonly" />
		</form-field>
		<template v-if="!room.adultsOnly">
			<div class="columns in-form">
				<div class="column">
					<form-field label="Maximum Adults" :errors="roomErrors.maxAdults" :required="true">
						<control-input v-model="room.maxAdults" type="number" min="1" max="20"
							:class="{ 'is-danger': (roomErrors.maxAdults || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Maximum Children" :errors="roomErrors.maxChildren" :required="true">
						<control-input v-model="room.maxChildren" type="number" min="1" max="20"
							:class="{ 'is-danger': (roomErrors.maxChildren || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
			<div class="columns in-form is-mobile">
				<div class="column">
					<form-field label="Ratio" :errors="roomErrors.minAdultsPerChild" :required="true">
						<control-input v-model="room.minAdultsPerChild" type="number" min="1" max="20" placeholder="Adults"
							:class="{ 'is-danger': (roomErrors.minAdultsPerChild || []).length }" :readonly="readonly" />
					</form-field>
				</div>
				<div class="column">
					<form-field label=" " :errors="roomErrors.maxChildrenPerAdult">
						<control-input v-model="room.maxChildrenPerAdult" type="number" min="1" max="20" placeholder="Children"
							:class="{ 'is-danger': (roomErrors.maxChildrenPerAdult || []).length }" :readonly="readonly" />
					</form-field>
				</div>
			</div>
		</template>
		<control-button v-if="!readonly" @click="showUpdateAlert = true" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
		<modal @hide="showUpdateAlert = false" :title="`Update ${savedRoom.name}`" :is-active="showUpdateAlert">
			<p>
				Are you sure you want to update this room?
				<br>
				This will affect all groups offering this room, including the following active groups:
			</p>
			<br>
			<ul style="list-style: disc outside; margin-left: 0.5rem;">
				<template v-if="groups.length">
				<li v-for="group in groups">
					{{ group.brideLastName }} &amp; {{ group.groomLastName }} ({{ $moment(group.eventDate).format('MMMM YYYY') }})
				</li>
				</template>
				<li v-else>No groups are currently offering this room as an accommodation.</li>
			</ul>
			<template v-slot:footer>
				<div class="field is-grouped">
					<control-button @click="showUpdateAlert = false">Cancel</control-button>
					<control-button @click="update" class="is-primary">Yes</control-button>
				</div>
			</template>
		</modal>
	</template>
	<template v-if="tabs.beds">
		<form-panel label="Bed Types" class="is-borderless">
			<div class="columns is-mobile is-variable is-1">
				<div class="column">
					<label class="label">Bed</label>
				</div>
			</div>
			<div v-for="(bed, index) in beds" class="columns is-mobile is-variable is-1">
				<div class="column">
					<form-field :errors="bedsErrors['beds.' + index]">
						<control-input v-model="beds[index]" :class="{ 'is-danger': (bedsErrors['beds.' + index] || []).length }"
							:readonly="readonly" />
					</form-field>
				</div>
				<div v-if="!readonly && index" class="column is-narrow">
					<control-button class="is-link is-outlined" @click="beds.splice(index, 1)">
						<i class="fas fa-minus"></i>
					</control-button>
				</div>
			</div>
			<a v-if="!readonly" @click.prevent="beds.push('')" class="has-text-mauve">
				+ Add another bed
			</a>
		</form-panel>
		<control-button v-if="!readonly" @click="updateBeds" class="is-primary" :class="{ 'is-loading': isLoading === 'updateBeds' }">Save
		</control-button>
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlRadio from '@dashboard/components/form/controls/Radio';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';
import DeleteRoom from '@dashboard/pages/Hotels/Rooms/Delete';
import FormField from '@dashboard/components/form/Field';
import FormPanel from '@dashboard/components/form/Panel';
import ImageUploader from '@dashboard/components/file/ImageUploader';
import Modal from '@dashboard/components/Modal';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		ControlRadio,
		ControlTextarea,
		DeleteRoom,
		FormField,
		FormPanel,
		ImageUploader,
		Modal,
		Tab,
		Tabs,
	},
	data() {
		return {
			savedRoom: null,
			hotel: {},
			groups: [],
			room: {},
			roomErrors: {},
			beds: [],
			bedsErrors: {},
			showUpdateAlert: false,
			showDelete: false,
			tabs: {
				info: true,
				beds: false
			},
			isLoading: '',
			currentRoomIndex: 0,
			hotelRooms: [],
		}
	},
	created() {
		this.fetchData();
	},
	computed: {
		readonly() {
			return !this.savedRoom.can.update;
		},
		hasPrevRoom() {
			return this.currentRoomIndex > 0;
		},
		hasNextRoom() {
			return this.currentRoomIndex < this.hotelRooms.length - 1;
		},
	},
	methods: {
		fetchData() {
			this.$http.get('/hotels/' + this.$route.params.hotel + '/rooms/' + this.$route.params.id)
				.then(response => {
					this.savedRoom = response.data.data;
					this.hotel = response.data.hotel;
					this.groups = response.data.activeGroups;
					this.room = {
						name: this.savedRoom.name,
						description: this.savedRoom.description,
						size: this.savedRoom.size || '',
						view: this.savedRoom.view || '',
						image: this.savedRoom.image,
						minOccupants: this.savedRoom.minOccupants,
						maxOccupants: this.savedRoom.maxOccupants,
						adultsOnly: this.savedRoom.adultsOnly,
						maxAdults: this.savedRoom.maxAdults,
						maxChildren: this.savedRoom.maxChildren,
						minAdultsPerChild: this.savedRoom.minAdultsPerChild,
						maxChildrenPerAdult: this.savedRoom.maxChildrenPerAdult
					};
					this.beds = this.savedRoom.beds;

					this.hotelRooms = response.data.hotel.rooms;
					this.currentRoomIndex = this.hotelRooms.findIndex(room => room.id === this.savedRoom.id);

					this.setBreadcrumbs();
				}).catch(error => {
					if (error.response.status === 403) {
						this.$store.commit('error', {
							status: 403,
							message: error.response.statusText
						});
					}
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
				},
				{
					label: this.savedRoom.name,
					route: 'rooms.show',
					params: {
						id: this.savedRoom.id
					}
				}
			]);
		},
		setTab(tab) {
			Object.keys(this.tabs).forEach(key => this.tabs[key] = false);
			this.tabs[tab] = true;
		},
		update() {
			this.showUpdateAlert = false;
			this.isLoading = 'update';
			let request = this.$http.put('/hotels/' + this.hotel.id + '/rooms/' + this.$route.params.id, this.room)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The room has been updated.'
					});
					this.savedRoom = response.data.data;
					this.roomErrors = {};
				}).catch(error => {
					if (error.response.status === 422) {
						this.roomErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		updateBeds() {
			this.isLoading = 'updateBeds';
			let request = this.$http.patch('/hotels/' + this.hotel.id + '/rooms/' + this.$route.params.id + '/beds', {
					beds: this.beds
				})
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The beds have been saved.'
					});
					this.savedRoom.beds = response.data.data;
					this.bedsErrors = {};
				}).catch(error => {
					if (error.response.status === 422) {
						this.bedsErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'rooms'
			});
		},
		navigateRoom(direction) {
			const newIndex = direction === 'prev' ? this.currentRoomIndex - 1 : this.currentRoomIndex + 1;
			if (newIndex >= 0 && newIndex < this.hotelRooms.length) {
				const targetRoom = this.hotelRooms[newIndex];
				window.location.href = '/hotels/' + this.hotel.id + '/rooms/' + targetRoom.id;
			}
		}
	}
}
</script>
