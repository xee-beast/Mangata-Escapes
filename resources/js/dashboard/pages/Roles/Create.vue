<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Role</a>
	<modal v-if="render" @hide="show = false" title="New Role" :is-active="show">
		<form-field label="Name" :errors="roleErrors.name" :required="true">
			<control-input v-model="role.name" :class="{ 'is-danger': roleErrors.name && roleErrors.name.length }" />
		</form-field>
		<form-field label="Description" :errors="roleErrors.description">
			<control-textarea v-model="role.description" :class="{ 'is-danger': roleErrors.description && roleErrors.description.length }" />
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
import Modal from '@dashboard/components/Modal';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';
import FormField from '@dashboard/components/form/Field';

export default {
	components: {
		Modal,
		ControlButton,
		ControlInput,
		ControlTextarea,
		FormField,
	},
	props: {
		buttonClass: String
	},
	data() {
		return {
			render: false,
			show: false,
			role: {},
			roleErrors: {},
			isLoading: false
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/roles', this.role)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The new role has been created.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.roleErrors = error.response.data.errors;
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
