<template>
<div>
	<a @click.prevent="render = show = true" class="button button-left-margin" :class="buttonClass">New Group</a>
	<modal v-if="render" @hide="show = false" title="New Group" :is-active="show">
		<form-field label="Destination" :errors="groupErrors.destination" :required="true">
			<control-select v-model="destination" :options="destinations" first-is-empty :class="{ 'is-danger': (groupErrors.destination || []).length }" />
		</form-field>
		<div class="columns">
			<div class="column">
				<form-field label="Group Type" :errors="groupErrors.fit">
					<label class="checkbox" :class="{ 'is-danger': (groupErrors.fit || []).length }">
						<input type="checkbox" v-model="group.fit">
						Is this a FIT group?
					</label>
				</form-field>
			</div>
			<div class="column">
				<form-field label="Wedding Location" :errors="groupErrors.weddingLocation" :required="true">
					<control-radio v-model="group.weddingLocation"  :options="[{text: 'Resort', value: 'resort'}, {text: 'Venue', value: 'venue'}]" />
				</form-field>
			</div>
		</div>
		<form-field v-if="group.weddingLocation == 'venue'" label="Venue Name" :errors="groupErrors.venueName" :required="true">
			<control-input v-model="group.venueName" :class="{ 'is-danger': (groupErrors.venueName || []).length }" />
		</form-field>
		<form-field label="Event Date" :errors="groupErrors.eventDate" :required="true">
      <date-picker v-model="eventDate" :popover="{ visibility: 'focus' }">
				<template v-slot="{ inputValue, inputEvents }">
					<input
						:class="'input' + ((groupErrors.eventDate || []).length ? ' is-danger' : '')"
						:value="inputValue"
						v-on="inputEvents"
					/>
				</template>
      </date-picker>
		</form-field>
		<div class="columns">
			<div class="column">
				<form-field label="Bride's First Name" :errors="groupErrors.brideFirstName" :required="true">
					<control-input v-model="group.brideFirstName" @input="updateSlug" class="is-capitalized" :class="{ 'is-danger': (groupErrors.brideFirstName || []).length }" />
				</form-field>
			</div>
			<div class="column">
				<form-field label="Bride's Last Name" :errors="groupErrors.brideLastName" :required="true">
					<control-input v-model="group.brideLastName" class="is-capitalized" :class="{ 'is-danger': (groupErrors.brideLastName || []).length }" />
				</form-field>
			</div>
		</div>
		<div class="columns">
			<div class="column">
				<form-field label="Groom's First Name" :errors="groupErrors.groomFirstName" :required="true">
					<control-input v-model="group.groomFirstName" @input="updateSlug" class="is-capitalized" :class="{ 'is-danger': (groupErrors.groomFirstName || []).length }" />
				</form-field>
			</div>
			<div class="column">
				<form-field label="Groom's Last Name" :errors="groupErrors.groomLastName" :required="true">
					<control-input v-model="group.groomLastName" class="is-capitalized" :class="{ 'is-danger': (groupErrors.groomLastName || []).length }" />
				</form-field>
			</div>
		</div>
		<form-field label="Email" :errors="groupErrors.email" :required="true">
			<control-input v-model="group.email" class="is-lowercase" :class="{ 'is-danger': (groupErrors.email || []).length }" />
		</form-field>
		<form-field label="Secondary Email" :errors="groupErrors.secondaryEmail">
			<control-input v-model="group.secondaryEmail" class="is-lowercase" :class="{ 'is-danger': (groupErrors.secondaryEmail || []).length }" />
		</form-field>
		<form-field label="Group Leader Password" :errors="groupErrors.password" :required="true">
			<control-input v-model="group.password" :class="{ 'is-danger': (groupErrors.password || []).length }" />
		</form-field>
		<div class="columns">
			<div class="column">
				<form-field label="Slug" :errors="groupErrors.slug" :required="true">
					<control-input v-model="group.slug" class="is-lowercase" :class="{ 'is-danger': (groupErrors.slug || []).length }" />
				</form-field>
			</div>
			<div class="column">
				<form-field label="Show Couple Site" :errors="groupErrors.isActive" :required="true">
					<control-radio v-model="group.isActive"  :options="[{text: 'Yes', value: true}, {text: 'No', value: false}]" />
				</form-field>
			</div>
		</div>
		<form-field label="Couples Site Password" :errors="groupErrors.couplesSitePassword" :required="true">
			<control-input v-model="group.couplesSitePassword" :class="{ 'is-danger': (groupErrors.couplesSitePassword || []).length }" />
		</form-field>
		<form-field label="Image" :errors="groupErrors.image">
			<image-uploader v-model="group.image" @errors="$set(groupErrors, 'image', $event)" :class="{ 'is-danger': (groupErrors.image || []).length }" :max-size="1024" is-single />
		</form-field>
		<form-field label="Couple's Message" :errors="groupErrors.message">
			<control-textarea v-model="group.message" :class="{ 'is-danger': (groupErrors.message || []).length }" />
		</form-field>
		<form-field label="Travel Agent" :errors="groupErrors.agent" :required="true">
			<control-select v-model="group.agent" :options="agents" first-is-empty :class="{ 'is-danger': (groupErrors.agent || []).length }" />
		</form-field>
		<form-field label="Supplier" :errors="groupErrors.provider" :required="true">
			<control-select v-model="group.provider" :options="providers" first-is-empty :class="{ 'is-danger': (groupErrors.provider || []).length }" />
		</form-field>
		<form-field label="Supplier ID" :errors="groupErrors.providerId" :required="true">
			<control-input v-model="group.providerId" :class="{ 'is-danger': (groupErrors.providerId || []).length }" />
		</form-field>
		<div class="columns">
			<div class="column">
				<form-field label="Fallback Insurance Rate" :errors="groupErrors.insuranceRate" :required="true">
					<control-select v-model="group.insuranceRate" :options="insuranceRates.filter(rate => rate.provider == (group.provider || 0))" first-is-empty :class="{ 'is-danger': (groupErrors.insuranceRate || []).length }" />
				</form-field>
			</div>
			<div class="column">
				<form-field label="Force fallback insurance rate?" :errors="groupErrors.useFallbackInsurance" :required="true">
					<control-radio v-model="group.useFallbackInsurance" :options="[{value: true, text: 'Yes'}, {value: false, text: 'No'}]" default="total" :class="{ 'is-danger': (groupErrors.useFallbackInsurance || []).length }" />
				</form-field>
			</div>
		</div>
		<form-field label="Minimum Nights" :errors="groupErrors.minNights" :required="true">
			<control-input v-model="group.minNights" type="number" :class="{ 'is-danger': (groupErrors.minNights || []).length }" />
		</form-field>
		<form-field label="Minimum Deposit" :errors="[...(groupErrors.deposit || []), ...(groupErrors.depositType || [])]" :required="true">
			<control-input v-model="group.deposit" :class="{ 'is-danger': (groupErrors.deposit || []).length }" />
			<template v-slot:addon>
				<control-select
					v-model="group.depositType"
					:options="depositTypeOptions"
					:class="{ 'is-danger': (groupErrors.depositType || []).length }"
				/>
			</template>
		</form-field>
		<form-field label="Change Fee Date" :errors="groupErrors.changeFeeDate" :required="true">
			<date-picker
				v-model="changeFeeDate"
				:max-date="group.eventDate"
				:popover="{ visibility: 'focus' }"
			>
				<template v-slot="{ inputValue, inputEvents }">
					<input
						:class="'input' + ((groupErrors.changeFeeDate || []).length ? ' is-danger' : '')"
						:value="inputValue"
						v-on="inputEvents"
					/>
				</template>
			</date-picker>
		</form-field>
		<form-field label="Change Fee Amount" :errors="[...(groupErrors.changeFeeAmount || [])]" :required="true">
			<control-input v-model="group.changeFeeAmount" :class="{ 'is-danger': (groupErrors.changeFeeAmount || []).length }" />
		</form-field>
		<form-field label="Cancellation Date" :errors="groupErrors.cancellationDate" :required="true">
			<date-picker
				v-model="cancellationDate"
				:max-date="group.eventDate"
				:popover="{ visibility: 'focus' }"
			>
				<template v-slot="{ inputValue, inputEvents }">
					<input
						:class="'input' + ((groupErrors.cancellationDate || []).length ? ' is-danger' : '')"
						:value="inputValue"
						v-on="inputEvents"
					/>
				</template>
			</date-picker>
		</form-field>
		<form-field label="Balance Due Date" :errors="groupErrors.dueDate" :required="true">
			<date-picker
				v-model="dueDate"
				:max-date="group.eventDate"
				:popover="{ visibility: 'focus' }"
			>
				<template v-slot="{ inputValue, inputEvents }">
					<input
						:class="'input' + ((groupErrors.dueDate || []).length ? ' is-danger' : '')"
						:value="inputValue"
						v-on="inputEvents"
					/>
				</template>
			</date-picker>
		</form-field>
		<form-field label="Notes" :errors="groupErrors.notes">
			<control-textarea v-model="group.notes" :class="{ 'is-danger': (groupErrors.notes || []).length }" />
		</form-field>
    <div class="columns">
      <div class="column">
        <form-field :errors="groupErrors.disableInvoiceSplitting">
          <input type="checkbox" v-model="group.disableInvoiceSplitting">
          Disable Invoice Splitting
        </form-field>
      </div>
      <div class="column">
        <form-field :errors="groupErrors.disableNotifications">
          <input type="checkbox" v-model="group.disableNotifications">
          Disable Group Notifications
        </form-field>
      </div>
    </div>
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
import ControlRadio from '@dashboard/components/form/controls/Radio';
import ControlSelect from '@dashboard/components/form/controls/Select';
import ControlTextarea from '@dashboard/components/form/controls/Textarea';
import DatePicker from 'v-calendar/lib/components/date-picker.umd';
import FormField from '@dashboard/components/form/Field';
import ImageUploader from '@dashboard/components/file/ImageUploader';
import Modal from '@dashboard/components/Modal';

export default {
	components: {
		ControlButton,
		ControlInput,
		ControlRadio,
		ControlSelect,
		ControlTextarea,
		DatePicker,
		ImageUploader,
		FormField,
		Modal,
	},
	props: {
		destinations: {
			type: Array,
			required: true
		},
		agents: {
			type: Array,
			required: true
		},
		providers: {
			type: Array,
			required: true
		},
		insuranceRates: {
			type: Array,
			required: true
		},
		buttonClass: String
	},
	data() {
		return {
			render: false,
			show: false,
			group: {
				fit: false,
				minNights: 3,
				depositType: 'fixed',
				disableNotifications: false
			},
			groupErrors: {},
			isLoading: false,
		}
	},
	computed: {
		eventDate: {
			get() {
				return this.group.eventDate;
			},
			set(date) {
				this.group.eventDate = date instanceof Date ? date.toDateString() : null;
			}
		},
		cancellationDate: {
			get() {
				return this.group.cancellationDate;
			},
			set(date) {
				this.group.cancellationDate = date instanceof Date ? date.toDateString() : null;
			}
		},
		changeFeeDate: {
			get() {
				return this.group.changeFeeDate;
			},
			set(date) {
				this.group.changeFeeDate = date instanceof Date ? date.toDateString() : null;
			}
		},
		dueDate: {
			get() {
				return this.group.dueDate;
			},
			set(date) {
				this.group.dueDate = date instanceof Date ? date.toDateString() : null;
			}
		},
		destination: {
			get() {},
			set(destination) {
				this.group.destination = destination;
			}
		},
		depositTypeOptions() {
			const baseOptions = [
				{ value: 'fixed', text: '$' },
				{ value: 'percentage', text: '%' }
			];

			if (!this.group.fit) {
				baseOptions.push({ value: 'nights', text: 'Nights' });
			}

			return baseOptions;
		}
	},
	methods: {
		create() {
			this.isLoading = true;

			let request = this.$http.post('/groups', this.group)
				.then(response => {
					this.close();
					this.$emit('created', response.data.data);
					this.$store.commit('notification', {
						type: 'success',
						message: 'The new group has been created.',
					});
				})
				.catch(error => {
					if (error.response.status == 422) {
						this.groupErrors = error.response.data.errors;
					}
				});

			request.then(() => {
				this.isLoading = false;
			});
		},
		close() {
			Object.assign(this.$data, this.$options.data.apply(this));
		},
		updateSlug() {
			this.group.slug = (this.group.brideFirstName || '') + (this.group.groomFirstName || '');
		}
	}
}
</script>
