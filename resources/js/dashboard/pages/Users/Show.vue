<template>
<card v-if="savedUser" :title="savedUser.firstName + ' ' + savedUser.lastName">
	<template v-slot:action v-if="savedUser.can.delete">
		<a v-if="savedUser.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash"></i></span>
		</a>
		<delete-user v-if="showDelete" :user="savedUser" @deleted="deleted" @canceled="showDelete = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">User</tab>
			<tab v-if="savedUser.can.manageRoles" @click="setTab('roles')" :is-active="tabs.roles">Roles</tab>
			<tab v-if="savedUser.can.managePermissions" @click="setTab('permissions')" :is-active="tabs.permissions">Permissions</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<div class="columns">
			<div class="column">
				<form-field label="First Name" :errors="userErrors.firstName" :required="true">
					<control-input v-model="user.firstName" class="is-capitalized" :class="{ 'is-danger': (userErrors.firstName || []).length }"
						:readonly="readonly" />
				</form-field>
			</div>
			<div class="column">
				<form-field label="Last Name" :errors="userErrors.lastName" :required="true">
					<control-input v-model="user.lastName" class="is-capitalized" :class="{ 'is-danger': (userErrors.lastName || []).length }"
						:readonly="readonly" />
				</form-field>
			</div>
		</div>
		<div class="columns">
			<div class="column">
				<form-field label="Email" :errors="userErrors.email" :required="true">
					<control-input v-model="user.email" type="email" class="is-lowercase" :class="{ 'is-danger': (userErrors.email || []).length }"
						:readonly="readonly" />
				</form-field>
			</div>
			<div class="column">
				<form-field label="Username">
					<control-input v-model="savedUser.username" :readonly="true" />
				</form-field>
			</div>
		</div>
		<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
	</template>
	<template v-if="tabs.roles">
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['', 'Role', 'Description', 'Permissions']">
			<tr v-for="role in roles">
				<td><input v-model="checkedRoles" type="checkbox" :value="role.name"></td>
				<th class="is-capitalized">{{ role.name }}</th>
				<td>{{ role.description }}</td>
				<td class="is-capitalized">{{ role.permissions.map(permission => permission.name).join(', ') }}</td>
			</tr>
		</data-table>
		<p v-if="rolesErrors" class="help is-danger">There was a problem while syncing roles for this user.</p>
		<control-button @click="syncRoles" class="is-primary" :class="{ 'is-loading': isLoading === 'roles' }">Update</control-button>
	</template>
	<template v-if="tabs.permissions">
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['', 'Permission', 'Desription']">
			<tr v-for="permission in permissions">
				<td><input v-model="checkedPermissions" type="checkbox" :value="permission.name" :disabled="isInherited(permission.name)"></td>
				<th class="is-capitalized">{{ permission.name }}</th>
				<td>{{ permission.description }}</td>
			</tr>
		</data-table>
		<p v-if="permissionsErrors" class="help is-danger">There was a problem while syncing permissions for this user.</p>
		<control-button @click="syncPermissions" class="is-primary" :class="{ 'is-loading': isLoading === 'permissions' }">Update</control-button>
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import DataTable from '@dashboard/components/table/Table';
import DeleteUser from '@dashboard/pages/Users/Delete';
import FormField from '@dashboard/components/form/Field';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		DataTable,
		DeleteUser,
		FormField,
		Tab,
		Tabs,
	},
	data() {
		return {
			savedUser: null,
			user: {},
			userErrors: [],
			checkedRoles: [],
			checkedPermissions: [],
			roles: [],
			rolesErrors: false,
			permissions: [],
			permissionsErrors: false,
			showDelete: false,
			tabs: {
				info: true,
				roles: false,
				permissions: false
			},
			isLoading: ''
		}
	},
	created() {
		this.fetchData();
	},
	computed: {
		readonly() {
			return !this.savedUser.can.update;
		},
	},
	methods: {
		fetchData() {
			this.$http.get('/users/' + this.$route.params.id)
				.then(response => {
					this.savedUser = response.data.data;
					this.user = {
						firstName: this.savedUser.firstName,
						lastName: this.savedUser.lastName,
						email: this.savedUser.email
					};
					this.checkedRoles = this.savedUser.roles.map(role => role.name);

					this.checkedPermissions = this.savedUser.permissions.map(permission => permission.name);

					this.roles = response.data.roles;
					this.permissions = response.data.permissions;

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
					label: 'Users',
					route: 'users'
				},
				{
					label: this.savedUser.firstName + ' ' + this.savedUser.lastName,
					route: 'users.show',
					params: {
						id: this.savedUser.id
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
			let request = this.$http.put('/users/' + this.$route.params.id, this.user)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The user has been updated.'
					});
					this.savedUser = {
						...this.savedUser,
						...response.data.data
					};
					this.userErrors = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.userErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'users'
			});
		},
		syncRoles() {
			this.isLoading = 'roles';
			let request = this.$http.patch('/users/' + this.savedUser.id + '/roles', {
				roles: this.checkedRoles
			}).then(response => {
				this.$store.commit('notification', {
					type: 'success',
					message: 'The user\'s roles have been updated.'
				});
				this.savedUser.roles = response.data.data;

				this.rolesErrors = false;
			}).catch(error => {
				if (error.response.status === 422) {
					this.rolesErrors = true;
				}
			});

			request.then(() => {
				this.isLoading = '';
			});
		},
		syncPermissions() {
			this.isLoading = 'permissions';
			let request = this.$http.patch('/users/' + this.savedUser.id + '/permissions', {
				permissions: this.checkedPermissions
			}).then(response => {
				this.$store.commit('notification', {
					type: 'success',
					message: 'The user\'s permissions have been updated.'
				});
				this.savedUser.permissions = response.data.data;

				this.permissionsErrors = false;
			}).catch(error => {
				if (error.response.status === 422) {
					this.permissionsErrors = true;
				}
			});

			request.then(() => {
				this.isLoading = '';
			});
		},
		isInherited(check) {
			return this.savedUser.roles.some(role => role.permissions.some(permission => permission.name === check));
		}
	}
}
</script>
