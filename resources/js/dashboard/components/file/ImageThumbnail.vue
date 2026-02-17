<template>
<div class="image-thumbnail">
	<i v-if="uploading" class="spinner fas fa-spinner"></i>
	<div v-else>
		<a @click.prevent="$emit('removed')"><i class="fas fa-times"></i></a>
		<img :src="url" draggable="false">
	</div>
</div>
</template>

<script>
export default {
	props: {
		uploading: {
			type: Boolean,
			default: false
		},
		uuid: String,
		path: String
	},
	computed: {
		url() {
			return process.env.MIX_STORAGE_URL + '/' + this.path;
		}
	}
}
</script>

<style lang="scss">
.image-thumbnail {
    margin: 4px;
    display: flex;
    flex-direction: column;
    height: 150px;
    width: 150px;
    border: 1px solid $grey-lighter;
    border-radius: 4px;
    overflow: hidden;
    justify-content: center;

    i.spinner {
        animation: spinAround 1s linear infinite;
    }

    div {
        position: relative;
        height: 100%;
        width: 100%;

        a {
            height: 20px;
            width: 20px;
            background-color: rgba($grey-dark, 0.2);
            color: $white;
            text-decoration: none;
            border-radius: 5px;
            position: absolute;
            top: 4px;
            right: 4px;

            &:hover {
                background-color: $grey-dark;
            }
        }

        img {
            height: 100%;
            width: 100%;
            object-fit: cover;
        }
    }

}
</style>
