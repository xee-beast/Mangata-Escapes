<template>
<div class="dropdown is-action" :class="{ 'is-active': isActive, 'is-hoverable': isHoverable }">
	<div class="dropdown-trigger">
		<a @click="toggle" class="table-action" :class="{ 'has-text-danger': hasNotifications }">
			<i class="fas fa-ellipsis-h"></i>
		</a>
	</div>
	<div class="dropdown-menu">
		<div class="dropdown-content">
			<slot />
		</div>
	</div>
</div>
</template>

<script>
export default {
	props: {
		isHoverable: {
			type: Boolean,
			default: false
		},
		hasNotifications: {
			type: Boolean,
			default: false
		}
	},
	data() {
		return {
			isActive: false
		}
	},
	created() {
		window.addEventListener('click', this.close);
	},
	beforeDestroy() {
		window.removeEventListener('click', this.close);
	},
	methods: {
		toggle() {
			this.isActive = !this.isActive;
		},
		close(event) {
			if (!this.$el.contains(event.target)) {
				this.isActive = false;
			}
		}
	}
}
</script>

<style lang="scss">
.dropdown {
    &.is-action {
        .dropdown-menu {
            transform: translateY(-50%);
            top: 50%;
            right: 100%;
            left: auto;
            padding: 0 4px 0 0;
        }
    }

}
</style>
