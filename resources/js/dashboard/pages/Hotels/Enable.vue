<template>
    <modal @hide="close" title="Enable Hotel" :is-active="true">
        <p>
            Are you sure you want to enable <span class="has-text-weight-semibold">{{ hotel.name }}</span>?
        </p>
        <template v-slot:footer>
            <div class="field is-grouped">
                <control-button @click="close" :disabled="isLoading">Cancel</control-button>
                <control-button @click="confirm" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Yes</control-button>
            </div>
        </template>
    </modal>
    </template>
    
    <script>
    import ControlButton from '@dashboard/components/form/controls/Button';
    import Modal from '@dashboard/components/Modal';
    
    export default {
        components: {
            ControlButton,
            Modal,
        },
        props: {
            hotel: {
                type: Object,
                required: true
            }
        },
        data() {
            return {
                isLoading: false
            }
        },
        methods: {
            confirm() {
                this.isLoading = true;
    
                let request = this.$http.post('/hotels/' + this.hotel.id + '/enable')
                    .then(response => {
                        this.$emit('enabled', this.hotel);
                    });
    
                request.then(() => {
                    this.isLoading = false;
                });
            },
            close() {
                this.$emit('canceled');
            }
        }
    }
    </script>
    