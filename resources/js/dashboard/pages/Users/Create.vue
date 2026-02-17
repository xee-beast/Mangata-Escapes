<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">New User</a>
	<modal v-if="render" @hide="show = false" title="New User" :is-active="show">
		<form-field label="First Name" :errors="userErrors.firstName" :required="true">
			<control-input v-model="user.firstName" @enter="create" class="is-capitalized"
				:class="{ 'is-danger': (userErrors.firstName || []).length }" />
		</form-field>
		<form-field label="Last Name" :errors="userErrors.lastName" :required="true">
			<control-input v-model="user.lastName" @enter="create" class="is-capitalized"
				:class="{ 'is-danger': (userErrors.lastName || []).length }" />
		</form-field>
		<form-field label="Username" :errors="userErrors.username" :required="true">
			<control-input v-model="user.username" @enter="create" :class="{ 'is-danger': (userErrors.username || []).length }" />
		</form-field>
		<form-field label="Email" :errors="userErrors.email" :required="true">
			<control-input v-model="user.email" @enter="create" :class="{ 'is-danger': (userErrors.email || []).length }" />
		</form-field>
		<form-field label="Password" :errors="userErrors.password">
			<control-input v-model="user.password" @enter="create" type="password" class="is-lowercase"
				:class="{ 'is-danger': (userErrors.password || []).length }" />
		</form-field>
		<template v-slot:footer>
			<div class="field is-grouped">
				<control-button @click="close" :disabled="isLoading">Cancel</control-button>
				<control-button @click="create" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">
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
			user: {},
			userErrors: {},
			isLoading: false
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/users', this.user)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The user has been created.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.userErrors = error.response.data.errors;
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
