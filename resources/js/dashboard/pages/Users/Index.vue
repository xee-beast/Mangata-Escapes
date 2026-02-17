<template>
<card title="Users">
	<template v-slot:action>
		<create-user v-if="can.create" @created="fetchData()" button-class="is-outlined is-primary is-inverted" />
	</template>

	<template v-if="users">
		<data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
			<data-filter>
				<control-select v-model="filters.role" @input="filterData()" class="is-small" control-class="is-capitalized" :options="[ { value: '', text: 'All Roles' }, ...roles ]" default-value="" />
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

		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Name', 'Username', 'Status', 'Roles', 'Actions']">
			<template v-if="users.length">
				<tr v-for="user in users">
					<th>{{ user.firstName }} {{ user.lastName }}</th>
					<td>{{ user.username }}</td>
					<td>{{ user.verifiedAt ? 'Verified' : 'Pending Verification' }}</td>
					<td class="is-capitalized">
						{{ user.roles.length ? user.roles.map(role => role.name).join(', ') : 'Default User' }}
					</td>
					<td>
						<a v-if="user.can.view || user.can.update" class="table-action" @click.prevent="show(user.id)" title="Update User">
							<i class="fas fa-info-circle"></i>
						</a>
						<a v-if="user.can.changePasswords && user != changePassword" class="table-action" @click.prevent="changePassword = user" style="padding: 0 5px;" title="Change Password">
							<i class="fas fa-info"></i>
						</a>
						<a v-if="user.can.delete" class="table-action" @click.prevent="deleteUser = user" title="Delete User">
							<i class="fas fa-trash"></i>
						</a>
					</td>
				</tr>
			</template>
			<tr v-else>
				<td>No records found...</td>
			</tr>
		</data-table>
		<paginator v-if="meta.total > 10" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from"
			:to="meta.to" :total="meta.total" />


		<delete-user v-if="deleteUser" :user="deleteUser" @deleted="deleted" @canceled="deleteUser = null" />

		<change-password v-if="changePassword" :user="changePassword" @changed="changed" @canceled="changePassword = null" />
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlSelect from '@dashboard/components/form/controls/Select';
import CreateUser from '@dashboard/pages/Users/Create';
import DataFilter from '@dashboard/components/table/Filter';
import DataFilters from '@dashboard/components/table/Filters';
import DataTable from '@dashboard/components/table/Table';
import DeleteUser from '@dashboard/pages/Users/Delete';
import FormField from '@dashboard/components/form/Field';
import PaginationFilter from '@dashboard/components/pagination/Filter';
import Paginator from '@dashboard/components/pagination/Paginator';
import ChangePassword from '@dashboard/pages/Users/ChangePassword';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		ControlSelect,
		CreateUser,
		DataFilter,
		DataFilters,
		DataTable,
		DeleteUser,
		FormField,
		PaginationFilter,
		Paginator,
		ChangePassword,
	},
	data() {
		return {
			users: [],
			roles: [],
			meta: {},
			can: {},
			filters: {
				paginate: 10,
				page: 1
			},
			deleteUser: null,
			changePassword: null
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
			this.$http.get('/users' + this.query)
				.then(response => {
					this.users = response.data.data;
					this.roles = response.data.roles.map(role => ({
						value: role.id,
						text: role.name
					}));
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
					label: 'Users',
					route: 'users'
				}
			]);
		},
		show(id) {
			this.$router.push({
				name: 'users.show',
				params: {
					id: id
				}
			});
		},
		deleted() {
			this.deleteUser = null;
			this.fetchData();
		},
		changed() {
			this.changePassword = null;
		}
	}
}
</script>
