<template>
  <div>
    <a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Hotel</a>
    <modal v-if="render" @hide="show = false" title="New Hotel" :is-active="show">
      <form-field label="Name" :errors="hotelErrors.name" :required="true">
        <control-input v-model="hotel.name" :class="{ 'is-danger': (hotelErrors.name || []).length }" />
      </form-field>
      <form-field label="Destination" :errors="hotelErrors.destination" :required="true">
				<control-select v-model="hotel.destination" :class="{ 'is-danger': (hotelErrors.destination || []).length }" :options="destinations" first-is-empty />
			</form-field>
			<form-field label="Description" :errors="hotelErrors.description">
				<control-editor v-model="hotel.description" :class="{ 'is-danger': (hotelErrors.description || []).length }" />
			</form-field>
			<form-field label="Hotel URL" :errors="hotelErrors.url">
				<control-input v-model="hotel.url" :class="{ 'is-danger': (hotelErrors.url || []).length }" />
			</form-field>
			<div class="panel-head columns is-vcentered is-mobile">
        <div class="column">
					<label for="" class="label">{{ showImages ? 'Collapse Images Section' : 'Expand Images Section' }}</label>
				</div>
        <div class="panel-actions column is-narrow">
					<control-button class="is-small is-link is-outlined" @click="showImages = !showImages">
						<i :class="showImages ? 'fas fa-minus' : 'fas fa-plus'"></i>
					</control-button>
        </div>
			</div>
      <div v-if="showImages" class="panel">
        <form-field label="Cover Image (Travel Documents)" :errors="hotelErrors.travelDocsCoverImage">
					<image-uploader v-model="hotel.travelDocsCoverImage" @errors="$set(hotelErrors, 'travelDocsCoverImage', $event)"
						:class="{ 'is-danger': (hotelErrors.travelDocsCoverImage || []).length }" :max-size="2048" is-single />
        </form-field>
        <form-field label="Second Image (Travel Documents)" :errors="hotelErrors.travelDocsImageTwo">
					<image-uploader v-model="hotel.travelDocsImageTwo" @errors="$set(hotelErrors, 'travelDocsImageTwo', $event)"
						:class="{ 'is-danger': (hotelErrors.travelDocsImageTwo || []).length }" :max-size="2048" is-single />
        </form-field>
        <form-field label="Third Image (Travel Documents)" :errors="hotelErrors.travelDocsImageThree">
					<image-uploader v-model="hotel.travelDocsImageThree" @errors="$set(hotelErrors, 'travelDocsImageThree', $event)"
						:class="{ 'is-danger': (hotelErrors.travelDocsImageThree || []).length }" :max-size="2048" is-single />
        </form-field>
				<form-field label="Images" :errors="hotelErrors.images">
					<image-uploader v-model="hotel.images" @errors="$set(hotelErrors, 'images', $event)"
						:class="{ 'is-danger': (hotelErrors.images || []).length }" />
        </form-field>
      </div>
      <template v-slot:footer>
        <div class="field is-grouped">
          <control-button @click="close" :disabled="isLoading">Cancel</control-button>
					<control-button @click="create" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Submit</control-button>
        </div>
      </template>
    </modal>
  </div>
</template>

<script>
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlEditor from '@dashboard/components/form/controls/QuillEditor';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlSelect from '@dashboard/components/form/controls/Select';
import FormField from '@dashboard/components/form/Field';
import ImageUploader from '@dashboard/components/file/ImageUploader';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlEditor,
		ControlInput,
		ControlSelect,
		FormField,
		ImageUploader,
		Modal,
	},
	props: {
		buttonClass: String,
		destinations: {
			type: Array,
			required: true
		}
	},
	data() {
		return {
			render: false,
			show: false,
			showImages: false,
			hotel: {},
			hotelErrors: {},
			isLoading: false
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/hotels', this.hotel)
				.then(response => {
					this.close();
					this.$emit('created');
					this.$store.commit('notification', {
						type: 'success',
						message: 'The new hotel has been added.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.hotelErrors = error.response.data.errors;
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
