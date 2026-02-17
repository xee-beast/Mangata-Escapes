<template>
<card v-if="savedModel" :title="savedModel.name">
	<template v-slot:action v-if="savedModel.can.delete">
		<a v-if="savedModel.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash"></i></span>
		</a>
		<delete-model v-if="showDelete" :model="savedModel" @deleted="deleted" @canceled="showDelete = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">Model</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<form-field label="Name" :errors="modelErrors.name">
			<control-input v-model="model.name" :class="{ 'is-danger': (modelErrors.name || []).length }" :readonly="readonly" />
		</form-field>
		<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import DeleteModel from '@dashboard/pages/Models/Delete';
import FormField from '@dashboard/components/form/Field';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		DeleteModel,
		FormField,
		Tab,
		Tabs,
	},
	data() {
		return {
			savedModel: null,
			model: {},
			modelErrors: {},
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
			return !this.savedModel.can.update;
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/models/' + this.$route.params.id)
				.then(response => {
					this.savedModel = response.data.data;
					this.model = {};

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
					label: 'Models',
					route: 'models'
				},
				{
					label: this.savedModel.name,
					route: 'models.show',
					params: {
						id: this.savedModel.id
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
			let request = this.$http.put('/models/' + this.$route.params.id, this.model)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The model has been updated.'
					});
					this.savedModel = response.data.data;
					this.modelErrors = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.modelErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'models'
			});
		}
	}
}
</script>
