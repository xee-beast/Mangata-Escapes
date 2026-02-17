<template>
    <div>
        <modal :is-active="show" title="Important" @hide="close">
            <p>
                {{ message }}
            </p>
            <template v-slot:footer>
                <div class="field is-grouped">
                    <button @click="close" class="button is-dark is-outlined">Close</button>
                </div>
			</template>
        </modal>
    </div>
</template>

<script>
import Modal from '@dashboard/components/Modal';

export default {
    components: {
		Modal
	},
    data() {
        return {
            group: '',
            message: null
        }
    },
    computed: {
        show() {
            return this.message != null;
        }
    },
    methods: {
        close() {
            this.message = null;
        },
        setGroup(route) {
            this.group = route.fullPath.includes('groups/')
                ? (route.params.group ? route.params.group : route.params.id)
                : '';
        },
        getMessage() {
            this.$http.get('/groups/' + this.group)
				.then(response => {
					this.message = response.data.data.staffMessage;
				}).catch(error => {
					if (error.response.status === 403) {
						this.$store.commit('error', {
							status: 403,
							message: error.response.statusText
						});
					}
				});
        }
    },
    mounted() {
        if (this.$route.fullPath.includes('groups/')) {
            this.setGroup(this.$route);
            this.getMessage();
        }
    },
    watch: {
        '$route': function (to, from) {
            this.setGroup(to);

            if (to.fullPath.includes('groups/' + this.group) && !from.fullPath.includes('groups/' + this.group)) {
                this.getMessage();
            }
        }
    }
}
</script>
