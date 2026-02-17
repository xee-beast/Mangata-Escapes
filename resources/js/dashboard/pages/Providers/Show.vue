<template>
<card v-if="savedProvider" :title="savedProvider.name">
	<template v-slot:action v-if="savedProvider.can.viewInsuranceRates || savedProvider.can.delete">
		<router-link v-if="savedProvider.can.viewInsuranceRates" :to="{ name: 'insuranceRates', params: { provider: savedProvider.id }}" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-chart-line"></i></span>
			<span>Insurance Rates</span>
		</router-link>
		<a v-if="savedProvider.can.delete" @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash"></i></span>
		</a>
		<delete-provider v-if="showDelete" :provider="savedProvider" @deleted="deleted" @canceled="showDelete = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">Supplier</tab>
			<tab @click="setTab('specialists')" :is-active="tabs.specialists">Specialists</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<form-field label="Name" :errors="providerErrors.name" :required="true">
			<control-input v-model="provider.name" :class="{ 'is-danger': providerErrors.name && providerErrors.name.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Abbreviation" :errors="providerErrors.abbreviation" :required="true">
			<control-input v-model="provider.abbreviation" :class="{ 'is-danger': providerErrors.abbreviation && providerErrors.abbreviation.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Phone Number" :errors="providerErrors.phoneNumber" :required="true">
			<control-input v-model="provider.phoneNumber" :class="{ 'is-danger': providerErrors.phoneNumber && providerErrors.phoneNumber.length }" :readonly="readonly" />
		</form-field>
    <form-field label="Email" :errors="providerErrors.email" :required="true">
			<control-input v-model="provider.email" :class="{ 'is-danger': providerErrors.email && providerErrors.email.length }" :readonly="readonly" />
		</form-field>
		<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
	</template>
	<template v-if="tabs.specialists">
		<form-panel label="Specialists" class="is-borderless">
			<template v-slot:action>
				<control-button v-if="!readonly" class="is-small is-link is-outlined" @click="specialists.push({name: '', email: ''})">
					<i class="fas fa-plus"></i>
				</control-button>
			</template>
			<form-panel v-for="(specialist, index) in specialists" :key="index">
				<template v-if="!readonly" v-slot:action>
					<control-button class="is-small is-link is-outlined" @click="specialist.leadProvidersCount > 0 ? openSpecialistDeleteModal(index) : specialists.splice(index, 1)">
						<i class="fas fa-minus"></i>
					</control-button>
				</template>
				<div class="columns">
					<div class="column">
						<form-field label="Name" :errors="specialistsErrors['specialists.' + index + '.name']" :required="true">
							<control-input v-model="specialist.name" :readonly="readonly" :class="{ 'is-danger': specialistsErrors['specialists.' + index + '.name'] && specialistsErrors['specialists.' + index + '.name'].length }" />
						</form-field>
					</div>
					<div class="column">
						<form-field label="Email" :errors="specialistsErrors['specialists.' + index + '.email']" :required="true">
							<control-input v-model="specialist.email" :readonly="readonly" :class="{ 'is-danger': specialistsErrors['specialists.' + index + '.email'] && specialistsErrors['specialists.' + index + '.email'].length }" />
						</form-field>
					</div>
				</div>
			</form-panel>
			<control-button v-if="!readonly" @click="updateSpecialists" class="is-primary" :class="{ 'is-loading': isLoading === 'updateSpecialists' }">Save</control-button>
		</form-panel>
		<modal @hide="closeSpecialistDeleteModal" title="Delete Specialist" :is-active="showSpecialistDeleteModal">
			<p>
				This specialist is currently assigned to one or more leads. If you delete them, the assigned specialist will be removed from those leads. Are you sure you want to continue?
			</p>
			<template v-slot:footer>
				<div class="field is-grouped">
					<control-button @click="closeSpecialistDeleteModal">Cancel</control-button>
					<control-button @click="deleteSpecialist" type="submit" class="is-primary">Yes</control-button>
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
import DeleteProvider from '@dashboard/pages/Providers/Delete';
import FormField from '@dashboard/components/form/Field';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';
import FormPanel from '@dashboard/components/form/Panel';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		DeleteProvider,
		FormField,
		Tab,
		Tabs,
		FormPanel,
		Modal,
	},
	data() {
		return {
			savedProvider: null,
			provider: {},
			providerErrors: {},
			specialists: [],
			specialistsErrors: {},
			showDelete: false,
			tabs: {
				info: true,
				specialists: false,
			},
			showSpecialistDeleteModal: false,
			specialistToDeleteIndex: null,
			isLoading: '',
		}
	},
	created() {
		this.fetchData();
	},
	computed: {
		readonly() {
			return !this.savedProvider.can.update;
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/providers/' + this.$route.params.id)
				.then(response => {
					this.savedProvider = response.data.data;
					this.specialists = this.savedProvider.specialists;

					this.provider = {
						name: this.savedProvider.name,
						abbreviation: this.savedProvider.abbreviation,
						phoneNumber: this.savedProvider.phoneNumber,
						email: this.savedProvider.email
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
					label: 'Suppliers',
					route: 'providers'
				},
				{
					label: this.savedProvider.name,
					route: 'providers.show',
					params: {
						id: this.savedProvider.id
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

			let request = this.$http.put('/providers/' + this.$route.params.id, this.provider)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The supplier has been updated.'
					});

					this.savedProvider = response.data.data;
					this.providerErrors = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.providerErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		openSpecialistDeleteModal(index) {
			this.specialistToDeleteIndex = index;
			this.showSpecialistDeleteModal = true;
		},
		closeSpecialistDeleteModal() {
			this.specialistToDeleteIndex = null;
			this.showSpecialistDeleteModal = false;
		},
		deleteSpecialist() {
			this.specialists.splice(this.specialistToDeleteIndex, 1);
			this.closeSpecialistDeleteModal();
		},
		updateSpecialists() {
			this.isLoading = 'updateSpecialists';

			let request = this.$http.post('/providers/' + this.$route.params.id + '/specialists', {'specialists': this.specialists})
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The specialists have been updated.'
					});

					this.specialistsErrors = {};
				}).catch(error => {
					if (error.response.status === 422) {
						this.specialistsErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'providers'
			});
		}
	}
}
</script>
