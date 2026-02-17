<template>
<div class="modal-container" :class="{ 'is-active': isActive }">
    <div @click.self="hide" class="modal">
        <div class="modal-content">
            <button @click.prevent="hide" class="modal-close">
                <span class="icon">
                    <i class="fas fa-times"></i>
                </span>
            </button>
            <slot />
        </div>
    </div>
</div>
</template>

<script>
export default {
    props: {
        isActive: {
            type: Boolean,
            required: true
        }
    },
    watch: {
        isActive: (active) => {
            if (active) {
                document.documentElement.classList.add('has-active-modal');
            } else {
                document.documentElement.classList.remove('has-active-modal');
            }
        }
    },
    methods: {
        hide() {
            this.$emit('hide');
        }
    }
}
</script>