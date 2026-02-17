<template>
    <div>
        <modal @hide="close" title="Send Quote" :is-active="true">
            <div v-if="clientsWithQuote.length > 0">
                <table class="table is-fullwidth">
                    <tbody>
                        <tr v-for="client in clientsWithQuote" :key="client.client.email">
                            <td><b>{{ client.firstName }} {{ client.lastName }} ({{ client.client.email }})</b> already has {{ client.acceptedFitQuote ? 'an accepted' : 'a pending' }} Quote, {{ client.pendingFitQuote ? 'which expires at ' + client.pendingFitQuote.expiryDateTime + '.' : '' }}</td>
                            <td class="has-text-right">
                                <control-button v-if="client.acceptedFitQuote" class="is-small is-success">Accepted</control-button>
                                <control-button v-if="client.pendingFitQuote" class="is-small is-danger" @click="cancel(client)" :disabled="isLoading">Cancel</control-button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <hr v-if="clientsWithoutQuote.length > 0 && clientsWithQuote.length > 0">
            <div v-if="clientsWithoutQuote.length > 0">
                <form-field label="Expiry Date & Time">
                    <input type="datetime-local" class="input" v-model="expiryDateTime" :min="minDate" />
                </form-field>
                <div v-if="clientsWithoutQuote.length == 1">
                    <br>
                    <p>Are you sure you want to send the quote to <b>{{ clientText }}</b>?</p>
                </div>
                <div v-if="clientsWithoutQuote.length > 1">
                    <form-field label="Please select the clients to whom you would like to send the quote.">
                        <control-select :options="clientOptions" v-model="selectedClient" @input="addClient()" />
                    </form-field>
                    <table class="table is-fullwidth">
                        <tbody>
                            <tr v-for="(client, index) in selectedClients" :key="client.client.email">
                                <td class="is-capitalized">{{ client.firstName }} {{ client.lastName }}</td>
                                <td>{{ client.client.email }}</td>
                                <td class="has-text-right">
                                    <a @click="removeClient(index)">
                                        <span class="icon has-text-link">
                                            <i class="fas fa-times"></i>
                                        </span>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <template v-slot:footer>
                <div class="field is-grouped">
                    <control-button @click="close" :disabled="isLoading">Cancel</control-button>
                    <control-button v-if="clientsWithoutQuote.length > 0" @click="confirm" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }" :disabled="selectedClients.length === 0 || !expiryDateTime">Confirm</control-button>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlSelect from '@dashboard/components/form/controls/Select';
import FormField from '@dashboard/components/form/Field';
import Modal from '@dashboard/components/Modal';

export default {
    components: {
        ControlButton,
        ControlSelect,
        FormField,
        Modal,
    },
    props: {
        booking: {
            type: Object,
            required: true
        },
        buttonClass: String
    },
    data() {
        return {
            show: true,
            isLoading: false,
            selectedClient: null,
            selectedClients: [],
            expiryDateTime: null,
        }
    },
    computed: {
        minDate() {
            const today = new Date();
            today.setDate(today.getDate() + 1);
            return today.toISOString().slice(0, 16);
        },
        clientsWithQuote() {
            return this.booking.clients.filter(
                client => (client.pendingFitQuote && Object.keys(client.pendingFitQuote).length > 0) || (client.acceptedFitQuote && Object.keys(client.acceptedFitQuote).length > 0)
            );
        },
        clientsWithoutQuote() {
            return this.booking.clients.filter(
                client => (!client.pendingFitQuote || Object.keys(client.pendingFitQuote).length === 0) && (!client.acceptedFitQuote || Object.keys(client.acceptedFitQuote).length === 0)
            );
        },
        clientText() {
            if (this.clientsWithoutQuote.length === 1) {
                return `${this.clientsWithoutQuote[0].firstName} ${this.clientsWithoutQuote[0].lastName} (${this.clientsWithoutQuote[0].client.email})`;
            }

            return '';
        },
        clientOptions() {
            const options = this.clientsWithoutQuote
                .map((client) => ({
                    text: `${client.firstName} ${client.lastName} (${client.client.email})`,
                    value: client,
                }))
                .filter((option) => !this.selectedClients
                .find((client) => client.client.email === option.value.client.email));

            if (options.length > 1) {
                options.unshift({
                    text: 'Add All Clients',
                    value: 'addAll',
                });
            }

            return options;
        },
    },
    watch: {
        clientsWithoutQuote: {
            handler(newVal) {
                if (newVal.length === 1) {
                    this.selectedClients = [newVal[0]];
                } else if (newVal.length === 2) {
                    this.selectedClients = [];
                }
            },

            immediate: true,
        },
    },
    methods: {
        confirm() {
            this.isLoading = true;
            let expiryDateTime = null;

            if (this.expiryDateTime) {
                expiryDateTime = new Date(this.expiryDateTime).toISOString();
            }

            const payload = {
                clients: this.selectedClients.map(client => client.id),
                expiryDateTime: expiryDateTime
            };

            let request = this.$http.post('/groups/' + this.$route.params.group + '/bookings/' + this.booking.id + '/send-fit-quote', payload)
                .then(() => {
                    this.$emit('fitQuoteSent', this.booking);

                    this.$store.commit('notification', {
                        type: 'success',
                        message: 'The Quote has been sent.'
                    });
                })
                .catch(error => {
                    if (error.response && error.response.status === 422) {
                        const errors = error.response.data.errors;
                        let firstError = Object.values(errors)[0][0];

                        this.$store.commit('notification', {  
                            type: 'danger',
                            message: firstError
                        });
                    }
                });

            request.then(() => {
              this.isLoading = false;
            });
        },
        cancel(client) {
            this.isLoading = true;

            let request = this.$http.post('/groups/' + this.$route.params.group + '/bookings/' + this.booking.id + '/cancel-fit-quote', {fitQuoteId: client.pendingFitQuote.id})
                .then(() => {
                    this.$set(client, 'pendingFitQuote', null);

                    this.$store.commit('notification', {
                        type: 'success',
                        message: 'The Quote has been canceled.'
                    });
                })
                .catch(error => {
                    this.$store.commit('notification', {  
                        type: 'danger',
                        message: 'Something went wrong while canceling the quote.'
                    });
                });

            request.then(() => {
              this.isLoading = false;
            });
        },
        close() {
            this.$emit('canceled');
        },
        addClient() {
            if (this.selectedClient) {
                if (this.selectedClient === 'addAll') {
                    this.addAllClients();
                } else {
                    this.selectedClients.push(this.selectedClient);
                }

                this.selectedClient = null;
            }
        },
        addAllClients() {
            this.clientOptions.forEach((option) => {
                if (option.value !== 'addAll') {
                    this.selectedClients.push(option.value);
                }
            });
        },
        removeClient(index) {
            this.selectedClients.splice(index, 1);
        },
    }
}
</script>
