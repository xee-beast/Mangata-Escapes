<template>
<card title="Change Password">
	<form-field label="Current Password" :errors="passwordErrors.currentPassword">
		<control-input v-model="password.currentPassword" :type="showPassword ? 'text' : 'password'"
			:class="{ 'is-danger': passwordErrors.currentPassword && passwordErrors.currentPassword.length }" />
	</form-field>
	<form-field label="New Password" :errors="passwordErrors.newPassword">
		<control-input v-model="password.newPassword" :type="showPassword ? 'text' : 'password'"
			:class="{ 'is-danger': passwordErrors.newPassword && passwordErrors.newPassword.length }" />
	</form-field>
	<form-field label="Confirm Password" :errors="passwordErrors.confirmPassword">
		<control-input v-model="password.confirmPassword" :type="showPassword ? 'text' : 'password'"
			:class="{ 'is-danger': passwordErrors.confirmPassword && passwordErrors.confirmPassword.length }" />
	</form-field>
	<form-field>
		<label class="checkboxis-size-7">
			<input v-model="showPassword" type="checkbox">
			Show Passwords
		</label>
	</form-field>
	<control-button @click="changePassword" class="is-primary" :class="{ 'is-loading': isLoading }">Submit</control-button>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import FormField from '@dashboard/components/form/Field';

export default {
	components: {
		Card,
		ControlButton,
		ControlInput,
		FormField,
	},
	data() {
		return {
			password: {},
			passwordErrors: {},
			showPassword: false,
			isLoading: false,
		}
	},
	methods: {
		changePassword() {
			this.isLoading = true;

			let request = this.$http.put('/account/password', this.password)
				.then(response => {
					this.$store.commit('notification', {
						type: 'success',
						message: 'Your password has been updated.'
					});

					this.password = {};

					this.passwordErrors = {};
				}).catch(error => {
					if (error.response.status === 422) {
						this.passwordErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = false;
			})
		}
	}
}
</script>
