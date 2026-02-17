<template>
	<div>
		<a @click.prevent="render = show = true" class="button" :class="buttonClass">Add Lead</a>
		<modal v-if="render" @hide="show = false" title="New Lead" :is-active="show">
			<form-field label="Bride First Name" :errors="leadErrors.brideFirstName" :required="true">
				<control-input v-model="lead.brideFirstName" class="is-capitalized" :class="{ 'is-danger': leadErrors.brideFirstName && leadErrors.brideFirstName.length }" />
			</form-field>
			<form-field label="Bride Last Name" :errors="leadErrors.brideLastName" :required="true">
				<control-input v-model="lead.brideLastName" class="is-capitalized" :class="{ 'is-danger': leadErrors.brideLastName && leadErrors.brideLastName.length }" />
			</form-field>
			<form-field label="Groom First Name" :errors="leadErrors.groomFirstName" :required="true">
				<control-input v-model="lead.groomFirstName" class="is-capitalized" :class="{ 'is-danger': leadErrors.groomFirstName && leadErrors.groomFirstName.length }" />
			</form-field>
			<form-field label="Groom Last Name" :errors="leadErrors.groomLastName" :required="true">
				<control-input v-model="lead.groomLastName" class="is-capitalized" :class="{ 'is-danger': leadErrors.groomLastName && leadErrors.groomLastName.length }" />
			</form-field>
			<form-field label="Departure" :errors="leadErrors.departure">
				<control-select
					v-model="lead.departure"
					:options="departureOptions"
					:class="{ 'is-danger': leadErrors.departure && leadErrors.departure.length }"
					first-is-empty
				/>
			</form-field>
			<form-field label="Phone" :errors="leadErrors.phone">
				<control-input v-model="lead.phone" :class="{ 'is-danger': leadErrors.phone && leadErrors.phone.length }" />
			</form-field>
			<form-field>
				<label class="checkbox">
					<input type="checkbox" v-model="lead.textAgreement" />
					I agree to receive text messages
				</label>
			</form-field>
			<form-field label="Email" :errors="leadErrors.email" :required="true">
				<control-input v-model="lead.email" :class="{ 'is-danger': leadErrors.email && leadErrors.email.length }" />
			</form-field>
			<form-field label="Destination(s)" :errors="leadErrors.destinations">
				<control-textarea v-model="lead.destinations" :class="{ 'is-danger': (leadErrors.destinations || []).length }" />
			</form-field>
			<form-field label="Wedding Date" :errors="leadErrors.weddingDate">
				<control-input v-model="lead.weddingDate" type="date" :class="{ 'is-danger': leadErrors.weddingDate && leadErrors.weddingDate.length }" :min="$moment().add(1, 'day').format('YYYY-MM-DD')" />
			</form-field>
			<form-field label="How They Heard About Us?" :errors="leadErrors.referralSource">
				<control-select
					v-model="lead.referralSource"
					:options="mappedReferralSourceOptions"
					:class="{ 'is-danger': leadErrors.referralSource && leadErrors.referralSource.length }"
					first-is-empty
				/>
			</form-field>
			<form-field v-if="lead.referralSource === 'Facebook Group (please include which)'" label="Facebook Group (if applicable)" :errors="leadErrors.facebookGroup">
				<control-textarea v-model="lead.facebookGroup" :class="{ 'is-danger': (leadErrors.facebookGroup || []).length }" />
			</form-field>
			<form-field v-if="lead.referralSource === 'Referral (please include who)'" label="Referred By (if applicable)" :errors="leadErrors.referredBy">
				<control-input v-model="lead.referredBy" :class="{ 'is-danger': leadErrors.referredBy && leadErrors.referredBy.length }" />
			</form-field>
			<form-field label="Message" :errors="leadErrors.message">
				<control-textarea v-model="lead.message" :class="{ 'is-danger': (leadErrors.message || []).length }" />
			</form-field>
			<form-field label="Travel Agent Requested" :errors="leadErrors.travelAgentRequested">
				<control-input v-model="lead.travelAgentRequested" :class="{ 'is-danger': leadErrors.travelAgentRequested && leadErrors.travelAgentRequested.length }" />
			</form-field>
			<form-field label="How They Contacted Us?" :errors="leadErrors.contactedUsBy">
				<control-select
					v-model="lead.contactedUsBy"
					:options="mappedContactedUsOptions"
					:class="{ 'is-danger': leadErrors.contactedUsBy && leadErrors.contactedUsBy.length }"
					first-is-empty
				/>
			</form-field>
			<form-field label="Contacted Us Date" :errors="leadErrors.contactedUsDate">
				<control-input v-model="lead.contactedUsDate" type="date" :class="{ 'is-danger': leadErrors.contactedUsDate && leadErrors.contactedUsDate.length }" :max="$moment().format('YYYY-MM-DD')" />
			</form-field>
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
	import ControlInput from '@dashboard/components/form/controls/Input';
	import ControlSelect from '@dashboard/components/form/controls/Select';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';
	import FormField from '@dashboard/components/form/Field';
	import Modal from '@dashboard/components/Modal';

	export default {
		components: {
			ControlButton,
			ControlInput,
			ControlSelect,
			ControlTextarea,
			FormField,
			Modal,
		},
		props: {
			buttonClass: String,
			referralSourceOptions: {
        type: Array,
        default: () => []
			},
			contactedUsOptions: {
        type: Array,
        default: () => []
			},
		},
		data() {
			return {
				render: false,
				show: false,
				lead: {},
				leadErrors: {},
				isLoading: false,
				departureOptions: [
					{ value: "US", text: "US" },
					{ value: "Canada", text: "Canada" },
					{ value: "Other", text: "Other" },
				],
			}
		},
		computed: {
			mappedReferralSourceOptions() {
				return this.referralSourceOptions.map(option => ({
					value: option.option,
					text: option.option
				}));
			},
			mappedContactedUsOptions() {
				return this.contactedUsOptions.map(option => ({
					value: option.option,
					text: option.option
				}));
			},
		},
		methods: {
			create() {
				this.isLoading = true;

				let request = this.$http.post('/leads', this.lead)
					.then(response => {
						this.close();

						this.$emit('created', response.data.data);

						this.$store.commit('notification', {
							type: 'success',
							message: 'The new lead has been created.',
						});
					})
					.catch(error => {
						if (error.response.status == 422) {
							this.leadErrors = error.response.data.errors;
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