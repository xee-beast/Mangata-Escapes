<template>
	<card title="Leads">
		<template v-slot:action>
			<create-lead v-if="can.create" @created="fetchData()" :referral-source-options="referralSourceOptions" :contacted-us-options="contactedUsOptions" button-class="is-outlined is-primary is-inverted" />
		</template>
		<template v-slot:tabs>
			<tabs class="is-boxed">
				<tab @click="setTab('leads')" :is-active="tabs.leads">Leads</tab>
				<tab v-if="can.viewAllLeads" @click="setTab('options')" :is-active="tabs.options">Options</tab>
			</tabs>
		</template>
		<template v-if="tabs.leads">
			<data-filters>
				<template v-slot:left>
					<data-filter v-if="meta.total > 10">
						<pagination-filter v-model="filters.paginate" @input="filterData()" />
					</data-filter>
				</template>
				<data-filter v-if="can.viewAllLeads">
					<control-select v-model="filters.travelAgent" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Travel Agents' }, ...travelAgents ]" default-value="" />
				</data-filter>
				<data-filter>
					<control-select v-model="filters.status" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Statuses' }, ...statusOptions ]" default-value="" />
				</data-filter>
				<data-filter>
					<control-select v-model="filters.isFit" @input="filterData()" class="is-small" :options="[ { value: '', text: 'Both FIT & Non FIT Leads' }, {value: 1, text: 'FIT Leads Only'}, {value: 0, text: 'Non FIT Leads Only'} ]" default-value="" />
				</data-filter>
				<data-filter>
					<control-select v-model="filters.isCanadian" @input="filterData()" class="is-small" :options="[ { value: '', text: 'Both Canadian & Non Canadian Leads' }, {value: 1, text: 'Canadian Leads Only'}, {value: 0, text: 'Non Canadian Leads Only'} ]" default-value="" />
				</data-filter>
				<data-filter>
					<control-select v-model="filters.weddingYear" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Wedding Years' }, ...weddingYears]" default-value="" />
				</data-filter>
				<data-filter>
					<control-select v-model="filters.contactedUsYear" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Contacted Us Years' }, ...contactedUsYears]" default-value="" />
				</data-filter>
				<data-filter>
					<control-select v-model="filters.departure" @input="filterData()" class="is-small" :options="[ { value: '', text: 'All Countries' }, {value: 'US', text: 'US'}, {value: 'Canada', text: 'Canada'}, {value: 'Other', text: 'Other'} ]" default-value="" />
				</data-filter>
				<data-filter>
					<form-field>
						<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search" />
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
							<input type="checkbox" :true-value="'true'" :false-value="'false'" v-model="filters.active" @change="filterData()">
							Active Leads Only
						</label>
					</form-field>
				</data-filter>
			</data-filters>
			<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Bride Name', 'Groom Name', 'Email', 'Phone', 'Wedding Date', 'Contacted Us Date', 'Travel Agent', 'Status', 'Type', 'Departure', 'Message', 'Notes', 'Actions']">
				<template v-if="leads.length">
					<tr v-for="lead in leads" :key="lead.id">
						<th>{{ lead.brideName ? lead.brideName : '-' }}</th>
						<th>{{ lead.groomName ? lead.groomName : '-' }}</th>
						<td>{{ lead.email }}</td>
						<td>{{ lead.phone }} <span v-if="lead.textAgreement"><i class="fas fa-comment-alt"></i></span></td>
						<td>{{ lead.weddingDate ? $moment.utc(lead.weddingDate).format('MMM DD, YYYY') : '-' }}</td>
						<td>{{ lead.contactedUsDate ? $moment.utc(lead.contactedUsDate).format('MMM DD, YYYY') : '-' }}</td>
						<td>
							{{ lead.travelAgent ? lead.travelAgent.firstName + ' ' + lead.travelAgent.lastName : '-' }} <br>
							<small>{{ lead.assignedAt }}</small>
						</td>
						<td>
							<span class="tag" :class="statusClass(lead.status)">
								{{ lead.status }}
							</span>
						</td>
						<td>
							<span v-if="lead.isFit && lead.isCanadian">FIT | Canadian</span>
							<span v-else-if="lead.isFit">FIT</span>
							<span v-else-if="lead.isCanadian">Canadian</span>
							<span v-else>-</span>
						</td>
						<td>{{ lead.departure }}</td>
						<td>
							<a v-if="lead.message" @click.prevent="showMessage(`Lead #${lead.id} - Message`, lead.message)">
								<i class="fas fa-eye"></i>
							</a>
							<template v-else>
								-
							</template>
						</td>
						<td>
							<a @click.prevent="showMessage(`Lead #${lead.id} - Notes`, lead.notes, lead.id, lead.can.update)">
								<i v-if="lead.notes" class="fas fa-eye"></i>
								<i v-else class="fas fa-low-vision"></i>
							</a>
						</td>
						<td>
							<a v-if="lead.can.view || lead.can.update" class="table-action" @click.prevent="show(lead.id)">
								<i class="fas fa-info-circle"></i>
							</a>
							<a v-if="lead.can.delete" class="table-action" @click.prevent="deleteLead = lead">
								<i class="fas fa-trash"></i>
							</a>
						</td>
					</tr>
				</template>
				<tr v-else>
					<td>No records found...</td>
				</tr>
			</data-table>
			<paginator v-if="meta.total > 10" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from" :to="meta.to" :total="meta.total" />
			<delete-lead v-if="deleteLead" :lead="deleteLead" @deleted="deleted" @canceled="deleteLead = null" />
			<modal :title="message.title" :is-active="message.show" @hide="hideMessage()">
				<form-field :errors="message.notesErrors" v-if="message.edit && message.leadId">
					<control-textarea v-model="message.body" :readonly="!message.edit || !message.leadId" :class="{ 'is-danger': (message.notesErrors || []).length }" />
				</form-field>
				<div v-if="message.edit && message.leadId" class="buttons mt-2">
					<control-button class="is-primary" :class="{ 'is-loading': isLoading === 'updateNotes' }" @click="updateNotes">Update</control-button>
					<control-button @click="hideMessage()">Cancel</control-button>
				</div>
				<template v-else>
					{{ message.body || '-' }}
				</template>
			</modal>
		</template>
		<template v-if="tabs.options && can.viewAllLeads">
			<form-panel label="Heard About Us Options">
        <template v-slot:action>
					<control-button class="is-small is-link is-outlined" @click="referralSourceOptions.push({ option: '' })">
						<i class="fas fa-plus"></i>
					</control-button>
        </template>
				<form-field :errors="optionErrors.referralSourceOptions"></form-field>
				<div v-for="(referralSourceOption, index) in referralSourceOptions" :key="index" class="field is-horizontal is-borderless">
					<div class="control is-flex is-align-items-center">
						<form-field class="control" :errors="optionErrors['referralSourceOptions.' + index + '.option']">
							<control-input v-model="referralSourceOption.option" :class="{ 'is-danger': (optionErrors['referralSourceOptions.' + index + '.option'] || []).length }" />		
						</form-field>
						<control-button class="is-small is-link is-outlined" style="margin-left: 10px;" @click="referralSourceOptions.splice(index, 1)">
							<i class="fas fa-minus"></i>
						</control-button>
					</div>
				</div>
			</form-panel>
			<form-panel label="Contacted Us Options">
        <template v-slot:action>
					<control-button class="is-small is-link is-outlined" @click="contactedUsOptions.push({ option: '' })">
						<i class="fas fa-plus"></i>
					</control-button>
        </template>
				<form-field :errors="optionErrors.contactedUsOptions"></form-field>
				<div v-for="(contactedUsOption, index) in contactedUsOptions" :key="index" class="field is-horizontal is-borderless">
					<div class="control is-flex is-align-items-center">
						<form-field class="control" :errors="optionErrors['contactedUsOptions.' + index + '.option']">
							<control-input v-model="contactedUsOption.option" :class="{ 'is-danger': (optionErrors['contactedUsOptions.' + index + '.option'] || []).length }" />
						</form-field>
						<control-button class="is-small is-link is-outlined" style="margin-left: 10px;" @click="contactedUsOptions.splice(index, 1)">
							<i class="fas fa-minus"></i>
						</control-button>
					</div>
				</div>
			</form-panel>
			<control-button @click="syncOptions" class="is-primary" :class="{ 'is-loading': isLoading === 'syncOptions' }">Save</control-button>
		</template>
	</card>
</template>

<script>
	import Card from '@dashboard/components/Card';
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import CreateLead from '@dashboard/pages/Leads/Create';
	import DataFilter from '@dashboard/components/table/Filter';
	import DataFilters from '@dashboard/components/table/Filters';
	import DataTable from '@dashboard/components/table/Table';
	import Tab from '@dashboard/components/tabs/Tab';
	import Tabs from '@dashboard/components/tabs/Tabs';
	import DeleteLead from '@dashboard/pages/Leads/Delete';
	import FormField from '@dashboard/components/form/Field';
	import PaginationFilter from '@dashboard/components/pagination/Filter';
	import Paginator from '@dashboard/components/pagination/Paginator';
	import Modal from '@dashboard/components/Modal';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import FormPanel from '@dashboard/components/form/Panel';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';

	export default {
		components: {
			Card,
			ControlButton,
			ControlInput,
			CreateLead,
			DataFilter,
			DataFilters,
			DataTable,
			Tab,
			Tabs,
			DeleteLead,
			FormField,
			PaginationFilter,
			Paginator,
			Modal,
			ControlSelect,
			FormPanel,
			ControlTextarea,
		},
		data() {
			return {
				tabs: {
					leads: true,
					options: false
				},
				leads: [],
				meta: {},
				can: {},
				filters: {
					active: true,
					paginate: 10,
					page: 1
				},
				weddingYears: [],
				contactedUsYears: [],
				deleteLead: null,
				message: {
					title: '',
					body: '',
					leadId: null,
					edit: false,
					show: false,
					notesErrors: [],
				},
				travelAgents: [],
				statusOptions: [
					{ value: 'Unassigned', text: 'Unassigned' },
					{ value: 'Assigned', text: 'Assigned' },
					{ value: 'Pending Rates', text: 'Pending Rates' },
					{ value: 'Received Rates', text: 'Received Rates' },
					{ value: 'Pending K', text: 'Pending K' },
					{ value: 'Pending Deposit', text: 'Pending Deposit' },
					{ value: 'Signed K', text: 'Signed K' },
					{ value: 'Declined', text: 'Declined' },
				],
				referralSourceOptions: [],
				contactedUsOptions: [],
				optionErrors: [],
				isLoading: '',
			}
		},
		created() {
			this.filters = Object.assign({}, this.filters, this.$route.query);

			this.fetchData();
		},
		computed: {
			query() {
				return '?' + Object.keys(this.filters).map(key => key + '=' + this.filters[key]).join('&');
			}
		},
    watch: {
      '$route'(to, from) {
        if (to.name === 'leads') {
          const searchChanged = to.query.search !== from.query.search;
          const pageChanged = to.query.page !== from.query.page;

          this.filters = Object.assign({}, this.filters, to.query);
          if (searchChanged || pageChanged || from.name !== 'leads') {
            this.fetchData();
          }
        }
      }
    },
		methods: {
			fetchData() {
				this.$http.get('/leads' + this.query)
					.then(response => {
						this.leads = response.data.data;
						this.travelAgents = response.data.travelAgents;
						this.can = response.data.can;
						this.meta = response.data.meta;
						this.weddingYears = response.data.weddingYears;
						this.contactedUsYears = response.data.contactedUsYears;
						this.referralSourceOptions = response.data.referralSourceOptions;
						this.contactedUsOptions = response.data.contactedUsOptions;
      			this.setBreadcrumbs();
					});
			},
			statusClass(status) {
				switch (status) {
					case 'Unassigned': return 'is-warning';
					case 'Assigned': return 'is-white';
					case 'Pending Rates': return 'has-background-sandstone';
					case 'Received Rates': return 'has-background-charcoal has-text-white';
					case 'Pending K': return 'has-background-dusty-rose has-text-white';
					case 'Pending Deposit': return 'has-background-mauve has-text-white';
					case 'Signed K': return 'is-success has-text-black';
					case 'Declined': return 'is-light';
					default: return 'is-light';
				}
			},
			filterData(page = '1') {
				this.filters.page = page;

				if (JSON.stringify(this.$route.query) !== JSON.stringify(this.filters)) {
					this.$router.replace({
						query: this.filters
					});

					this.fetchData();
				}
			},
			setTab(tab) {
				Object.keys(this.tabs).forEach(key => this.tabs[key] = false);
				this.tabs[tab] = true;
			},
			setBreadcrumbs() {
				this.$store.commit('breadcrumbs', [{
						label: 'Dashboard',
						route: 'home'
					},
					{
						label: 'Leads',
						route: 'leads'
					}
				]);
			},
			show(id) {
				this.$router.push({
					name: 'leads.show',
					params: {
						id: id
					}
				});
			},
			deleted() {
				this.deleteLead = null;

				this.fetchData();
			},
			showMessage(title, body, leadId = null, edit = false) {
				this.message.title = title;
				this.message.body = body;
				this.message.leadId = leadId;
				this.message.edit = edit;
				this.message.show = true;
			},
			hideMessage() {
				this.message.title = '';
				this.message.body = '';
				this.message.leadId = null;
				this.message.edit = false;
				this.message.show = false;
				this.message.notesErrors = [];
			},
			updateNotes() {
				this.isLoading = 'updateNotes';

				this.$http.patch(`/leads/${this.message.leadId}/update-notes`, {
						notes: this.message.body
					}).then((response) => {
						const lead = this.leads.find(l => l.id === this.message.leadId);
						if (lead) lead.notes = response.data.notes;

						this.$store.commit('notification', {
							type: 'success',
							message: 'Notes updated successfully.'
						});

						this.hideMessage();
					})
					.catch(error => {
						if (error.response && error.response.status === 422) {
							this.message.notesErrors = error.response.data.errors.notes;
						}
					})
					.finally(() => {
						this.isLoading = '';
					});
			},
			syncOptions() {
				this.isLoading = 'syncOptions';

				let request = this.$http.patch('/sync-lead-options', {
						referralSourceOptions: this.referralSourceOptions,
						contactedUsOptions: this.contactedUsOptions,
					}).then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The heard about us options and contacted us options have been updated.'
						});

						this.optionErrors = [];
					}).catch(error => {
						if (error.response.status === 422) {
							this.optionErrors = error.response.data.errors;
						}
					});

				request.then(() => {
					this.isLoading = '';
				});
			}
		}
	}
</script>
