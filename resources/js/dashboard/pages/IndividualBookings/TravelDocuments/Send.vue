<template>
    <div>
        <modal @hide="close" title="Send Travel Docs" :is-active="true">
            <p>
                {{ this.booking.clients.length > 1 ? 'Please select the clients to whom you would like to send the travel docs.' : 'Are you sure you want to send the travel docs to ' + this.clientText + '?' }}
            </p>
            <br>
            <div v-if="this.booking.clients.length > 1">
                <form-field>
                    <control-select
                        :options="clientOptions"
                        v-model="selectedClient"
                        @input="addClient()"
                    />
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
            <template v-slot:footer>
                <div class="field is-grouped">
                    <control-button @click="close" :disabled="isLoading">Cancel</control-button>
                    <control-button @click="confirm" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }" :disabled="selectedClients.length === 0">Confirm</control-button>
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
                clientText: '',
                selectedClient: null,
                selectedClients: []
            }
        },
        created() {
            if (this.booking.clients.length === 1) {
                this.clientText = this.clientOptions[0].text;
                this.selectedClients.push(this.clientOptions[0].value);
            }
        },
        computed: {
            clientOptions() {
                const options = this.booking.clients.map((client) => ({
                    text: `${client.firstName} ${client.lastName} (${client.client.email})`,
                    value: client,
                })).filter((option) => !this.selectedClients.find((client) => client.client.email === option.value.client.email));

                if (options.length > 1) {
                    options.unshift({
                        text: 'Add All Clients',
                        value: 'addAll',
                    });
                }

                return options;
            },
        },
        methods: {
            confirm() {
                this.isLoading = true;

                let request = this.$http.post('/individual-bookings/' + this.booking.id + '/send-travel-documents', { 'clients': this.selectedClients.map(client => client.id) })
                    .then(() => {
                        this.$emit('travelDocumentsSent', this.booking);
                        
                        this.$store.commit('notification', {
                            type: 'success',
                            message: 'The travel docs have been sent.'
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
