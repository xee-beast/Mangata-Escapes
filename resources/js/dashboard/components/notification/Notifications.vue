<template>
<div class="notifications" :class="position">
	<notification v-for="(notification, index) in notifications" :key="notification.id" v-bind="notification" @close="removeNotification(index)" />
</div>
</template>

<script>
import Notification from './Notification';

export default {
	components: {
		Notification
	},
	props: {
		position: {
			type: String,
			default: 'bottom-right'
		}
	},
	computed: {
		notifications() {
			return this.$store.state.notifications;
		}
	},
	methods: {
		removeNotification(notification) {
			this.$store.commit('removeNotification', notification);
		}
	}
}
</script>

<style lang="scss">
.notifications {
    position: fixed;
    width: 350px;
    max-width: 350px;
    top: 15px;
    right: auto;
    bottom: auto;
    left: 15px;
    z-index: 1000;

    &.bottom-right {
        top: auto;
        right: 15px;
        bottom: 15px;
        left: auto;
    }

    &.bottom-left {
        top: auto;
        right: 15px;
        bottom: auto;
        left: 15px;
    }

    &.top-right {
        top: 15px;
        right: 15px;
        bottom: auto;
        left: auto;
    }

    &.top-left {
        top: 15px;
        right: auto;
        bottom: auto;
        left: 15px;
    }

    @include mobile {
        width: 90%;
        max-width: 90%;
        margin-left: 5%;
        margin-right: 5%;
        right: auto !important;
        left: auto !important;
    }

}
</style>
