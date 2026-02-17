<template>
<div class="modal" :class="{ 'is-active': isActive }">
	<div class="modal-background"></div>
	<div class="modal-card">
		<header class="modal-card-head">
			<p class="modal-card-title">{{ title }}</p>
			<button @click="$emit('hide')" class="modal-card-close"><i class="fas fa-times"></i></button>
		</header>
		<section class="modal-card-body">
			<slot />
		</section>
		<footer v-if="$slots.footer" class="modal-card-foot">
			<slot name="footer"></slot>
		</footer>
	</div>
</div>
</template>

<script>
export default {
	props: {
		title: String,
		isActive: {
			type: Boolean,
			default: false
		}
	}
}
</script>

<style lang="scss">
.modal {
    .modal-card {
        @include mobile {
            max-height: 100%;
            height: 100%;
        }

        .modal-card-head {
            background-color: $primary;

            .modal-card-close {
                background: none;
                border: none;

                &:hover {
                    cursor: pointer;
                }
            }
        }

        .modal-card-foot {
            justify-content: flex-end;
        }
    }
}
</style>
