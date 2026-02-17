<template>
<card v-if="savedRole" :title="savedRole.name">
	<template v-slot:action v-if="savedRole.can.delete">
		<a v-if="savedRole.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash"></i></span>
		</a>
		<delete-role v-if="showDelete" :role="savedRole" @deleted="deleted" @canceled="showDelete = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">Role</tab>
			<tab v-if="!readonly" @click="setTab('permissions')" :is-active="tabs.permissions">Permissions</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<form-field label="Name" :errors="roleErrors.name">
			<control-input v-model="role.name" :class="{ 'is-danger': roleErrors.name && roleErrors.name.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Description" :errors="roleErrors.description">
			<control-textarea v-model="role.description" :class="{ 'is-danger': roleErrors.description && roleErrors.description.length }"
				:readonly="readonly" />
		</form-field>
		<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
	</template>
	<template v-if="tabs.permissions">
		<data-table class="is-size-6" table-class="is-fullwidth" :columns="['', 'Permission', 'Desription']" :errors="roleErrors.permissions">
			<tr v-for="permission in permissions">
				<td><input v-model="checkedPermissions" type="checkbox" :value="permission.name"></td>
				<th class="is-capitalized">{{ permission.name }}</th>
				<td>{{ permission.description }}</td>
			</tr>
		</data-table>
		<control-button @click="syncPermissions" class="is-primary" :class="{ 'is-loading': isLoading === 'permissions' }">Update</control-button>
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';
import DataTable from '@dashboard/components/table/Table';
import DeleteRole from '@dashboard/pages/Roles/Delete';
import FormField from '@dashboard/components/form/Field';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		ControlTextarea,
		DataTable,
		DeleteRole,
		FormField,
		Tab,
		Tabs,
	},
	data() {
		return {
			savedRole: null,
			role: {},
			roleErrors: {},
			permissions: [],
			checkedPermissions: [],
			showDelete: false,
			tabs: {
				info: true,
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
			return !this.savedRole.can.update;
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/roles/' + this.$route.params.id)
				.then(response => {
					this.savedRole = response.data.data;
					this.permissions = response.data.permissions;
					this.role = {
						name: this.savedRole.name,
						description: this.savedRole.description,
						permissions: this.savedRole.permissions.map(permission => permission.name)
					};
					this.checkedPermissions = this.savedRole.permissions.map(permission => permission.name);

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
					label: 'Roles',
					route: 'roles'
				},
				{
					label: this.savedRole.name,
					route: 'roles.show',
					params: {
						id: this.savedRole.id
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
			let request = this.$http.put('/roles/' + this.$route.params.id, this.role)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The role has been updated.'
					});
					this.savedRole = response.data.data;
					this.roleErrors = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.roleErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		syncPermissions() {
			this.isLoading = 'permissions';

			let request = this.$http.patch('/roles/' + this.savedRole.id + '/permissions', {
				permissions: this.checkedPermissions
			}).then(response => {
				this.$store.commit('notification', {
					type: 'success',
					message: 'The role\'s permissions have been updated.'
				});
				this.savedRole.permissions = response.data.data;

				this.$delete(this.roleErrors, 'permissions');
			}).catch(error => {
				if (error.response.status === 422)
					this.$set(this.roleErrors, 'permissions', error.response.data.errors.permissions);
			});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'roles'
			});
		}
	}
}
</script>
