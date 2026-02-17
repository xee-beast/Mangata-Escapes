<template>
<div class="loader-container" :class="{ 'loading': dataIsLoading }">
	<div class="heart-loader">
		<div class="heart"></div>
	</div>
</div>
</template>

<script>
export default {
	computed: {
		dataIsLoading() {
			return this.$store.state.dataIsLoading;
		}
	}
}
</script>

<style lang="scss">
.loader-container {
    position: absolute;
    top: 50%;
    left: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 0;
    width: 100%;
    opacity: 0;
    background-color: $white;
    z-index: -10;

    &.loading {
        top: 0;
        height: 100%;
        opacity: 1;
        z-index: 10;
    }

    &:not(.loading) {
        animation: blur 1s ease-out;
    }

    .heart-loader {
        display: inline-block;
        position: relative;
        width: 80px;
        height: 80px;
        transform: rotate(45deg);
        transform-origin: 40px 40px;

        .heart {
            top: 32px;
            left: 32px;
            position: absolute;
            width: 32px;
            height: 32px;
            background: $primary;
            animation: heart-beat 1.2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);

            &:after,
            &:before {
                content: " ";
                position: absolute;
                display: block;
                width: 32px;
                height: 32px;
                background: $primary;
            }

            &:before {
                left: -24px;
                border-radius: 50% 0 0 50%;
            }

            &:after {
                top: -24px;
                border-radius: 50% 50% 0 0;
            }
        }
    }
}

@keyframes blur {
    0% {
        opacity: 1;
        height: 100%;
        top: 0;
        z-index: 10;
    }
    49% {
        opacity: 1;
    }
    99% {
        opacity: 0;
        height: 100%;
        top: 0;
        z-index: 10;
    }
    100% {
        opacity: 0;
        height: 0;
        top: 50%;
        z-index: -10;
    }
}

@keyframes heart-beat {
    0% {
        transform: scale(0.95);
    }
    5% {
        transform: scale(1.1);
    }
    39% {
        transform: scale(0.85);
    }
    45% {
        transform: scale(1);
    }
    60% {
        transform: scale(0.95);
    }
    100% {
        transform: scale(0.9);
    }
}
</style>
