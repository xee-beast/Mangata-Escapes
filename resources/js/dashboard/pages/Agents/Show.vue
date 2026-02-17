<template>
<card v-if="savedAgent" :title="savedAgent.firstName + ' ' + savedAgent.lastName">
	<template v-slot:action>
		<router-link v-if="savedAgent.can.viewGroups" :to="{ name: 'groups', query: {agent: savedAgent.id} }"
			class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-handshake"></i></span>
			<span>Groups</span>
		</router-link>
		<a v-if="savedAgent.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash"></i></span>
		</a>
		<delete-agent v-if="showDelete" :agent="savedAgent" @deleted="deleted" @canceled="showDelete = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">Travel Agent</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<form-field label="First Name" :errors="agentErrors.firstName" :required="true">
			<control-input v-model="agent.firstName" class="is-capitalized" :class="{ 'is-danger': (agentErrors.firstName || []).length }" :readonly="readonly" />
		</form-field>
		<form-field label="Last Name" :errors="agentErrors.lastName" :required="true">
			<control-input v-model="agent.lastName" class="is-capitalized" :class="{ 'is-danger': (agentErrors.lastName || []).length }" :readonly="readonly" />
		</form-field>
		<form-field label="Email" :errors="agentErrors.email" :required="true">
				<control-input v-model="agent.email" :class="{ 'is-danger': (agentErrors.email || []).length }" :readonly="readonly" />
			</form-field>
		<form-field label="Status" :errors="agentErrors.isActive">
			<div class="field">
				<control-switch v-model="agent.isActive" :disabled="readonly">
					{{ agent.isActive ? 'Active' : 'Inactive' }}
				</control-switch>
			</div>
		</form-field>
		<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlSwitch from '@dashboard/components/form/controls/Switch';
import DeleteAgent from '@dashboard/pages/Agents/Delete';
import FormField from '@dashboard/components/form/Field';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		ControlSwitch,
		DeleteAgent,
		FormField,
		Tab,
		Tabs,
	},
	data() {
		return {
			savedAgent: null,
			agent: {},
			agentErrors: {},
			showDelete: false,
			tabs: {
				info: true
			},
			isLoading: ''
		}
	},
	created() {
		this.fetchData();
	},
	computed: {
		readonly() {
			return !this.savedAgent.can.update;
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/agents/' + this.$route.params.id)
				.then(response => {
					this.savedAgent = response.data.data;
					this.agent = {
						firstName: this.savedAgent.firstName,
						lastName: this.savedAgent.lastName,
						email: this.savedAgent.email,
						isActive: this.savedAgent.isActive
					};

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
					label: 'Travel Agents',
					route: 'agents'
				},
				{
					label: this.savedAgent.firstName + ' ' + this.savedAgent.lastName,
					route: 'agents.show',
					params: {
						id: this.savedAgent.id
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
			let request = this.$http.put('/agents/' + this.$route.params.id, this.agent)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The agent has been updated.'
					});
					this.savedAgent = response.data.data;
					this.agentErrors = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.agentErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'agents'
			});
		}
	}
}
</script>
