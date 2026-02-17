<template>
	<card v-if="savedDestination" :title="savedDestination.name + ', ' + savedDestination.country.name">
		<template v-slot:action>
			<router-link v-if="can.viewHotels" :to="{ name: 'hotels', query: { destination: savedDestination.id } }" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-hotel"></i></span>
				<span>Hotels</span>
			</router-link>
			<a v-if="savedDestination.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-trash"></i></span>
			</a>
			<delete-destination v-if="showDelete" :destination="savedDestination" @deleted="deleted" @canceled="showDelete = false" />
		</template>
		<template v-slot:tabs>
			<tabs class="is-boxed">
				<tab @click="setTab('info')" :is-active="tabs.info">Destination</tab>
			</tabs>
		</template>
		<template v-if="tabs.info">
			<form-field label="Name" :errors="destinationErrors.name" :required="true">
				<control-input v-model="destination.name" :class="{ 'is-danger': destinationErrors.name && destinationErrors.name.length }" :readonly="readonly" />
			</form-field>

			<form-field label="Airports" :errors="destinationErrors.airports" :required="true">
				<span style="font-style: italic">(The first will be the default one)</span>
				<control-select 
					v-model="newAirport.airport_code" 
					class="width-86"
					:class="{ 'is-danger': newAirportErrors.airport_code && newAirportErrors.airport_code.length }" 
					:options="[...airports]" 
					first-is-empty 
				/>
				<button @click="addAirport" class="button is-link button-left-margin" :class="{ 'is-loading': newAirportIsLoading }">Add</button>
				<p v-if="'newAirport.airport_code' in newAirportErrors" class="help is-danger">{{ newAirportErrors['newAirport.airport_code'][0] }}</p>
			</form-field>

			<form-field>
				<div v-if="destination.airports.length > 0" class="table-container">
					<table class="table is-fullwidth">
						<tbody>
							<tr v-for="(airport, index) in destination.airports" :key="airport.airport_code" class="airport-row">
								<td>{{ airport.airport_code }}</td>
								<td class="has-text-right">
									<a @click="destination.airports.splice(index, 1)">
										<span class="icon has-text-link">
											<i class="fas fa-times"></i>
										</span>
									</a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</form-field>		

			<form-field label="Weather Description" :errors="destinationErrors.weatherDescription">
				<control-textarea v-model="destination.weatherDescription" :class="{ 'is-danger': (destinationErrors.weatherDescription || []).length }" :readonly="readonly" />
			</form-field>

			<form-field>
				<label class="checkbox">
					<input type="checkbox" v-model="destination.outletAdapter">
					Requires Power Outlet Adapter
				</label>
			</form-field>

			<form-field label="Tax Description (Travel Documents)" :errors="destinationErrors.taxDescription">
				<control-editor v-model="destination.taxDescription" :class="{ 'is-danger': (destinationErrors.taxDescription || []).length }" :readonly="readonly" />                                    
			</form-field>

			<form-field label="Language Description (Travel Documents)" :errors="destinationErrors.languageDescription">
				<control-input v-model="destination.languageDescription" ::class="{ 'is-danger': (destinationErrors.languageDescription || []).length }" :readonly="readonly" />
			</form-field>

			<form-field label="Currency Description (Travel Documents)" :errors="destinationErrors.currencyDescription">
				<control-input v-model="destination.currencyDescription" :class="{ 'is-danger': (destinationErrors.currencyDescription || []).length }" :readonly="readonly" />
			</form-field>

      <form-field label="Bon Voyage Image (Travel Documents)" :errors="destinationErrors.image">
        <image-uploader v-model="destination.image" :disabled="readonly" @errors="$set(destinationErrors, 'image', $event)" :class="{ 'is-danger': (destinationErrors.image || []).length }" :max-size="2048" is-single />
      </form-field>

			<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
		</template>
	</card>
</template>

<script>
	import Card from '@dashboard/components/Card';
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';
	import DeleteDestination from '@dashboard/pages/Destinations/Delete';
	import FormField from '@dashboard/components/form/Field';
	import Tab from '@dashboard/components/tabs/Tab';
	import Tabs from '@dashboard/components/tabs/Tabs';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import ControlEditor from '@dashboard/components/form/controls/QuillEditor';
	import ImageUploader from '@dashboard/components/file/ImageUploader';

	export default {
		components: {
			Card,
			ControlButton,
			ControlInput,
			ControlTextarea,
			DeleteDestination,
			FormField,
			Tab,
			Tabs,
			ControlSelect,
			ControlEditor,
			ImageUploader,
		},

		data() {
			return {
				savedDestination: null,
				destination: {},
				airports: [],
				destinationErrors: {},
				showDelete: false,
				tabs: {
					info: true
				},
				isLoading: '',
				can: {},
				newAirport: {
					airport_code: null
				},			
				newAirportErrors: {},
				newAirportIsLoading: false,
			}
		},

		created() {
			this.fetchData();
		},

		computed: {
			readonly() {
				return !this.savedDestination.can.update;
			}
		},

		methods: {
			fetchData() {
				this.$http.get('/destinations/' + this.$route.params.id)
					.then(response => {
						this.savedDestination = response.data.data;

						this.airports = response.data.airports.map(airport => ({
							value: airport.airport_code,
							text: airport.airport_code,
						}));

						this.destination = {
							name: this.savedDestination.name,
							airportCode: this.savedDestination.airportCode,
							airports: this.savedDestination.airports,
							weatherDescription: this.savedDestination.weatherDescription,
							outletAdapter: this.savedDestination.outletAdapter,
							taxDescription: this.savedDestination.taxDescription,
							languageDescription: this.savedDestination.languageDescription,
							currencyDescription: this.savedDestination.currencyDescription,
							image: this.savedDestination.image,
						};

						this.can = response.data.can;
						this.setBreadcrumbs();
					});
			},

			setBreadcrumbs() {
				this.$store.commit('breadcrumbs', [
					{
						label: 'Dashboard',
						route: 'home'
					},
					{
						label: 'Destinations',
						route: 'destinations'
					},
					{
						label: this.savedDestination.name,
						route: 'destinations.show',
						params: {
							id: this.savedDestination.id
						}
					}
				]);
			},

			setTab(tab) {
				Object.keys(this.tabs).forEach(key => this.tabs[key] = false);
				this.tabs[tab] = true;
			},

			update() {
				this.isLoading = 'update';
				
				let request = this.$http.put('/destinations/' + this.$route.params.id, this.destination)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The destination has been updated.'
						});

						this.savedDestination = response.data.data;
						this.destinationErrors = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.destinationErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			},

			addAirport() {
				this.newAirportErrors = {};
				this.newAirportIsLoading = true;

				let request = this.$http.post(`/destinations/validate-airport`, {newAirport: this.newAirport, airports: this.destination.airports})
					.then(() => {
						this.newAirport.airport_code = this.newAirport.airport_code.toUpperCase();
						this.destination.airports.push(this.newAirport);
						this.newAirport = this.$options.data.call(this).newAirport;
						delete this.destinationErrors['airports'];
					}).catch(error => {
						if (error.response.status == 422) {
							this.newAirportErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.newAirportIsLoading = false;
				});
			},
		
			deleted() {
				this.$router.push({
					name: 'destinations'
				});
			}
		}
	}
</script>