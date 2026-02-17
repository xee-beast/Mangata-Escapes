<template>
  <div>
    <modal @hide="close" title="Booking Changes" :is-active="true">
      <table class="table is-fullwidth is-size-6">
        <thead>
          <tr>
            <th width="30%"></th>
            <th width="35%">Before</th>
            <th width="35%">After</th>
          </tr>
        </thead>
        <booking-change v-for="(change, key) in changes" :key="key" :change="change" :index="key" />
      </table>
      <template v-slot:footer>
        <div class="field is-grouped">
          <control-button @click="close" :disabled="isLoading">Close</control-button>
          <control-button @click="showEmailConfirmation('cancel')" type="submit" class="is-inverted is-primary" :class="{ 'is-loading': isLoading }">Cancel</control-button>
          <control-button @click="showEmailConfirmation('confirm')" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Confirm</control-button>
        </div>
      </template>
    </modal>
    <modal :title="`${capitalize(emailConfirmationType)} Changes`" :is-active="showEmailConfirmationModal" @hide="showEmailConfirmationModal = false;">
      <p>
        Are you sure you want to {{ emailConfirmationType }} changes?
          <br>
          <br>
      </p>
      <div class="control">
        <label class="checkbox">
            <input type="checkbox" v-model="sendEmail">
                Send an email to <b>{{ emailRecipients }}</b>.
          </label>
      </div>
      <template v-slot:footer>
        <div class="field is-grouped">
          <control-button @click="handelCloseEmailConfirmation" :disabled="isLoading">Close</control-button>
          <control-button @click="handelEmailConfirmation" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Save</control-button>
        </div>
      </template>
    </modal>
  </div>
</template>

<script>
  import BookingChange from '@dashboard/pages/Groups/Bookings/BookingChange';
	import ControlButton from '@dashboard/components/form/controls/Button';
	import Modal from '@dashboard/components/Modal';

	export default {
		components: {
			BookingChange,
			ControlButton,
			Modal,
		},
		props: {
			booking: {
				type: Object,
				required: true
			},
      group: {
        type: Object,
				required: true
      }
		},
		data() {
			return {
				changes: [],
				isLoading: false,
        showEmailConfirmationModal: false,
        emailConfirmationType: '',
        sendEmail: false,
			}
		},
		created() {
			this.fetchData();
		},
    computed: {
      emailRecipients() {
        const clients = this.booking.clients.map( c => `${c.client.firstName} ${c.client.lastName}: ${c.client.email}`);

        if (clients.length === 0) return '';

        const head = clients.slice(0, -1).join(', ');
        const last = clients[clients.length - 1];

        return head ? `${head} & ${last}` : last;
      }
    },
		methods: {
			fetchData() {
				this.$http.get('/groups/' + this.group.id + '/bookings/' + this.booking.id + '/changes')
					.then(response => {
						this.changes = response.data;
					});
			},
      capitalize(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
      },
      showEmailConfirmation(type){
        this.showEmailConfirmationModal = true;
        this.emailConfirmationType = type;
      },
      handelEmailConfirmation(){
        if(this.emailConfirmationType == 'confirm'){
          this.confirm();
        } else{
          this.revert();
        }
      },
      handelCloseEmailConfirmation(){
        this.showEmailConfirmationModal = false;
        this.emailConfirmationType = '';
        this.sendEmail= false;
      },
			revert() {
				this.isLoading = true;

				let request = this.$http.patch('/groups/' + this.group.id + '/bookings/' + this.booking.id + '/revert-changes', { sendEmail: this.sendEmail })
					.then(() => {
						this.$emit('resolvedChanges');

						this.$store.commit('notification', {
							type: 'success',
							message: 'The changes have been reverted.'
						});

            this.handelCloseEmailConfirmation();
					}).catch((error) => {
					});

				request.then(() => {
					this.isLoading = false;
				});
			},
			confirm() {
				this.isLoading = true;

				let request = this.$http.patch('/groups/' + this.group.id + '/bookings/' + this.booking.id + '/confirm-changes', { sendEmail: this.sendEmail })
					.then(() => {
						this.$emit('resolvedChanges');

						this.$store.commit('notification', {
							type: 'success',
							message: 'The changes have been confirmed.'
						});

            this.handelCloseEmailConfirmation();
					});

				request.then(() => {
					this.isLoading = false;
				});
			},
			close() {
				this.$emit('canceled');
			},
		}
	}
</script>
