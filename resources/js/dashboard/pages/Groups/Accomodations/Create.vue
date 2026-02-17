<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Accommodation</a>

	<modal v-if="render" @hide="show = false" title="Add Room" :is-active="show">
		<form-field label="Hotel" :errors="accomodationErrors.hotel" :required="true">
			<control-select v-model="accomodation.hotel" :options="hotels.map(hotel => ({value: hotel.id, text: hotel.name}))" first-is-empty
				:class="{ 'is-danger': [...(accomodationErrors.hotel || [])].length }" />
		</form-field>
		<form-field label="Room" :errors="accomodationErrors.room" :required="true">
			<control-select v-model="accomodation.room"
				:options="selectedHotel ? selectedHotel.rooms.map(room => ({value: room.id, text: room.name})) : []" first-is-empty
				:class="{ 'is-danger': [...(accomodationErrors.room || [])].length }" />
		</form-field>

		<div v-if="!group.fit">
			<form-field label="Inventory" :errors="accomodationErrors.inventory">
				<control-input v-model="accomodation.inventory" type="number"
					:class="{ 'is-danger': [...(accomodationErrors.inventory || [])].length }" />
			</form-field>
			<form-field label="Block Dates" :errors="[...(accomodationErrors['dates.start'] || []), ...(accomodationErrors['dates.end'] || [])]" :required="true">
				<date-picker v-model="accomodationDates" is-range
					:popover="{ visibility: 'focus' }"
					:min-date="$moment(group.eventDate).subtract(14, 'days').toDate()"
					:max-date="$moment(group.eventDate).add(14, 'days').toDate()"
				>
					<template v-slot="{ inputValue, inputEvents }">
						<input
							:value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
							v-on="inputEvents.start"
							:class="'input' + ([...(accomodationErrors['dates.start'] || []), ...(accomodationErrors['dates.end'] || [])].length ? ' is-danger' : '')"
						/>
					</template>
				</date-picker>
			</form-field>
			<form-field :errors="accomodationErrors.hasSplitDates">
				<label class="checkbox">
					<input v-model="accomodation.hasSplitDates" type="checkbox">
					Split Dates
				</label>
			</form-field>
			<form-field v-if="accomodation.hasSplitDates" label="Split Date" :errors="accomodationErrors.splitDate" :required="true">
				<date-picker v-model="accomodationSplitDate"
					:popover="{ visibility: 'focus' }"
					:min-date="accomodation.dates.start || $moment(group.eventDate).subtract(14, 'days').toDate()"
					:max-date="accomodation.dates.end || $moment(group.eventDate).add(14, 'days').toDate()"
				>
					<template v-slot="{ inputValue, inputEvents }">
						<input
							:value="inputValue"
							v-on="inputEvents"
							:class="'input' + ((accomodationErrors.splitDate || []).length ? ' is-danger' : '')"
						/>
					</template>
				</date-picker>
			</form-field>

			<template v-if="selectedRoom">
				<form-panel label="Rates" class="is-borderless">
					<template v-slot:action>
						<control-button v-if="accomodation.rates.length > 1" class="is-small is-link is-outlined" @click="accomodation.rates.pop()">
							<i class="fas fa-minus"></i>
						</control-button>
						<control-button
							v-if="(selectedRoom.minOccupants + accomodation.rates.length) <= (selectedRoom.maxAdults || selectedRoom.maxOccupants)"
							class="is-small is-link is-outlined" @click="accomodation.rates.push({})">
							<i class="fas fa-plus"></i>
						</control-button>
					</template>
					<form-panel v-for="(rate, index) in accomodation.rates" :key="index" :label="'Rate/Adult/Night for ' + (selectedRoom.minOccupants + index) + ' in a room'">
						<div class="columns">
							<div class="column">
								<form-field
									:label="'Rate' + (accomodation.hasSplitDates ? ` (Before ${$moment(accomodation.splitDate || group.eventDate).format('MM/DD/YYYY')})` : '')"
									:errors="accomodationErrors['rates.'+index+'.rate'] || []" :required="true">
									<control-input v-model="rate.rate"
										:class="{ 'is-danger': [...(accomodationErrors['rates.'+index+'.rate'] || [])].length }" />
								</form-field>
							</div>
							<div class="column">
								<form-field label="Supplier Rate" :errors="accomodationErrors['rates.'+index+'.providerRate'] || []">
									<control-input v-model="rate.providerRate"
										:class="{ 'is-danger': [...(accomodationErrors['rates.'+index+'.providerRate'] || [])].length }" />
								</form-field>
							</div>
						</div>
						<div v-if="accomodation.hasSplitDates" class="columns">
							<div class="column">
								<form-field :label="`Rate (After ${$moment(accomodation.splitDate || group.eventDate).format('MM/DD/YYYY')})`"
									:errors="accomodationErrors['rates.'+index+'.splitRate'] || []" :required="true">
									<control-input v-model="rate.splitRate"
										:class="{ 'is-danger': [...(accomodationErrors['rates.'+index+'.splitRate'] || [])].length }" />
								</form-field>
							</div>
							<div class="column">
								<form-field label="Supplier Rate" :errors="accomodationErrors['rates.'+index+'.splitProviderRate'] || []">
									<control-input v-model="rate.splitProviderRate"
										:class="{ 'is-danger': [...(accomodationErrors['rates.'+index+'.splitProviderRate'] || [])].length }" />
								</form-field>
							</div>
						</div>
					</form-panel>
				</form-panel>

				<template v-if="!selectedRoom.adultsOnly">
					<form-panel label="Child Rates" class="is-borderless">
						<template v-slot:action>
							<control-button class="is-small is-link is-outlined" @click="accomodation.childRates.push({})">
								<i class="fas fa-plus"></i>
							</control-button>
						</template>
						<form-panel v-for="(childRate, index) in accomodation.childRates" :key="index" label="Rate/Child/Night">
							<template v-slot:action>
								<control-button class="is-small is-link is-outlined" @click="accomodation.childRates.splice(index, 1)">
									<i class="fas fa-minus"></i>
								</control-button>
							</template>
							<form-field
								:errors="[...(accomodationErrors['childRates.'+index+'.from'] || []), ...(accomodationErrors['childRates.'+index+'.to'] || [])]">
								<div class="columns is-multiline is-vcentered is-marginless">
									<span class="column is-narrow has-text-weight-bold" style="padding-left: 0;">Child from </span>
									<control-input v-model="childRate.from" class="column is-narrow" type="number" min="0" max="16"
										:class="{ 'is-danger': [...(accomodationErrors['childRates.'+index+'.from'] || [])].length }" />
									<span class="column is-narrow has-text-weight-bold"> to </span>
									<control-input v-model="childRate.to" class="column is-narrow" type="number" min="1" max="17"
										:class="{ 'is-danger': [...(accomodationErrors['childRates.'+index+'.to'] || [])].length }" />
									<span class="column is-narrow has-text-weight-bold"> years old.</span>
								</div>
							</form-field>
							<div class="columns">
								<div class="column">
									<form-field
										:label="'Rate' + (accomodation.hasSplitDates ? ` (Before ${$moment(accomodation.splitDate || group.eventDate).format('MM/DD/YYYY')})` : '')"
										:errors="accomodationErrors['childRates.'+index+'.rate'] || []" :required="true">
										<control-input v-model="childRate.rate"
											:class="{ 'is-danger': [...(accomodationErrors['childRates.'+index+'.rate'] || [])].length }" />
									</form-field>
								</div>
								<div class="column">
									<form-field label="Supplier Rate" :errors="accomodationErrors['childRates.'+index+'.providerRate'] || []">
										<control-input v-model="childRate.providerRate"
											:class="{ 'is-danger': [...(accomodationErrors['childRates.'+index+'.providerRate'] || [])].length }" />
									</form-field>
								</div>
							</div>
							<div v-if="accomodation.hasSplitDates" class="columns">
								<div class="column">
									<form-field :label="`Rate (After ${$moment(accomodation.splitDate || group.eventDate).format('MM/DD/YYYY')})`"
										:errors="accomodationErrors['childRates.'+index+'.splitRate'] || []" :required="true">
										<control-input v-model="childRate.splitRate"
											:class="{ 'is-danger': [...(accomodationErrors['childRates.'+index+'.splitRate'] || [])].length }" />
									</form-field>
								</div>
								<div class="column">
									<form-field label="Supplier Rate" :errors="accomodationErrors['childRates.'+index+'.splitProviderRate'] || []">
										<control-input v-model="childRate.splitProviderRate"
											:class="{ 'is-danger': [...(accomodationErrors['childRates.'+index+'.splitProviderRate'] || [])].length }" />
									</form-field>
								</div>
							</div>
						</form-panel>
					</form-panel>
				</template>
			</template>
		</div>

		<template v-if="selectedRoom">
			<template v-if="!selectedRoom.adultsOnly">
				<div class="columns in-form is-mobile">
					<div class="column">
						<form-field label="Ratio" :errors="accomodationErrors.minAdultsPerChild">
							<control-input v-model="accomodation.minAdultsPerChild" type="number" min="1" max="20"
								:placeholder="`Adults (${selectedRoom.minAdultsPerChild})`"
								:class="{ 'is-danger': (accomodationErrors.minAdultsPerChild || []).length }" />
						</form-field>
					</div>
					<div class="column">
						<form-field label=" " :errors="accomodationErrors.maxChildrenPerAdult">
							<control-input v-model="accomodation.maxChildrenPerAdult" type="number" min="1" max="20"
								:placeholder="`Children (${selectedRoom.maxChildrenPerAdult})`"
								:class="{ 'is-danger': (accomodationErrors.maxChildrenPerAdult || []).length }" />
						</form-field>
					</div>
				</div>
			</template>
		</template>

		<template v-slot:footer>
			<div class="field is-grouped">
				<control-button @click="close" :disabled="isLoading">Cancel</control-button>
				<control-button @click="create" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }" :disabled="isLoading">
					Submit
				</control-button>
			</div>
		</template>
	</modal>
</div>
</template>

<script>
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlSelect from '@dashboard/components/form/controls/Select';
import DatePicker from 'v-calendar/lib/components/date-picker.umd';
import FormField from '@dashboard/components/form/Field';
import FormPanel from '@dashboard/components/form/Panel';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlInput,
		ControlSelect,
		DatePicker,
		FormField,
		FormPanel,
		Modal,
	},
	props: {
		buttonClass: String,
		group: {
			type: Object,
			required: true
		},
		hotels: {
			type: Array,
			required: true
		}
	},
	data() {
		return {
			render: false,
			show: false,
			accomodation: {
				dates: {},
				hasSplitDates: false,
				rates: [{}],
				childRates: []
			},
			accomodationErrors: {},
			isLoading: false
		}
	},
	computed: {
		selectedHotel() {
			return this.hotels.find(hotel => hotel.id == this.accomodation.hotel) || null;
		},
		selectedRoom() {
			return this.selectedHotel ? this.selectedHotel.rooms.find(room => room.id == this.accomodation.room) : null;
		},
		accomodationDates: {
			get() {
				return this.accomodation.dates;
			},
			set(dates) {
				if (! dates) {
					this.accomodation.dates = {};
				}

				this.accomodation.dates.start = dates.start instanceof Date ? dates.start.toDateString() : null;
				this.accomodation.dates.end = dates.end instanceof Date ? dates.end.toDateString() : null;
			},
		},
		accomodationSplitDate: {
			get() {
				return this.accomodation.splitDate;
			},
			set(date) {
				this.accomodation.splitDate = date instanceof Date ? date.toDateString() : null;
			},
		},
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/groups/' + this.$route.params.group + '/accomodations', this.accomodation)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The room has been added.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.accomodationErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = false;
			});
		},
		close() {
			Object.assign(this.$data, this.$options.data.apply(this));
		}
	}
}
</script>
