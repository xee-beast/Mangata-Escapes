<template>
<card v-if="savedHotel" :title="savedHotel.name + ', ' + savedHotel.destination.name">
	<template v-slot:action>
		<router-link v-if="savedHotel.can.viewRooms" :to="{ name: 'rooms', params: { hotel: savedHotel.id }}" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-door-open"></i></span>
			<span>Rooms</span>
		</router-link>
		<a v-if="savedHotel.can.delete && !savedHotel.deletedAt" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash"></i></span>
		</a>
		<delete-hotel v-if="showDelete" :hotel="savedHotel" @deleted="deleted" @canceled="showDelete = false" />
		<a v-if="savedHotel.can.update && savedHotel.deletedAt" @click.prevent="showEnable = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash-restore"></i></span>
			<span>Enable Hotel</span>
		</a>
		<enable-hotel v-if="showEnable" :hotel="savedHotel" @enabled="enabled" @canceled="showEnable = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">Hotel</tab>
			<tab @click="setTab('images')" :is-active="tabs.images">Images</tab>
			<tab @click="setTab('airportRates')" :is-active="tabs.airportRates">Airport Rates</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<form-field label="Name" :errors="hotelErrors.name" :required="true">
			<control-input v-model="hotel.name" :class="{ 'is-danger': (hotelErrors.name || []).length }" :readonly="readonly" />
		</form-field>
		<form-field label="Destination" :errors="hotelErrors.destination" :required="true">
			<control-select v-model="hotel.destination" :class="{ 'is-danger': hotelErrors.destination && (hotelErrors.destination || []).length }" :options="destinations" :disabled="readonly" />
		</form-field>
		<form-field label="Description" :errors="hotelErrors.description">
			<control-editor v-model="hotel.description" :class="{ 'is-danger': (hotelErrors.description || []).length }" :disabled="readonly" />
		</form-field>
    <form-field label="Hotel URL" :errors="hotelErrors.url">
			<control-input v-model="hotel.url" :class="{ 'is-danger': (hotelErrors.url || []).length }" :disabled="readonly" />
		</form-field>
		<div class="panel-head columns is-vcentered is-mobile">
			<div class="column">
				<label for="" class="label">{{ showImages ? 'Collapse Images Section' : 'Expand Images Section' }}</label>
			</div>
			<div class="panel-actions column is-narrow">
				<control-button class="is-small is-link is-outlined" @click="showImages = !showImages">
					<i :class="showImages ? 'fas fa-minus' : 'fas fa-plus'"></i>
				</control-button>
			</div>
		</div>
		<div v-if="showImages" class="panel">
			<form-field label="Cover Image (Travel Documents)" :errors="hotelErrors.travelDocsCoverImage">
				<image-uploader v-model="hotel.travelDocsCoverImage" @errors="$set(hotelErrors, 'travelDocsCoverImage', $event)"
					:class="{ 'is-danger': (hotelErrors.travelDocsCoverImage || []).length }" :max-size="3072" is-single :disabled="readonly" />
			</form-field>
			<form-field label="Second Image (Travel Documents)" :errors="hotelErrors.travelDocsImageTwo">
				<image-uploader v-model="hotel.travelDocsImageTwo" @errors="$set(hotelErrors, 'travelDocsImageTwo', $event)"
					:class="{ 'is-danger': (hotelErrors.travelDocsImageTwo || []).length }" :max-size="3072" is-single :disabled="readonly" />
			</form-field>
			<form-field label="Third Image (Travel Documents)" :errors="hotelErrors.travelDocsImageThree">
				<image-uploader v-model="hotel.travelDocsImageThree" @errors="$set(hotelErrors, 'travelDocsImageThree', $event)"
					:class="{ 'is-danger': (hotelErrors.travelDocsImageThree || []).length }" :max-size="3072" is-single :disabled="readonly" />
			</form-field>
		</div>
		<control-button v-if="!readonly" @click="showUpdateAlert = true" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }" :disabled="isLoading === 'update'">Save</control-button>
		<modal @hide="showUpdateAlert = false" :title="`Update ${savedHotel.name}`" :is-active="showUpdateAlert">
			<p>
				Are you sure you want to update this hotel?
				<br>
				This will affect all groups offering this hotel.
			</p>
			<template v-slot:footer>
				<div class="field is-grouped">
					<control-button @click="showUpdateAlert = false">Cancel</control-button>
					<control-button @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }" :disabled="isLoading === 'update'">Yes</control-button>
				</div>
			</template>
		</modal>
	</template>
	<template v-if="tabs.images">
		<form-field :errors="imagesErrors.images">
			<image-uploader v-model="images" @errors="$set(imagesErrors, 'images', $event)"
				:class="{ 'is-danger': (imagesErrors.images || []).length }" :disabled="readonly" />
		</form-field>
		<control-button v-if="!readonly" @click="syncImages" class="is-primary" :class="{ 'is-loading': isLoading === 'images' }">Save</control-button>
	</template>
	<template v-if="tabs.airportRates">
		<div v-if="!readonly" class="box mb-4">
			<div class="columns">
				<div class="column is-3">
					<form-field label="Airport" :errors="airportRateErrors.airport_id" :required="true">
						<control-select  v-model="newAirportRate.airport_id"  :options="availableAirports" first-is-empty
							:class="{ 'is-danger': (airportRateErrors.airport_id || []).length }"
						/>
					</form-field>
				</div>
				<div class="column is-3">
					<form-field label="Transportation Rate" :errors="airportRateErrors.transportation_rate" :required="true">
						<control-input v-model="newAirportRate.transportation_rate" type="number" step="0.01" min="0" placeholder="0.00"
							:class="{ 'is-danger': (airportRateErrors.transportation_rate || []).length }"
						/>
					</form-field>
				</div>
				<div class="column is-3">
					<form-field label="Single Transportation Rate" :errors="airportRateErrors.single_transportation_rate" :required="true">
						<control-input v-model="newAirportRate.single_transportation_rate" type="number" step="0.01" min="0" placeholder="0.00"
							:class="{ 'is-danger': (airportRateErrors.single_transportation_rate || []).length }"
						/>
					</form-field>
				</div>
				<div class="column is-3">
					<form-field label="One Way Transp. Rate" :errors="airportRateErrors.one_way_transportation_rate" :required="true">
						<control-input v-model="newAirportRate.one_way_transportation_rate" type="number" step="0.01" min="0" placeholder="0.00"
							:class="{ 'is-danger': (airportRateErrors.one_way_transportation_rate || []).length }"
						/>
					</form-field>
				</div>
			</div>
			<control-button
				@click="addAirportRate"
				class="is-primary"
				:class="{ 'is-loading': isLoading === 'addRate' }"
				:disabled="isLoading === 'addRate'"
			>
				Add Rate
			</control-button>
		</div>
		<div v-if="savedHotel.hotelAirportRates && savedHotel.hotelAirportRates.length > 0">
			<data-table class="is-size-6 mt-30" table-class="is-fullwidth" :columns="['Airport Code', 'Transportation Rate', 'Single Transp. Rate', 'One Way Transp. Rate', 'Actions']">
				<tr v-for="rate in savedHotel.hotelAirportRates" :key="rate.id">
					<td><strong>{{ rate.airport.airport_code }}</strong></td>
					<td>
						<span v-if="editingRateId !== rate.id">{{ rate.transportation_rate ? '$' + parseFloat(rate.transportation_rate).toFixed(2) : '-' }}</span>
						<template v-else>
							<control-input v-model="editingRate.transportation_rate" type="number" step="0.01" min="0" placeholder="0.00" class="is-small"
								:class="{ 'is-danger': (editingRateErrors.transportation_rate || []).length }"
							/>
							<p v-if="editingRateErrors.transportation_rate" class="help is-danger">{{ editingRateErrors.transportation_rate[0] }}</p>
						</template>
					</td>
					<td>
						<span v-if="editingRateId !== rate.id">{{ rate.single_transportation_rate ? '$' + parseFloat(rate.single_transportation_rate).toFixed(2) : '-' }}</span>
						<template v-else>
							<control-input v-model="editingRate.single_transportation_rate" type="number" step="0.01" min="0" placeholder="0.00" class="is-small"
								:class="{ 'is-danger': (editingRateErrors.single_transportation_rate || []).length }"
							/>
							<p v-if="editingRateErrors.single_transportation_rate" class="help is-danger">{{ editingRateErrors.single_transportation_rate[0] }}</p>
						</template>
					</td>
					<td>
						<span v-if="editingRateId !== rate.id">{{ rate.one_way_transportation_rate ? '$' + parseFloat(rate.one_way_transportation_rate).toFixed(2) : '-' }}</span>
						<template v-else>
							<control-input v-model="editingRate.one_way_transportation_rate" type="number" step="0.01" min="0" placeholder="0.00" class="is-small"
								:class="{ 'is-danger': (editingRateErrors.one_way_transportation_rate || []).length }"
							/>
							<p v-if="editingRateErrors.one_way_transportation_rate" class="help is-danger">{{ editingRateErrors.one_way_transportation_rate[0] }}</p>
						</template>
					</td>
					<td>
						<template v-if="editingRateId !== rate.id">
							<a v-if="rate.can.update && !readonly" @click="startEditRate(rate)" class="table-action" title="Edit Rate">
								<i class="fas fa-edit"></i>
							</a>
							<a v-if="rate.can.delete && !readonly" @click="showDeleteRateConfirm(rate)" class="table-action" title="Delete Rate">
								<i class="fas fa-trash"></i>
							</a>
						</template>
						<template v-else>
							<a @click="saveEditRate(rate)" class="table-action" title="Save">
								<i class="fas fa-save"></i>
							</a>
							<a @click="cancelEditRate" class="table-action" title="Cancel">
								<i class="fas fa-times"></i>
							</a>
						</template>
					</td>
				</tr>
			</data-table>
		</div>
		<div v-else class="has-text-centered py-5">
			<p class="has-text-grey">No airport rates configured yet.</p>
		</div>

		<modal @hide="showDeleteRateModal = false" title="Delete Airport Rate" :is-active="showDeleteRateModal">
			<p>Are you sure you want to delete this airport rate?</p>
			<template v-slot:footer>
				<div class="field is-grouped">
					<control-button @click="showDeleteRateModal = false" class="is-dark">Cancel</control-button>
					<control-button @click="confirmDeleteRate" class="is-primary" :class="{ 'is-loading': isLoading === 'deleteRate' }" :disabled="isLoading === 'deleteRate'">Delete</control-button>
				</div>
			</template>
		</modal>
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlEditor from '@dashboard/components/form/controls/QuillEditor';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlSelect from '@dashboard/components/form/controls/Select';
import DeleteHotel from '@dashboard/pages/Hotels/Delete';
import FormField from '@dashboard/components/form/Field';
import ImageUploader from '@dashboard/components/file/ImageUploader';
import Modal from '@dashboard/components/Modal';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';
import EnableHotel from '@dashboard/pages/Hotels/Enable';
import DataTable from '@dashboard/components/table/Table';

export default {
	components: {
		Card,
		ControlButton,
		ControlEditor,
		ControlInput,
		ControlSelect,
		DeleteHotel,
		FormField,
		ImageUploader,
		Modal,
		Tab,
		Tabs,
		EnableHotel,
		DataTable,
	},
	data() {
		return {
			savedHotel: null,
			hotel: {},
			hotelErrors: {},
			images: null,
			showImages: false,
			imagesErrors: {},
			destinations: [],
			showUpdateAlert: false,
			showDelete: false,
			showEnable: false,
			tabs: {
				info: true,
				images: false,
				airportRates: false
			},
			isLoading: '',
			newAirportRate: {
				airport_id: null,
				transportation_rate: null,
				single_transportation_rate: null,
				one_way_transportation_rate: null
			},
			airportRateErrors: {},
			editingRateId: null,
			editingRate: {},
			editingRateErrors: {},
			showDeleteRateModal: false,
			rateToDelete: null
		}
	},
	created() {
		this.fetchData();
	},
	computed: {
		readonly() {
			return !this.savedHotel.can.update || !!this.savedHotel.deletedAt;
		},
		availableAirports() {
			if (!this.savedHotel || !this.savedHotel.destination || !this.savedHotel.destination.airports) {
				return [];
			}
			
			const usedAirportIds = (this.savedHotel.hotelAirportRates || []).map(rate => rate.airport_id);
			
			return this.savedHotel.destination.airports
				.filter(airport => !usedAirportIds.includes(airport.id))
				.map(airport => ({
					value: airport.id,
					text: airport.airport_code
				}));
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/hotels/' + this.$route.params.id)
				.then(response => {
					this.savedHotel = response.data.data;
					this.destinations = response.data.meta.destinations;
					this.hotel = {
						name: this.savedHotel.name,
						destination: this.savedHotel.destination.id,
						description: this.savedHotel.description,
						url: this.savedHotel.url,
						travelDocsCoverImage: this.savedHotel.travelDocsCoverImage,
						travelDocsImageTwo: this.savedHotel.travelDocsImageTwo,
						travelDocsImageThree: this.savedHotel.travelDocsImageThree,
					};
					this.images = this.savedHotel.images;

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
					label: this.savedHotel.name,
					route: 'hotels.show',
					params: {
						id: this.savedHotel.id
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
			let request = this.$http.put('/hotels/' + this.$route.params.id, this.hotel)
				.then(response => {
					this.savedHotel = response.data.data;
					this.$store.commit('notification', {
						type: 'success',
						message: 'The hotel\'s information has been updated.'
					});
					this.hotelErrors = [];
				}).catch(error => {
					if (error.response.status == 422) {
						this.hotelErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		syncImages() {
			this.isLoading = 'images';
			let request = this.$http.put('/hotels/' + this.$route.params.id + '/images', {
				images: this.images
			}).then(response => {
				this.$store.commit('notification', {
					type: 'success',
					message: 'The images have been saved.'
				});

				this.imagesErrors = {};
			}).catch(error => {
				if (error.response.status == 422) {
					this.imagesErrors = error.response.data.errors;
				}
			});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'hotels'
			});
		},
		enabled() {
			window.location.href = '/hotels/' + this.savedHotel.id;
		},
		addAirportRate() {
			this.airportRateErrors = {};
			this.isLoading = 'addRate';
			
			this.$http.post('/hotels/' + this.$route.params.id + '/airport-rates', this.newAirportRate)
				.then(response => {
					this.savedHotel.hotelAirportRates.push(response.data.data);
					this.newAirportRate = {
						airport_id: null,
						transportation_rate: null,
						single_transportation_rate: null,
						one_way_transportation_rate: null
					};
					this.$store.commit('notification', {
						type: 'success',
						message: 'Airport rate has been added.'
					});
				})
				.catch(error => {
					if (error.response.status === 422) {
						this.airportRateErrors = error.response.data.errors || {};
					}
				})
				.finally(() => {
					this.isLoading = '';
				});
		},
		startEditRate(rate) {
			this.editingRateId = rate.id;
			this.editingRate = {
				transportation_rate: rate.transportation_rate,
				single_transportation_rate: rate.single_transportation_rate,
				one_way_transportation_rate: rate.one_way_transportation_rate
			};
			this.editingRateErrors = {};
		},
		saveEditRate(rate) {
			this.editingRateErrors = {};
			this.isLoading = 'editRate';
			
			this.$http.put('/hotels/' + this.$route.params.id + '/airport-rates/' + rate.id, this.editingRate)
				.then(response => {
					const index = this.savedHotel.hotelAirportRates.findIndex(r => r.id === rate.id);
					if (index !== -1) {
						this.$set(this.savedHotel.hotelAirportRates, index, response.data.data);
					}
					this.editingRateId = null;
					this.editingRate = {};
					this.$store.commit('notification', {
						type: 'success',
						message: 'Airport rate has been updated.'
					});
				})
				.catch(error => {
					if (error.response.status === 422) {
						this.editingRateErrors = error.response.data.errors || {};
					}
				})
				.finally(() => {
					this.isLoading = '';
				});
		},
		cancelEditRate() {
			this.editingRateId = null;
			this.editingRate = {};
			this.editingRateErrors = {};
		},
		showDeleteRateConfirm(rate) {
			this.rateToDelete = rate;
			this.showDeleteRateModal = true;
		},
		confirmDeleteRate() {
			if (!this.rateToDelete) {
				return;
			}

			this.isLoading = 'deleteRate';

			this.$http.delete('/hotels/' + this.$route.params.id + '/airport-rates/' + this.rateToDelete.id)
				.then(() => {
					const index = this.savedHotel.hotelAirportRates.findIndex(r => r.id === this.rateToDelete.id);

					if (index !== -1) {
						this.savedHotel.hotelAirportRates.splice(index, 1);
					}
					this.$store.commit('notification', {
						type: 'success',
						message: 'Airport rate has been deleted.'
					});
				})
				.catch(() => {
          this.$store.commit('notification', {
						type: 'danger',
						message: 'Failed to delete airport rate.'
					});
				})
				.finally(() => {
					this.isLoading = '';
					this.showDeleteRateModal = false;
					this.rateToDelete = null;
				});
		}
	}
}
</script>
<style lang="css" scoped>
  .mt-30{
    margin-top: 30px;
  }
</style>
