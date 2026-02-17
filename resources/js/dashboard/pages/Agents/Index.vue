<template>
<card title="Travel Agents">
	<template v-slot:action>
		<create-agent v-if="can.create && users.length" @created="fetchData()" button-class="is-outlined is-primary is-inverted" :users="users" />
	</template>

	<template v-if="agents">
		<data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<form-field>
					<control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search" />
					<template v-slot:addon>
						<control-button @click="filterData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
					</template>
				</form-field>
			</data-filter>
			<data-filter>
				<form-field>
					<control-select
						v-model="filters.status"
						@input="filterData()"
						class="is-small"
						default-value=""
						:options="[
							{ value: '', text: 'All' },
							{ value: 'active', text: 'Active' },
							{ value: 'inactive', text: 'Inactive' },
						]"
					/>
				</form-field>
			</data-filter>
		</data-filters>

		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Travel Agent', 'Status', 'Actions']">
			<template v-if="agents.length">
				<tr v-for="agent in agents">
					<th>{{ agent.firstName }} {{ agent.lastName }}</th>
					<td>
						<span class="tag" :class="agent.isActive ? 'is-success' : 'is-warning'">
							{{ agent.isActive ? 'Active' : 'Inactive' }}
						</span>
					</td>
					<td>
						<a v-if="agent.can.view || agent.can.update" class="table-action" @click.prevent="show(agent.id)" title="View/Edit">
							<i class="fas fa-info-circle"></i>
						</a>
						<table-actions v-if="can.viewGroups || can.viewBookings || agent.can.update">
							<div v-if="agent.can.update" class="dropdown-item">
								<a href="#" class="table-action" @click.prevent="toggleAgentStatus(agent)">
									{{ agent.isActive ? 'Disable Agent' : 'Enable Agent' }}
								</a>
							</div>
							<div v-if="can.viewGroups" class="dropdown-item">
								<router-link :to="{ name: 'groups', query: {agent: agent.id} }" class="table-action">
									View Groups
								</router-link>
							</div>
							<div v-if="can.viewBookings" class="dropdown-item">
								<router-link :to="{ name: 'individual-bookings', query: {agent: agent.id} }" class="table-action">
									View Individual Bookings
								</router-link>
							</div>
						</table-actions>
					</td>
				</tr>
			</template>
			<tr v-else>
				<td colspan="3">No records found...</td>
			</tr>
		</data-table>
		<paginator v-if="meta.total > 10" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from"
			:to="meta.to" :total="meta.total" />

		<delete-agent v-if="deleteAgent" :agent="deleteAgent" @deleted="deleted" @canceled="deleteAgent = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlSelect from '@dashboard/components/form/controls/Select';
import CreateAgent from '@dashboard/pages/Agents/Create';
import DataFilter from '@dashboard/components/table/Filter';
import DataFilters from '@dashboard/components/table/Filters';
import DataTable from '@dashboard/components/table/Table';
import DeleteAgent from '@dashboard/pages/Agents/Delete';
import FormField from '@dashboard/components/form/Field';
import PaginationFilter from '@dashboard/components/pagination/Filter';
import Paginator from '@dashboard/components/pagination/Paginator';
import TableActions from '@dashboard/components/table/Actions';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		ControlSelect,
		CreateAgent,
		DataFilter,
		DataFilters,
		DataTable,
		DeleteAgent,
		FormField,
		PaginationFilter,
		Paginator,
		TableActions,
	},
	data() {
		return {
			agents: [],
			users: [],
			meta: {},
			can: {},
			filters: {
				paginate: 10,
				page: 1,
				search: '',
				status: ''
			},
			deleteAgent: null
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
	methods: {
		fetchData() {
			this.$http.get('/agents' + this.query)
				.then(response => {
					this.agents = response.data.data;
					this.users = response.data.users;
					this.can = response.data.can;
					this.meta = response.data.meta;
      		this.setBreadcrumbs();
				});
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
		setBreadcrumbs() {
			this.$store.commit('breadcrumbs', [{
					label: 'Dashboard',
					route: 'home'
				},
				{
					label: 'Travel Agents',
					route: 'agents'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'agents.show',
				params: {
					id: id
				}
			});
		},
		deleted() {
			this.deleteAgent = null;
			this.fetchData();
		},
		toggleAgentStatus(agent) {
			const action = agent.isActive ? 'disable' : 'enable';
			const actionText = agent.isActive ? 'disable' : 'enable';
			
			if (confirm(`Are you sure you want to ${actionText} this agent?`)) {
				this.$http.post(`/agents/${agent.id}/${action}`)
					.then(() => {
						// Update the local agent status without refreshing the whole list
						agent.isActive = !agent.isActive;
						this.$toast.success(`Agent has been ${actionText}d successfully`);
					})
					.catch(error => {
						console.error('Error toggling agent status:', error);
						this.$toast.error(`Failed to ${actionText} agent. Please try again.`);
					});
			}
		}
	}
}
</script>
