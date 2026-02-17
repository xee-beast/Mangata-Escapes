<template>
<card v-if="savedAccomodation" :title="savedAccomodation.name">
	<template v-slot:action v-if="savedAccomodation.can.delete && !savedAccomodation.hasBooking">
		<a v-if="savedAccomodation.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash"></i></span>
		</a>
		<delete-accomodation v-if="showDelete" :accomodation="savedAccomodation" @deleted="deleted" @canceled="showDelete = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab v-if="!group.fit" @click="setTab('info')" :is-active="tabs.info">Accommodation</tab>
			<tab @click="setTab('rates')" :is-active="tabs.rates">{{ group.fit ? 'Ratios' : 'Rates' }}</tab>
		</tabs>
	</template>
	<template v-if="tabs.info && !group.fit">
		<form-field label="Inventory" :errors="accomodationErrors.inventory">
			<control-input v-model="accomodation.inventory" type="number" :readonly="readonly"
				:class="{ 'is-danger': (accomodationErrors.inventory || []).length }" />
		</form-field>
		<div class="columns">
			<div class="column">
				<form-field :errors="accomodationErrors.soldOut">
					<label class="checkbox">
						<input v-model="accomodation.soldOut" type="checkbox">
						Sold Out
					</label>
        </form-field>
			</div>
			<div class="column">
				<form-field :errors="accomodationErrors.isVisible">
					<control-switch v-model="accomodation.isVisible" :disabled="readonly">
						{{ accomodation.isVisible ? 'Visible' : 'Hidden' }} to Couples
					</control-switch>
        </form-field>
			</div>
		</div>
		<form-field label="Block Dates" :errors="[...(accomodationErrors['dates.start'] || []), ...(accomodationErrors['dates.end'] || [])]" :required="true">
			<date-picker
				v-model="accomodation.dates" is-range
				:popover="{ visibility: 'focus' }"
				:min-date="$moment(group.eventDate).subtract(14, 'days').toDate()"
				:max-date="$moment(group.eventDate).add(14, 'days').toDate()"
			>
				<template v-slot="{ inputValue, inputEvents }">
					<input
						:readonly="readonly"
						:value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
						v-on="inputEvents.start"
						:class="'input' + ([...(accomodationErrors['dates.start'] || []), ...(accomodationErrors['dates.end'] || [])].length ? ' is-danger' : '')"
					/>
				</template>
			</date-picker>
		</form-field>
		<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
	</template>

	<template v-if="tabs.rates">
		<div v-if="!group.fit">
			<form-field v-if="!readonly" :errors="ratesErrors.hasSplitDates">
				<label class="checkbox">
					<input v-model="rates.hasSplitDates" type="checkbox">
					Split Dates
				</label>
			</form-field>
			<form-field v-if="rates.hasSplitDates" label="Split Date" :errors="accomodationErrors.splitDate" :required="true">
				<date-picker
					v-model="rates.splitDate"
					:popover="{ visibility: 'focus' }"
					:min-date="$moment(savedAccomodation.startDate).toDate()"
					:max-date="$moment(savedAccomodation.endDate).toDate()"
				>
					<template v-slot="{ inputValue, inputEvents }">
						<input
							:readonly="readonly"
							:value="inputValue"
							v-on="inputEvents"
							:class="'input' + ((ratesErrors.splitDate || []).length ? ' is-danger' : '')"
						/>
					</template>
				</date-picker>
			</form-field>

			<form-panel label="Rates" class="is-borderless">
				<template v-slot:action>
					<control-button v-if="rates.rates.length > 1" class="is-small is-link is-outlined" @click="rates.rates.pop()">
						<i class="fas fa-minus"></i>
					</control-button>
					<control-button
						v-if="(savedAccomodation.minOccupants + rates.rates.length) <= (savedAccomodation.maxAdults || savedAccomodation.maxOccupants)"
						class="is-small is-link is-outlined" @click="rates.rates.push({})">
						<i class="fas fa-plus"></i>
					</control-button>
				</template>
				<form-panel v-for="(rate, index) in rates.rates" :key="index"
					:label="'Rate/Adult/Night for ' + (savedAccomodation.minOccupants + index) + ' in a room'">
					<div class="columns">
						<div class="column">
							<form-field
								:label="'Rate' + (rates.hasSplitDates ? ` (Before ${$moment(rates.splitDate || group.eventDate).format('MM/DD/YYYY')})` : '')"
								:errors="ratesErrors['rates.'+index+'.rate'] || []" :required="true">
								<control-input v-model="rate.rate" :class="{ 'is-danger': [...(ratesErrors['rates.'+index+'.rate'] || [])].length }"
									:readonly="readonly" />
							</form-field>
						</div>
						<div class="column">
							<form-field label="Supplier Rate" :errors="ratesErrors['rates.'+index+'.providerRate'] || []">
								<control-input v-model="rate.providerRate"
									:class="{ 'is-danger': [...(ratesErrors['rates.'+index+'.providerRate'] || [])].length }" :readonly="readonly" />
							</form-field>
						</div>
					</div>

					<template v-if="rates.hasSplitDates">
						<div class="columns">
							<div class="column">
								<form-field :label="`Rate (After ${$moment(rates.splitDate || group.eventDate).format('MM/DD/YYYY')})`"
									:errors="ratesErrors['rates.'+index+'.splitRate'] || []" :required="true">
									<control-input v-model="rate.splitRate"
										:class="{ 'is-danger': [...(ratesErrors['rates.'+index+'.splitRate'] || [])].length }" :readonly="readonly" />
								</form-field>
							</div>
							<div class="column">
								<form-field label="Supplier Rate" :errors="ratesErrors['rates.'+index+'.splitProviderRate'] || []">
									<control-input v-model="rate.splitProviderRate"
										:class="{ 'is-danger': [...(ratesErrors['rates.'+index+'.splitProviderRate'] || [])].length }"
										:readonly="readonly" />
								</form-field>
							</div>
						</div>
					</template>
				</form-panel>
			</form-panel>

			<template v-if="!savedAccomodation.adultsOnly">
				<form-panel label="Child Rates" class="is-borderless">
					<template v-slot:action>
						<control-button class="is-small is-link is-outlined" @click="rates.childRates.push({})">
							<i class="fas fa-plus"></i>
						</control-button>
					</template>
					<form-panel v-for="(childRate, index) in rates.childRates" :key="index" label="Rate/Child/Night">
						<input type="hidden" v-model="childRate.uuid" />
						<template v-slot:action>
							<control-button class="is-small is-link is-outlined" @click="rates.childRates.splice(index, 1)">
								<i class="fas fa-minus"></i>
							</control-button>
						</template>
						<form-field :errors="[...(ratesErrors['childRates.'+index+'.from'] || []), ...(ratesErrors['childRates.'+index+'.to'] || [])]">
							<div class="columns is-multiline is-vcentered is-marginless">
								<span class="column is-narrow has-text-weight-bold" style="padding-left: 0;">Child from </span>
								<control-input v-model="childRate.from" class="column is-narrow" type="number" min="0" max="16"
									:class="{ 'is-danger': [...(ratesErrors['childRates.'+index+'.from'] || [])].length }" :readonly="readonly" />
								<span class="column is-narrow has-text-weight-bold"> to </span>
								<control-input v-model="childRate.to" class="column is-narrow" type="number" min="1" max="17"
									:class="{ 'is-danger': [...(ratesErrors['childRates.'+index+'.to'] || [])].length }" :readonly="readonly" />
								<span class="column is-narrow has-text-weight-bold"> years old.</span>
							</div>
						</form-field>
						<div class="columns">
							<div class="column">
								<form-field
									:label="'Rate' + (rates.hasSplitDates ? ` (Before ${$moment(rates.splitDate || group.eventDate).format('MM/DD/YYYY')})` : '')"
									:errors="ratesErrors['childRates.'+index+'.rate'] || []" :required="true">
									<control-input v-model="childRate.rate"
										:class="{ 'is-danger': [...(ratesErrors['childRates.'+index+'.rate'] || [])].length }" :readonly="readonly" />
								</form-field>
							</div>
							<div class="column">
								<form-field label="Supplier Rate" :errors="ratesErrors['childRates.'+index+'.providerRate'] || []">
									<control-input v-model="childRate.providerRate"
										:class="{ 'is-danger': [...(ratesErrors['childRates.'+index+'.providerRate'] || [])].length }"
										:readonly="readonly" />
								</form-field>
							</div>
						</div>
						<div class="columns" v-if="rates.hasSplitDates">
							<div class="column">
								<form-field :label="`Rate (After ${$moment(rates.splitDate || group.eventDate).format('MM/DD/YYYY')})`"
									:errors="ratesErrors['childRates.'+index+'.splitRate'] || []" :required="true">
									<control-input v-model="childRate.splitRate"
										:class="{ 'is-danger': [...(ratesErrors['childRates.'+index+'.splitRate'] || [])].length }"
										:readonly="readonly" />
								</form-field>
							</div>
							<div class="column">
								<form-field label="Supplier Rate" :errors="ratesErrors['childRates.'+index+'.splitProviderRate'] || []">
									<control-input v-model="childRate.splitProviderRate"
										:class="{ 'is-danger': [...(ratesErrors['childRates.'+index+'.splitProviderRate'] || [])].length }"
										:readonly="readonly" />
								</form-field>
							</div>
						</div>
					</form-panel>
				</form-panel>
			</template>
		</div>

		<template v-if="!savedAccomodation.adultsOnly">
			<div class="columns in-form is-mobile">
				<div class="column">
					<form-field label="Adults/Children Ratio" :errors="ratesErrors.minAdultsPerChild">
						<control-input v-model="rates.minAdultsPerChild" type="number" min="1" max="20" placeholder="Adults"
							:class="{ 'is-danger': (accomodationErrors.minAdultsPerChild || []).length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field label=" " :errors="ratesErrors.maxChildrenPerAdult">
						<control-input v-model="rates.maxChildrenPerAdult" type="number" min="1" max="20" placeholder="Children"
							:class="{ 'is-danger': (ratesErrors.maxChildrenPerAdult || []).length }" />
					</form-field>
				</div>
			</div>
		</template>

		<control-button v-if="!readonly" @click="showSyncAlert = true" class="is-primary" :class="{ 'is-loading': isLoading === 'syncRates' }">Save</control-button>

		<modal @hide="showSyncAlert = false" :title="`Update ${savedAccomodation.name}`" :is-active="showSyncAlert">
			<p>
				Are you sure you want to update to these {{ this.group.fit ? 'ratios' : 'rates' }}?
				<br>
				This will affect any existing bookings as well as future bookings.
			</p>
			<template v-slot:footer>
				<div class="field is-grouped">
					<control-button @click="showSyncAlert = false">Cancel</control-button>
					<control-button @click="syncRates" class="is-primary">Yes</control-button>
				</div>
			</template>
		</modal>
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlSwitch from '@dashboard/components/form/controls/Switch';
import DatePicker from 'v-calendar/lib/components/date-picker.umd';
import DeleteAccomodation from '@dashboard/pages/Groups/Accomodations/Delete';
import FormField from '@dashboard/components/form/Field';
import FormPanel from '@dashboard/components/form/Panel';
import Modal from '@dashboard/components/Modal';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		DatePicker,
		DeleteAccomodation,
		FormField,
		FormPanel,
		Modal,
		ControlSwitch,
		Tab,
		Tabs,
	},
	data() {
		return {
			savedAccomodation: null,
			accomodation: {},
			accomodationErrors: {},
			rates: [],
			ratesErrors: {},
			group: {},
			showSyncAlert: false,
			showDelete: false,
			tabs: {
				info: true,
				rates: false
			},
			isLoading: ''
		}
	},
	created() {
		this.fetchData();
	},
	computed: {
		readonly() {
			return !this.savedAccomodation.can.update;
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/groups/' + this.$route.params.group + '/accomodations/' + this.$route.params.id)
				.then(response => {
					this.group = response.data.group;
					
					if (this.group.fit) {
						this.setTab('rates');
					}

					this.savedAccomodation = response.data.data;

					this.accomodation = {
						inventory: this.savedAccomodation.inventory,
						soldOut: this.savedAccomodation.soldOut,
						isVisible: this.savedAccomodation.isVisible !== undefined ? this.savedAccomodation.isVisible : true,
						dates: {
							start: this.savedAccomodation.startDate ? this.$moment(this.savedAccomodation.startDate).toDate() : null,
							end:  this.savedAccomodation.endDate ? this.$moment(this.savedAccomodation.endDate).toDate() : null
						}
					};

					this.rates = {
						hasSplitDates: this.savedAccomodation.splitDate != null,
						splitDate: (this.savedAccomodation.splitDate ? this.$moment(this.savedAccomodation.splitDate).toDate() : null),
						rates: this.savedAccomodation.rates.filter(rate => (
							rate.occupancy >= this.savedAccomodation.minOccupants &&
							rate.occupancy <= (this.savedAccomodation.maxAdults || this.savedAccomodation.maxOccupants)
						)),
						childRates: this.savedAccomodation.childRates,
						minAdultsPerChild: this.savedAccomodation.minAdultsPerChild,
						maxChildrenPerAdult: this.savedAccomodation.maxChildrenPerAdult
					};

					if (!this.group.fit) {
						const occupancy = this.rates.rates[0].occupancy;

						for (var i = this.savedAccomodation.minOccupants; i < occupancy; i++) {
							this.rates.rates.unshift({});
						}
					}

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
					label: 'Groups',
					route: 'groups'
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
				},
				{
					label: this.savedAccomodation.name,
					route: 'accomodations.show',
					params: {
						id: this.savedAccomodation.id
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

			if (this.accomodation.dates instanceof Object) {
				this.accomodation.dates.start = this.accomodation.dates.start instanceof Date ? this.accomodation.dates.start.toDateString() : null;
				this.accomodation.dates.end = this.accomodation.dates.end instanceof Date ? this.accomodation.dates.end.toDateString() : null;
			}

			let request = this.$http.put('/groups/' + this.$route.params.group + '/accomodations/' + this.$route.params.id, this.accomodation)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The accommodation has been updated.'
					});

					this.savedAccomodation = response.data.data;

					if (this.accomodation.dates.start || this.accomodation.dates.end) {
						this.accomodation.dates = {
							start: this.$moment(this.savedAccomodation.startDate).toDate(),
							end: this.$moment(this.savedAccomodation.endDate).toDate()
						};
					}

					this.accomodationErrors = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.accomodationErrors = error.response.data.errors;
					}

					if (this.accomodation.dates.start || this.accomodation.dates.end) {
						this.accomodation.dates = {
							start: this.$moment(this.accomodation.dates.start).toDate(),
							end: this.$moment(this.accomodation.dates.end).toDate(),
						};
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'accomodations',
				params: {
					group: this.$route.params.group
				}
			});
		},
		syncRates() {
			this.showSyncAlert = false;
			this.isLoading = 'syncRates';
			this.rates.splitDate = this.rates.splitDate instanceof Date ? this.rates.splitDate.toDateString() : null;

			let response = this.$http.patch('/groups/' + this.$route.params.group + '/accomodations/' + this.$route.params.id + '/rates', this.rates)
				.then(response => {
					let subject = this.group.fit ? 'ratios' : 'rates';
					
					this.$store.commit('notification', {
						type: 'success',
						message: 'The ' + subject + ' have been updated'
					});

					if (!this.group.fit) {
						this.savedAccomodation = response.data.data;

						if (this.savedAccomodation.splitDate) {
							this.rates.splitDate = this.$moment(this.savedAccomodation.splitDate).toDate();
						} else {
							this.rates.splitDate = null;
							
							this.rates.rates = this.rates.rates.map(rate => ({
								...rate,
								splitRate: null,
								splitProviderRate: null,
							}));
							
							this.rates.childRates = this.rates.childRates.map(childRate => ({
								...childRate,
								splitRate: null,
								splitProviderRate: null,
							}));
						}
					}

					this.ratesErrors = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.ratesErrors = error.response.data.errors;
					}
					
					if (!this.group.fit) {
						if (this.rates.splitDate) {
							this.rates.splitDate = this.$moment(this.rates.splitDate).toDate();
						} else {
							this.rates.splitDate = null;

							this.rates.rates = this.rates.rates.map(rate => ({
								...rate,
								splitRate: null,
								splitProviderRate: null,
							}));
							
							this.rates.childRates = this.rates.childRates.map(childRate => ({
								...childRate,
								splitRate: null,
								splitProviderRate: null,
							}));
						}
					}
				});

			response.then(() => {
				this.isLoading = '';
			});
		},
	}
}
</script>
