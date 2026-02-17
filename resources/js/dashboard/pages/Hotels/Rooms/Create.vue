<template>
<div>
	<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Room</a>
	<modal v-if="render" @hide="show = false" :title="hotel.name + ' - New Room'" :is-active="show">
		<form-field label="Name" :errors="roomErrors.name" :required="true">
			<control-input v-model="room.name" :class="{ 'is-danger': (roomErrors.name || []).length }" />
		</form-field>
		<form-field label="Description" :errors="roomErrors.description">
			<control-textarea v-model="room.description" :class="{ 'is-danger': (roomErrors.description || []).length }" />
		</form-field>
		<form-field label="Image" :errors="roomErrors.image">
			<image-uploader v-model="room.image" @errors="$set(roomErrors, 'image', $event)" :class="{ 'is-danger': (roomErrors.image || []).length }"
				is-single />
		</form-field>
    <div class="columns in-form">
      <div class="column">
        <form-field label="Room Size" :errors="roomErrors.size" :required="true">
          <control-input v-model="room.size" :class="{ 'is-danger': (roomErrors.size || []).length }" />
        </form-field>
      </div>
      <div class="column">
        <form-field label="Room View" :errors="roomErrors.view" :required="true">
          <control-input v-model="room.view" :class="{ 'is-danger': (roomErrors.view || []).length }" />
        </form-field>
      </div>
    </div>
		<div class="columns in-form">
			<div class="column">
				<form-field label="Minimum Occupants" :errors="roomErrors.minOccupants" :required="true">
					<control-input v-model="room.minOccupants" type="number" min="1" max="20"
						:class="{ 'is-danger': (roomErrors.minOccupants || []).length }" />
				</form-field>
			</div>
			<div class="column">
				<form-field label="Maximum Occupants" :errors="roomErrors.maxOccupants" :required="true">
					<control-input v-model="room.maxOccupants" type="number" min="1" max="20"
						:class="{ 'is-danger': (roomErrors.maxOccupants || []).length }" />
				</form-field>
			</div>
		</div>
		<form-field label="Is this room for adults only?" :errors="roomErrors.adultsOnly">
			<control-radio v-model="room.adultsOnly" :options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]" :default="false"
				:class="{ 'is-danger': (roomErrors.adultsOnly || []).length }" />
		</form-field>
		<template v-if="!room.adultsOnly">
			<div class="columns in-form">
				<div class="column">
					<form-field label="Maximum Adults" :errors="roomErrors.maxAdults" :required="true">
						<control-input v-model="room.maxAdults" type="number" min="1" max="20"
							:class="{ 'is-danger': (roomErrors.maxAdults || []).length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field label="Maximum Children" :errors="roomErrors.maxChildren" :required="true">
						<control-input v-model="room.maxChildren" type="number" min="1" max="20"
							:class="{ 'is-danger': (roomErrors.maxChildren || []).length }" />
					</form-field>
				</div>
			</div>
			<div class="columns in-form is-mobile">
				<div class="column">
					<form-field label="Ratio" :errors="roomErrors.minAdultsPerChild" :required="true">
						<control-input v-model="room.minAdultsPerChild" type="number" min="1" max="20" placeholder="Adults"
							:class="{ 'is-danger': (roomErrors.minAdultsPerChild || []).length }" />
					</form-field>
				</div>
				<div class="column">
					<form-field label=" " :errors="roomErrors.maxChildrenPerAdult">
						<control-input v-model="room.maxChildrenPerAdult" type="number" min="1" max="20" placeholder="Children"
							:class="{ 'is-danger': (roomErrors.maxChildrenPerAdult || []).length }" />
					</form-field>
				</div>
			</div>
		</template>
		<template v-slot:footer>
			<div class="field is-grouped">
				<control-button @click="close" :disabled="isLoading">Cancel</control-button>
				<control-button @click="create" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Submit
				</control-button>
			</div>
		</template>
	</modal>
</div>
</template>

<script>
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlInput from '@dashboard/components/form/controls/Input';
import ControlRadio from '@dashboard/components/form/controls/Radio';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';
import FormField from '@dashboard/components/form/Field';
import ImageUploader from '@dashboard/components/file/ImageUploader';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlInput,
		ControlRadio,
		ControlTextarea,
		FormField,
		ImageUploader,
		Modal,
	},
	props: {
		buttonClass: String,
		hotel: Object
	},
	data() {
		return {
			render: false,
			show: false,
			room: {},
			roomErrors: {},
			isLoading: false
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/hotels/' + this.hotel.id + '/rooms', this.room)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The new room has been created.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.roomErrors = error.response.data.errors;
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
