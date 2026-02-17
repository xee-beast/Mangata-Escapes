<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Supplier</a>
	<modal v-if="render" @hide="show = false" title="New Supplier" :is-active="show">
		<form-field label="Name" :errors="providerErrors.name" :required="true">
			<control-input v-model="provider.name" :class="{ 'is-danger': providerErrors.name && providerErrors.name.length }" />
		</form-field>
		<form-field label="Abbreviation" :errors="providerErrors.abbreviation" :required="true">
			<control-input v-model="provider.abbreviation"
				:class="{ 'is-danger': providerErrors.abbreviation && providerErrors.abbreviation.length }" />
		</form-field>
        <form-field label="Email" :errors="providerErrors.email" :required="true">
			<control-input v-model="provider.email"
				:class="{ 'is-danger': providerErrors.email && providerErrors.email.length }" />
		</form-field>
		<form-field label="Phone Number (Travel Documents)" :errors="providerErrors.phoneNumber" :required="true">
			<control-input v-model="provider.phoneNumber"
				:class="{ 'is-danger': providerErrors.phoneNumber && providerErrors.phoneNumber.length }" />
		</form-field>
		<template v-slot:footer>
			<div class="field is-grouped">
				<control-button @click="close" :disabled="isLoading">Cancel</control-button>
				<control-button @click="create" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }" :disabled="isLoading">
					Submit
				</control-button>
			</div>
		</template>
	</modal>
</div>
</template>

<script>
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import FormField from '@dashboard/components/form/Field';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlInput,
		FormField,
		Modal,
	},
	props: {
		buttonClass: String
	},
	data() {
		return {
			render: false,
			show: false,
			provider: {},
			providerErrors: {},
			isLoading: false
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/providers', this.provider)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The new supplier has been created.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.providerErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = false;
			});
		},
		close() {
			Object.assign(this.$data, this.$options.data.apply(this));
		}
	}
}
</script>
