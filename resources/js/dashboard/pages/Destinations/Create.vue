<template>
	<div>
		<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Destination</a>
		<modal v-if="render" @hide="show = false" title="New Destination" :is-active="show">
			<form-field label="Name" :errors="destinationErrors.name" :required="true">
				<control-input v-model="destination.name" :class="{ 'is-danger': destinationErrors.name && destinationErrors.name.length }" />
			</form-field>
			
			<form-field label="Airports" :required="true">
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

			<form-field :errors="destinationErrors.airports">
				<div v-if="	destination.airports.length > 0" class="table-container">
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

			<form-field label="Country" :errors="destinationErrors.country" :required="true">
				<control-select 
					v-model="destination.country" 
					:class="{ 'is-danger': destinationErrors.country && destinationErrors.country.length }"
					:options="[...countries, {value: 0, text: 'Other'}]" first-is-empty :disabled="destination.hasOtherCountry" 
				/>
			</form-field>

			<form-field v-if="destination.country == 0" :errors="destinationErrors.otherCountry" :required="true">
				<control-input 
					v-model="destination.otherCountry" 
					class="is-capitalized"
					:class="{ 'is-danger': destinationErrors.otherCountry && destinationErrors.otherCountry.length }" 
				/>
			</form-field>

			<form-field label="Weather Description" :errors="destinationErrors.weatherDescription">
				<control-textarea v-model="destination.weatherDescription" :class="{ 'is-danger': (destinationErrors.weatherDescription || []).length }" />
			</form-field>

			<form-field>
				<label class="checkbox">
					<input type="checkbox" v-model="destination.outletAdapter">
					Requires Power Outlet Adapter
				</label>
			</form-field>

			<form-field label="Tax Description (Travel Documents)" :errors="destinationErrors.taxDescription">
				<control-editor v-model="destination.taxDescription" :class="{ 'is-danger': destinationErrors.taxDescription && destinationErrors.taxDescription.length }" />                                    
			</form-field>

			<form-field label="Language Description (Travel Documents)" :errors="destinationErrors.languageDescription">
				<control-input v-model="destination.languageDescription" :class="{ 'is-danger': destinationErrors.languageDescription && destinationErrors.languageDescription.length }" />
			</form-field>

			<form-field label="Currency Description (Travel Documents)" :errors="destinationErrors.currencyDescription">
				<control-input v-model="destination.currencyDescription" :class="{ 'is-danger': destinationErrors.currencyDescription && destinationErrors.currencyDescription.length }" />
			</form-field>

      <form-field label="Bon Voyage Image (Travel Documents)" :errors="destinationErrors.image">
        <image-uploader v-model="destination.image" @errors="$set(destinationErrors, 'image', $event)" :class="{ 'is-danger': (destinationErrors.image || []).length }" :max-size="2048" is-single />
			</form-field>

			<template v-slot:footer>
				<div class="field is-grouped">
					<control-button @click="close" :disabled="isLoading">Cancel</control-button>
					<control-button @click="create" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Submit</control-button>
				</div>
			</template>
		</modal>
	</div>
</template>

<script>
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';
	import FormField from '@dashboard/components/form/Field';
	import Modal from '@dashboard/components/Modal';
	import ControlEditor from '@dashboard/components/form/controls/QuillEditor';
	import ImageUploader from '@dashboard/components/file/ImageUploader';

	export default {
		components: {
			ControlButton,
			ControlInput,
			ControlSelect,
			ControlTextarea,
			FormField,
			Modal,
			ControlEditor,
			ImageUploader,
		},

		props: {
			buttonClass: String,
			countries: {
				type: Array,
				required: true
			},
			airports: {
				type: Array,
				required: true
			}
		},

		data() {
			return {
				render: false,
				show: false,
				destination: {
					airports: [],
					image: null,
				},
				destinationErrors: {},
				isLoading: false,
				newAirport: {
					airport_code: null
				},			
				newAirportErrors: {},
				newAirportIsLoading: false,
			}
		},

		methods: {
			create() {
				this.isLoading = true;

				let request = this.$http.post('/destinations', this.destination)
					.then(response => {
						this.close();
						this.$emit('created', response.data.data);

						this.$store.commit('notification', {
							type: 'success',
							message: 'The new destination has been created.',
						});
					})
					.catch(error => {
						if (error.response.status == 422) {
							this.destinationErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = false;
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

			close() {
				Object.assign(this.$data, this.$options.data.apply(this));
			}
		}
	}
</script>