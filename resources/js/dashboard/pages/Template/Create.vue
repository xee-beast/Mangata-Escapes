<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Model</a>
	<modal v-if="render" @hide="show = false" title="New Model" :is-active="show">
		<form-field label="Name" :errors="modelErrors.name">
			<control-input v-model="model.name" :class="{ 'is-danger': (modelErrors.name || []).length }" />
		</form-field>
		<!-- Code Here -->
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
			model: {},
			modelErrors: {},
			isLoading: false
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/models', this.model)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The new model has been created.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.modelErrors = error.response.data.errors;
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
