<template>
<card v-if="savedTransfer" :title="savedTransfer.name">
	<template v-slot:action v-if="savedTransfer.can.delete">
		<a @click.prevent="showDelete = true" class="button is-outlined is-primary is-inverted">
			<span class="icon"><i class="fas fa-trash"></i></span>
		</a>
		<delete-transfer v-if="showDelete" :transfer="savedTransfer" @deleted="deleted" @canceled="showDelete = false" />
	</template>
	<template v-slot:tabs>
		<tabs class="is-boxed">
			<tab @click="setTab('info')" :is-active="tabs.info">Transfer Provider</tab>
		</tabs>
	</template>
	<template v-if="tabs.info">
		<form-field label="Name" :errors="transferErrors.name" :required="true">
			<control-input v-model="transfer.name" :class="{ 'is-danger': transferErrors.name && transferErrors.name.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Email" :errors="transferErrors.email" :required="true">
			<control-input v-model="transfer.email" :class="{ 'is-danger': transferErrors.email && transferErrors.email.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Primary Phone Number" :errors="transferErrors.primaryPhoneNumber" :required="true">
			<control-input v-model="transfer.primaryPhoneNumber" :class="{ 'is-danger': transferErrors.primaryPhoneNumber && transferErrors.primaryPhoneNumber.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Secondary Phone Number Label" :errors="transferErrors.secondaryPhoneNumberLabel">
			<control-input v-model="transfer.secondaryPhoneNumberLabel" :class="{ 'is-danger': transferErrors.secondaryPhoneNumberLabel && transferErrors.secondaryPhoneNumberLabel.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Secondary Phone Number Value" :errors="transferErrors.secondaryPhoneNumberValue">
			<control-input v-model="transfer.secondaryPhoneNumberValue" :class="{ 'is-danger': transferErrors.secondaryPhoneNumberValue && transferErrors.secondaryPhoneNumberValue.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Whatsapp Number" :errors="transferErrors.whatsappNumber">
			<control-input v-model="transfer.whatsappNumber" :class="{ 'is-danger': transferErrors.whatsappNumber && transferErrors.whatsappNumber.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Missed or Changed Flight" :errors="transferErrors.missedOrChangedFlight">
			<control-editor v-model="transfer.missedOrChangedFlight" :class="{ 'is-danger': transferErrors.missedOrChangedFlight && transferErrors.missedOrChangedFlight.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Arrival Procedure" :errors="transferErrors.arrivalProcedure">
			<control-editor v-model="transfer.arrivalProcedure" :class="{ 'is-danger': transferErrors.arrivalProcedure && transferErrors.arrivalProcedure.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Departure Procedure" :errors="transferErrors.departureProcedure">
			<control-editor v-model="transfer.departureProcedure" :class="{ 'is-danger': transferErrors.departureProcedure && transferErrors.departureProcedure.length }" :readonly="readonly" />
		</form-field>
		<form-field label="Display Image" :errors="transferErrors.displayImage">
			<image-uploader v-model="transfer.displayImage" :class="{ 'is-danger': transferErrors.displayImage && transferErrors.displayImage.length }" :readonly="readonly" @errors="$set(errors, 'displayImage', $event)" :max-size="2048" is-single />
		</form-field>
		<form-field label="App Image" :errors="transferErrors.appImage">
			<image-uploader v-model="transfer.appImage" :class="{ 'is-danger': transferErrors.appImage && transferErrors.appImage.length }" :readonly="readonly" @errors="$set(errors, 'appImage', $event)" :max-size="2048" is-single />
		</form-field>
		<form-field label="App Link" :errors="transferErrors.appLink">
			<control-input v-model="transfer.appLink" :class="{ 'is-danger': transferErrors.appLink && transferErrors.appLink.length }" :readonly="readonly" />
		</form-field>
		<control-button v-if="!readonly" @click="update" class="is-primary" :class="{ 'is-loading': isLoading === 'update' }">Save</control-button>
	</template>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import DeleteTransfer from '@dashboard/pages/Transfers/Delete';
import FormField from '@dashboard/components/form/Field';
import Tab from '@dashboard/components/tabs/Tab';
import Tabs from '@dashboard/components/tabs/Tabs';
import ControlEditor from '@dashboard/components/form/controls/QuillEditor';
import ImageUploader from '@dashboard/components/file/ImageUploader';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		DeleteTransfer,
		FormField,
		Tab,
		Tabs,
		ControlEditor,
		ImageUploader,
	},
	data() {
		return {
			savedTransfer: null,
			transfer: {},
			transferErrors: {},
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
			return !this.savedTransfer.can.update;
		}
	},
	methods: {
		fetchData() {
			this.$http.get('/transfers/' + this.$route.params.id)
				.then(response => {
					this.savedTransfer = response.data.data;

					this.transfer = {
						name: this.savedTransfer.name,
						email: this.savedTransfer.email,
						primaryPhoneNumber: this.savedTransfer.primaryPhoneNumber,
						secondaryPhoneNumberLabel: this.savedTransfer.secondaryPhoneNumberLabel,
						secondaryPhoneNumberValue: this.savedTransfer.secondaryPhoneNumberValue,
						whatsappNumber: this.savedTransfer.whatsappNumber,
						missedOrChangedFlight: this.savedTransfer.missedOrChangedFlight,
						arrivalProcedure: this.savedTransfer.arrivalProcedure,
						departureProcedure: this.savedTransfer.departureProcedure,
						displayImage: this.savedTransfer.displayImage,
						appImage: this.savedTransfer.appImage,
						appLink: this.savedTransfer.appLink,
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
					label: 'Transfer Providers',
					route: 'transfers'
				},
				{
					label: this.savedTransfer.name,
					route: 'transfers.show',
					params: {
						id: this.savedTransfer.id
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

			let request = this.$http.put('/transfers/' + this.$route.params.id, this.transfer)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'The transfer provider has been updated.'
					});

					this.savedTransfer = response.data.data;
					this.transferErrors = [];
				}).catch(error => {
					if (error.response.status === 422) {
						this.transferErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = '';
			});
		},
		deleted() {
			this.$router.push({
				name: 'transfers'
			});
		}
	}
}
</script>
