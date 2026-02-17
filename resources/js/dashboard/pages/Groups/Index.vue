<template>
<card title="Groups">
	<template v-slot:action>
			<a @click.prevent="showEmailModal = true" class="button is-outlined is-primary is-inverted">
				<span class="icon"><i class="fas fa-envelope"></i></span>
				<span>Send Email</span>
			</a>		
			<create-group v-if="can.create" @created="fetchData()" :destinations="destinations" :agents="agents" :providers="providers"
				:insurance-rates="insuranceRates" button-class="is-outlined is-primary is-inverted" />
	</template>

	<template v-if="groups">
		<data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-select v-model="filters.fit" @input="filterData()" class="is-small"
						:options="[ { value: '', text: 'All Groups' }, {value: 1, text: 'FIT Groups Only'}, {value: 0, text: 'Non FIT Groups Only'} ]" default-value="" />
				</form-field>
			</data-filter>
			<data-filter>
				<form-field>
					<control-select v-model="filters.agent" @input="filterData()" class="is-small"
						:options="[ { value: '', text: 'All Agents' }, ...agents ]" default-value="" />
				</form-field>
			</data-filter>
			<data-filter>
				<form-field>
					<control-select v-model="filters.provider" @input="filterData()" class="is-small"
						:options="[ { value: '', text: 'All Suppliers' }, ...providers ]" default-value="" />
				</form-field>
			</data-filter>
			<data-filter>
				<form-field>
					<control-select v-model="filters.year" @input="filterData()" class="is-small"
						:options="[ { value: '', text: 'All Years' }, ...years]" default-value="" />
				</form-field>
			</data-filter>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search Groups" />
					<template v-slot:addon>
						<control-button @click="filterData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
					</template>
				</form-field>
			</data-filter>
		</data-filters>
		<data-filters>
			<data-filter>
				<form-field>
					<label class="checkbox">
						<input type="checkbox" :true-value="'true'" :false-value="'false'" v-model="filters.old" @change="filterData()">
						Show Old
					</label>
				</form-field>
			</data-filter>
		</data-filters>
		<data-table class="is-size-6" table-class="is-fullwidth"
			:columns="['Couple', 'Supplier / ID', 'Destination', 'Date', 'Balance Due', 'Agent', 'Type', 'Actions']">
			<template v-if="groups.length">
				<tr v-for="group in groups">
					<th>{{ group.brideFirstName }} {{ group.brideLastName }} &amp; {{ group.groomFirstName }} {{ group.groomLastName }} <i v-if="group.disableNotifications" class="fas fa-bell-slash"></i></th>
					<td>{{ group.provider.abbreviation }} / {{ group.providerId }}</td>
					<td>{{ group.destination.name }} - {{ group.hotels.map(hotel => hotel.name).join(' & ') || 'Pending Hotel' }}</td>
					<td>{{ $moment(group.eventDate).format('MMMM Do, YYYY') }}</td>
					<td>{{ $moment(group.dueDate).format('MMMM Do, YYYY') }}</td>
					<td>{{ group.agent.firstName }} {{ group.agent.lastName }}</td>
					<td>
						<span v-if="group.fit" class="has-text-weight-bold">FIT</span>
					</td>
					<td>
						<a v-if="group.can.view || group.can.update" class="table-action" @click.prevent="show(group.id)">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="group.can.delete" class="table-action" @click.prevent="deleteGroup = group">
							<i class="fas fa-trash"></i>
						</a>
						<table-actions v-if="group.can.viewAccomodations || group.can.viewBookings" :has-notifications="group.can.viewBookings && !!group.pendingBookings">
							<div class="dropdown-item" v-if="group.can.viewAccomodations">
								<router-link :to="{ name: 'accomodations', params: {group: group.id} }" class="table-action">
									View Accommodations
								</router-link>
							</div>
							<div class="dropdown-item" v-if="group.can.viewBookings">
								<router-link :to="{ name: 'bookings', params: {group: group.id} }" class="table-action">
									View Bookings
									<span v-if="group.pendingBookings" class="notification-counter is-text">
										{{ group.pendingBookings }}
									</span>
								</router-link>
							</div>
            	<div class="dropdown-item">
								<a :href="group.bookingsExportUrl" target="_blank" class="table-action">Export Bookings</a>
							</div>
							<div class="dropdown-item">
								<a :href="group.flightManifestsExportUrl" target="_blank" class="table-action">Export Flight Manifests</a>
							</div>
						</table-actions>
					</td>
				</tr>
			</template>
			<tr v-else>
				<td>No records found...</td>
			</tr>
		</data-table>
		<paginator v-if="meta.total > 10" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from"
			:to="meta.to" :total="meta.total" />

		<delete-group v-if="deleteGroup" :group="deleteGroup" @deleted="deleted" @canceled="deleteGroup = null" />
		<modal :is-active="showEmailModal" title="Send message" @hide="closeEmailModal">
			<form-field label="Subject" :errors="emailErrors.subject">
				<control-input v-model="email.subject" class="is-capitalized"
					:class="{ 'is-danger': (emailErrors.subject || []).length }" />
			</form-field>
			<form-field label="Message:" :errors="emailErrors.message">
				<control-textarea v-model="email.message" :class="{ 'is-danger': (emailErrors.message || []).length }" />
			</form-field>
			<template v-slot:footer>
				<div class="field is-grouped">
					<button @click="closeEmailModal" class="button is-dark is-outlined">Close</button>
					<control-button @click="sendBulkEmail" type="submit" class="is-primary" :class="{ 'is-loading': isLoading === 'sendEmail' }">Send</control-button>
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
import ControlSelect from '@dashboard/components/form/controls/Select';
import CreateGroup from '@dashboard/pages/Groups/Create';
import DataFilter from '@dashboard/components/table/Filter';
import DataFilters from '@dashboard/components/table/Filters';
import DataTable from '@dashboard/components/table/Table';
import DeleteGroup from '@dashboard/pages/Groups/Delete';
import FormField from '@dashboard/components/form/Field';
import Modal from '@dashboard/components/Modal';
import PaginationFilter from '@dashboard/components/pagination/Filter';
import Paginator from '@dashboard/components/pagination/Paginator';
import TableActions from '@dashboard/components/table/Actions';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		ControlSelect,
		CreateGroup,
		DataFilter,
		DataFilters,
		DataTable,
		DeleteGroup,
		FormField,
		Modal,
		PaginationFilter,
		Paginator,
		TableActions,
		ControlTextarea,
	},
	data() {
		return {
			groups: null,
			meta: {},
			can: {},
			filters: {
				paginate: 25,
				page: 1,
				year: '',
				agent: '',
				provider: '',
				search: '',
				old: false
			},
			email: {
				subject: '',
				message: ''
			},
			emailErrors: [],
			destinations: [],
			agents: [],
			providers: [],
			years: [],
			insuranceRates: [],
			airports: [],
			transfers: [],
			isLoading: '',
			showEmailModal: false,
			deleteGroup: null
		}
	},
	created() {
		this.filters = Object.assign({}, this.filters, this.$route.query);

		this.fetchData();
	},
	watch: {
		'$route'(to, from) {
			if (to.name === 'groups') {
				const searchChanged = to.query.search !== from.query.search;
				const pageChanged = to.query.page !== from.query.page;

				this.filters = Object.assign({}, this.filters, to.query);
				if (searchChanged || pageChanged || from.name !== 'groups') {
					this.fetchData();
				}
			}
		}
	},
	computed: {
		query() {
			return {
				...this.filters
			}
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/groups', {
					params: this.query
				})
				.then(response => {
					let data = response.data;
      		this.setBreadcrumbs();
					if(!!data.booking && !!data.group) {
						this.showBooking(data.group, data.booking);
					} else {
						this.groups = data.data;
						this.destinations = data.destinations;
						this.agents = data.agents;
						this.providers = data.providers;
						this.years = data.years;
						this.insuranceRates = data.insuranceRates;
						this.can = data.can;
						this.meta = data.meta;
					}
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
			this.$store.commit('breadcrumbs', [{
					label: 'Dashboard',
					route: 'home'
				},
				{
					label: 'Groups',
					route: 'groups'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'groups.show',
				params: {
					id: id
				}
			});
		},
        showBooking(group, booking) {
            this.$router.push({
                name: 'bookings.show',
                params: {
                    group: group,
                    id: booking
                }
            });
        },
		deleted() {
			this.deleteGroup = null;
			this.fetchData();
		},
		closeEmailModal() {
			this.showEmailModal = false;
			this.emailErrors = [];
		},
		sendBulkEmail() {
			this.isLoading = 'sendEmail';
			let request = this.$http.post('/send-bulk-email', {
				...this.email
			}).then(response => {
				this.$store.commit('notification', {
					type: 'success',
					message: 'The emails have been sent to all groups.'
				});
				this.emailErrors = [];
				this.email.subject = '';
				this.email.message = '';
				this.showEmailModal = false;
			}).catch(error => {
				if (error.response.status === 422) {
					this.emailErrors = error.response.data.errors;
				}
			});

			request.then(() => {
				this.isLoading = '';
			});
		},
	}
}
</script>
<style lang="scss">
@media only screen and (min-width: 769px) {
	.breadcrumb {
		margin-top: 1.5rem;
	}
}
</style>
