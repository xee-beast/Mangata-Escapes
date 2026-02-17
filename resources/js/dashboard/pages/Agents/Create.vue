<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">New Travel Agent</a>
	<modal v-if="render" @hide="show = false" title="New Travel Agent" :is-active="show">
		<form-field label="User" :errors="agentErrors.user" :required="true">
			<control-select v-model="agent.user" @input="setAgent" :class="{ 'is-danger': (agentErrors.user | []).length }"
				:options="users.map(user => ({value: user.id, text: user.firstName + ' ' + user.lastName}))" first-is-empty />
		</form-field>
		<template v-if="agent.user">
			<form-field label="First Name" :errors="agentErrors.firstName" :required="true">
				<control-input v-model="agent.firstName" class="is-capitalized" :class="{ 'is-danger': (agentErrors.firstName || []).length }" />
			</form-field>
			<form-field label="Last Name" :errors="agentErrors.lastName" :required="true">
				<control-input v-model="agent.lastName" class="is-capitalized" :class="{ 'is-danger': (agentErrors.lastName || []).length }" />
			</form-field>
			<form-field label="Email" :errors="agentErrors.email" :required="true">
				<control-input v-model="agent.email" :class="{ 'is-danger': (agentErrors.email || []).length }" />
			</form-field>
		</template>
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
import ControlSelect from '@dashboard/components/form/controls/Select';
import FormField from '@dashboard/components/form/Field';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlInput,
		ControlSelect,
		FormField,
		Modal,
	},
	props: {
		users: {
			type: Array,
			required: true
		},
		buttonClass: String
	},
	data() {
		return {
			render: false,
			show: false,
			agent: {
				user: null
			},
			agentErrors: {},
			isLoading: false
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/agents', this.agent)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The new agent has been created.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.agentErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = false;
			});
		},
		close() {
			Object.assign(this.$data, this.$options.data.apply(this));
		},
		setAgent() {
			const user = this.users.find(user => user.id == this.agent.user);

			if (user) {
				this.agent.firstName = user.firstName;
				this.agent.lastName = user.lastName;
				this.agent.email = user.email;
			}
		}
	}
}
</script>
