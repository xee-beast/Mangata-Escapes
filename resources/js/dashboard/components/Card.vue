<template>
<div class="card" :class="{ 'is-cancelled': bookingStatus === 'cancelled' }">
	<header class="card-header">
		<p class="card-header-title">
            <template v-if="$slots.title">
                <slot name="title" />
            </template>
            <template v-else>
                {{ title }}
            </template>
        </p>
		<div class="card-actions">
			<slot name="action" />
		</div>
		<div v-if="$slots.tabs" class="card-tabs">
			<slot name="tabs" />
		</div>
	</header>
	<div class="card-content">
		<slot />
	</div>
	<div v-if="$slots.footer" class="card-footer">
		<slot name="footer" />
	</div>
</div>
</template>

<script>
export default {
	props: {
		title: String,

        bookingStatus: {
            type: String,
            default: 'active'
        },
	}
}
</script>

<style lang="scss">
.card {
    border: 2px solid $dusty-rose;
    border-radius: 4px;
    margin-bottom: 1rem;

    &.is-cancelled {
        border-color: #b5b5b5;

        .card-header {
            background-color: #d9d9d9 !important;
        }
    }

    .card-header {
        flex-flow: row wrap;
        background-color: $dusty-rose;

        .card-actions {
            display: flex;
            align-items: center;
            margin-right: 0.5rem;
            flex-wrap: wrap;
            row-gap: 0.5rem;

            & > .button {
                margin-left: 0.5rem;
                color: $charcoal !important;
            }
        }

        .card-tabs {
            display: flex;
            width: 100%;
            margin-top: 0.5rem;
            padding: 0 0.5rem;
            box-shadow: 0 0.3em 0 0 $scheme-main;
        }
    }

}
</style>
