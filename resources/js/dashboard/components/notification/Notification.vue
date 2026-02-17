<template>
<article @mouseover="isHovered = true" @mouseleave="isHovered = false" ref="notification" class="message" :class="'is-' + type">
	<div v-if="timeout" class="notification-progress" :style="{ height: notificationHeight }">
		<div class="progress-bar" :style="{ height: progress + '%' }"></div>
	</div>
	<button class="notification-close" @click="close"><i class="fas fa-times"></i></button>
	<div class="message-body">
		{{ message }}
	</div>
</article>
</template>

<script>
export default {
	props: {
		type: {
			type: String,
			default: 'info'
		},
		message: {
			type: String,
			required: true
		},
		timeout: {
			type: [Number, Boolean],
			default: 8000
		}
	},
	data() {
		return {
			notificationHeight: '0',
			timer: '',
			progress: 100,
			isHovered: false
		}
	},
	mounted() {
		this.notificationHeight = this.$refs.notification.clientHeight + 'px';

		if (this.timeout) {
			this.timer = setInterval(() => this.setProgress(), (this.timeout / 100));
		}
	},
	beforeDestoy() {
		clearInterval(this.timer);
	},
	methods: {
		setProgress() {
			if (!this.progress) {
				this.close();
			}

			if (!this.isHovered) {
				this.progress--;
			}
		},
		close() {
			this.$emit('close');
		}
	}
}
</script>

<style lang="scss">
.message {
    overflow: hidden;

    .notification-close {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 1rem;
        width: 1rem;
        float: right;
        margin-top: 0.25rem;
        margin-right: 0.25rem;
        background: rgba($black, .5);
        border: none;
        border-radius: 50%;
        font-size: 0.5rem;
        color: $white;

        &:hover {
            background: rgba($black, .75);
            cursor: pointer;
        }
    }

    .notification-progress {
        position: relative;
        float: left;
        height: 100%;
        width: 4px;
        transform: rotate(180deg);

        .progress-bar {
            position: relative;
            bottom: 0;
            width: 100%;
            background-color: rgba($black, 0.25);
        }
    }
}
</style>
