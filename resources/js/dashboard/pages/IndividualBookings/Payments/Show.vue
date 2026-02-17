<template>
	<modal @hide="close" title="Payment Details" :is-active="true">
		<div class="columns is-multiline is-size-6">
			<div class="column">
				<p>
					<span class="has-text-weight-bold">Payment Amount</span>&nbsp;
					<a @click.prevent="clip({key: 'Amount', value: parseFloat(updatePayment.amount).toFixed(2)})" class="has-text-grey-dark">
						<span class="icon"><i class="fas fa-copy"></i></span>
					</a>
					<br>
					<control-input v-model="updatePayment.amount" :class="{'is-danger': (updatePaymentErrors.amount || []).length}" :readonly="!payment.can.update" />
				</p>
			</div>
		</div>
		<form-field label="Notes" :errors="updatePaymentErrors.notes">
			<control-textarea v-model="updatePayment.notes" :class="{'is-danger': (updatePaymentErrors.notes || []).length}" :readonly="!payment.can.update" />
		</form-field>
		<template v-if="showSensitive">
			<form-panel label="Credit Card Information">
				<div class="columns is-multiline is-size-6">
					<div class="column is-6">
						<p>
							<span class="has-text-weight-bold">Cardholder Name</span>&nbsp;
							<a @click.prevent="clip({key: 'Name', value: payment.card.name})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
							<br>
							{{ payment.card.name }}
						</p>
					</div>
					<div class="column is-6">
						<p>
							<span class="has-text-weight-bold">Credit Card Number</span>&nbsp;
							<a @click.prevent="clip({key: 'Card Number', value: payment.card.number})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
							<br>
							<span class="icon"><i :class="creditCards[payment.card.type]"></i></span>&nbsp;
							{{ payment.card.number }}
						</p>
					</div>
					<div class="column is-6">
						<p>
							<span class="has-text-weight-bold">Expiration Date</span>&nbsp;
							<br>
							{{ payment.card.expMonth }}/{{ payment.card.expYear }}
						</p>
					</div>
					<div class="column is-6">
						<p>
							<span class="has-text-weight-bold">CVV Code</span>&nbsp;
							<a @click.prevent="clip({key: 'CVV Code', value: payment.card.code})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
							<br>
							{{ payment.card.code }}
						</p>
					</div>
				</div>
			</form-panel>
			<form-panel v-if="payment.card.address" label="Billing Address">
				<div class="columns is-multiline is-size-6">
					<div class="column is-4">
						<p>
							<span class="has-text-weight-bold">Country</span>&nbsp;
							<a @click.prevent="clip({key: 'Country', value: payment.card.address.country})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
							<br>
							{{ payment.card.address.country }}
						</p>
					</div>
					<div class="column is-4">
						<p>
							<span class="has-text-weight-bold">State</span>&nbsp;
							<a @click.prevent="clip({key: 'State', value: payment.card.address.state})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
							<br>
							{{ payment.card.address.state }} 
							<span v-if="payment.card.address.stateAbbreviation">
								({{ payment.card.address.stateAbbreviation }})
							</span>
						</p>
					</div>
					<div class="column is-4">
						<p>
							<span class="has-text-weight-bold">City</span>&nbsp;
							<a @click.prevent="clip({key: 'City', value: payment.card.address.city})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
							<br>
							{{ payment.card.address.city }}
						</p>
					</div>
					<div class="column is-4">
						<p>
							<span class="has-text-weight-bold">Address Line 1</span>&nbsp;
							<a @click.prevent="clip({key: 'Address Line 1', value: payment.card.address.line1})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
							<br>
							{{ payment.card.address.line1 }}
						</p>
					</div>
					<div v-if="payment.card.address.line2" class="column is-4">
						<p>
							<span class="has-text-weight-bold">Address Line 2</span>&nbsp;
							<a @click.prevent="clip({key: 'Address Line 2', value: payment.card.address.line2})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
							<br>
							{{ payment.card.address.line2 }}
						</p>
					</div>
					<div class="column is-4">
						<p>
							<span class="has-text-weight-bold">Zip/Postal Code</span>&nbsp;
							<a @click.prevent="clip({key: 'Zip/Postal Code', value: payment.card.address.zipCode})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
							<br>
							{{ payment.card.address.zipCode }}
						</p>
					</div>
				</div>
			</form-panel>
		</template>
		<template v-slot:footer>
			<div class="field is-grouped">
				<control-button @click="close">Close</control-button>
				<control-button v-if="payment.can.update" @click="update" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Save</control-button>
			</div>
		</template>
	</modal>
</template>

<script>
	import ControlButton from '@dashboard/components/form/controls/Button';
	import ControlTextarea from '@dashboard/components/form/controls/Textarea';
	import ControlInput from '@dashboard/components/form/controls/Input';
	import FormField from '@dashboard/components/form/Field';
	import FormPanel from '@dashboard/components/form/Panel';
	import Modal from '@dashboard/components/Modal';

	export default {
		components: {
			ControlButton,
			ControlTextarea,
			ControlInput,
			FormField,
			FormPanel,
			Modal,
		},
		props: {
			payment: {
				type: Object,
				required: true
			},
			showSensitive: {
				type: Boolean,
				default: false
			}
		},
		data() {
			return {
				updatePayment: {
					notes: this.payment.notes,
					amount: this.payment.amount,
				},
				updatePaymentErrors: {},
				creditCards: {
					visa: 'fab fa-cc-visa',
					mastercard: 'fab fa-cc-mastercard',
					amex: 'fab fa-cc-amex',
					discover: 'fab fa-cc-discover'
				},
				isLoading: false
			}
		},
		methods: {
			clip(message) {
				let copyElement = document.createElement('textarea');
				copyElement.style.position = 'absolute';
				copyElement.style.top = '-999px';
				copyElement.innerText = message.value;

				document.body.appendChild(copyElement);
				copyElement.select();
				document.execCommand('copy');
				document.body.removeChild(copyElement);

				this.$store.commit('notification', {
					type: 'success',
					message: `${message.key} copied to clipboard.`,
					timeout: 2000
				});
			},
			update() {
				this.isLoading = true;

				let response = this.$http.patch(`individual-bookings/${this.$route.params.id}/payments/${this.payment.id}`, this.updatePayment)
					.then(response => {
						this.$store.commit('notification', {
							type: 'success',
							message: 'The payment was updated successfully.'
						});

						this.$emit('updated', response.data.data);
					}).catch(error => {
						if (error.response.status == 422) {
							this.paymentErrors = error.response.data.errors;
						}
					});

				response.then(() => {
					this.isLoading = false;
				})
			},
			close() {
				this.$emit('canceled');
			}
		}
	}
</script>
