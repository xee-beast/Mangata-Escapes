<template>
    <modal @hide="close" title="Change Password" :is-active="true">
        <p style="margin-bottom: 10px;">
            Change password for <span class="has-text-weight-semibold">{{ user.firstName }} {{ user.lastName }}</span>:
        </p>
        <p style="margin-bottom: 10px;">
            <control-input v-model="userData.password" type="password" placeholder="New Password" class="is-lowercase"
				:class="{ 'is-danger': (userErrors.password || []).length }" />
        </p>              
        <template v-slot:footer>
            <div class="field is-grouped">
                <control-button @click="close" :disabled="isLoading">Cancel</control-button>
                <control-button @click="confirm" type="submit" class="is-primary" :class="{ 'is-loading': isLoading }">Ok</control-button>
            </div>
        </template>
    </modal>
    </template>
    
    <script>
    import ControlButton from '@dashboard/components/form/controls/Button';
    import Modal from '@dashboard/components/Modal';
    import ControlInput from '@dashboard/components/form/controls/Input';
    
    export default {
        components: {
            ControlButton,
            Modal,
            ControlInput,
        },
        props: {
            user: {
                type: Object,
                required: true
            },
        },
        data() {
            return {
                isLoading: false,
                userData: {},
                userErrors: [],
            }
        },
        methods: {
            confirm() {
                this.isLoading = true;
    
                let request = this.$http.put('/users/' + this.user.id + '/updatePasswordByAdmin', this.userData)
                    .then(response => {
                        this.$store.commit('notification', {
                            type: 'success',
                            message: 'Password for ' + this.user.firstName + ' ' + this.user.lastName + ' has been updated.'
                        });
                        this.userErrors = [];
                        this.$emit('changed');
                    }).catch(error => {
                        if (error.response.status === 422) {
                            this.userErrors = error.response.data.errors;
                        }
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