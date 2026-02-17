<template>
<div class="image-upload-container" :class="{ 'is-over': fileOver, 'is-disabled': disabled }" @dragenter.prevent="fileOver++"
	@dragleave.prevent="fileOver--" @dragover.prevent.stop @drop.prevent.stop="dropped">
	<div class="image-uploader">
		<div v-if="images.length" class="upload-preview">
			<div class="file is-right is-info">
				<label class="file-label">
					<input @change="browsed" class="file-input" type="file" accept="image/*" :multiple="!isSingle">
					<span class="file-cta">
						<span class="file-icon">
							<i class="fas fa-cloud-upload-alt"></i>
						</span>
						<span class="file-label">
							Browse {{ isSingle ? 'image' : 'images' }}
						</span>
					</span>
				</label>
			</div>
			<div class="image-preview">
				<thumbnail v-if="isSingle" v-bind="images[images.length - 1]" @removed="removeImage(images.length - 1)" />
				<template v-else>
					<thumbnail v-for="(image, index) in images" :key="index" v-bind="image" @removed="removeImage(index)" />
				</template>
			</div>
		</div>
		<div v-else class="uploader-instructions">
			<template v-if="!disabled">
				<i class="fas fa-cloud-upload-alt"></i>
				<span>Drop {{ isSingle ? 'image' : 'images' }} to upload, or&nbsp</span>
				<label class="uploader-input-label">
					<input @change="browsed" type="file" class="uploader-input" accept="image/*" :multiple="!isSingle">
					<span>Browse</span>
				</label>
			</template>
			<template v-else>
				<span>No {{ isSingle ? 'image' : 'images' }}.</span>
			</template>
		</div>
	</div>
	<div v-if="failedImages.length" class="failed-upload-container">
		<div v-for="(image, index) in failedImages" class="columns is-vcentered">
			<span class="column is-size-6 has-text-danger">{{ image.name }}</span>
			<p class="column is-narrow">
				<button @click.prevent="retryFailed(index)" @mousedown.prevent class="button is-small is-outlined is-danger">
					<span class="icon">
						<i class="fas fa-redo-alt"></i>
					</span>
				</button>
				<button @click.prevent="failedImages.splice(index, 1)" class="button is-small is-outlined is-danger">
					<span class="icon">
						<i class="fas fa-times"></i>
					</span>
				</button>
			</p>
		</div>
	</div>
</div>
</template>

<script>
import Thumbnail from './ImageThumbnail';

export default {
	components: {
		Thumbnail
	},
	props: {
		value: [Array, Object],
		disabled: {
			type: Boolean,
			default: false
		},
		isSingle: {
			type: Boolean,
			default: false
		},
		maxSize: {
			type: Number,
			default: 1024
		}
	},
	data() {
		return {
			images: [],
			failedImages: [],
			fileOver: 0
		}
	},
	created() {
		if (this.value) {
			this.images = this.isSingle ? [this.value] : this.value;
		}
	},
	computed: {
		size() {
			return (Math.round(this.maxSize / 10.24) / 100) + 'mb';
		},
	},
	methods: {
		dropped(event) {
			this.fileOver--;

			if (this.disabled) {
				return;
			}

			this.uploadMany(this.filter(Array.from(event.dataTransfer.files)));
		},
		browsed(event) {
			if (this.disabled) {
				return;
			}

			this.uploadMany(this.filter(Array.from(event.target.files)));
		},
		filter(files) {
			this.$emit('errors', []);

			const unfilteredImages = files.filter(file => {
				return ['image/bmp', 'image/gif', 'image/x-icon', 'image/jpeg', 'image/png', 'image/svg'].includes(file.type);
			});

			if (!unfilteredImages.length) {
				this.$emit('errors', ['Only image files are accepted. (.jpeg, .png, .bmp, .gif, .svg)']);
				return [];
			}

			if (this.isSingle && unfilteredImages.length > 1) {
				this.$emit('errors', ['You may only upload 1 image.']);
				return [];
			}

			const filteredImages = unfilteredImages.filter(image => {
				return image.size <= (this.maxSize * 1024);
			});

			if (!filteredImages.length) {
				this.$emit('errors', [
					'The ' +
					(unfilteredImages.length > 1 ? 'images' : 'image') +
					' could not be uploaded. The maximum file size is ' +
					this.size +
					'.'
				]);
				return [];
			}

			if (filteredImages.length < unfilteredImages.length) {
				const bigCount = unfilteredImages.length - filteredImages.length;
				this.$emit('errors', [
					bigCount +
					(bigCount > 1 ? ' of the images' : ' image') +
					' could not be uploaded. The maximum file size is ' +
					this.size +
					'.'
				]);
			}

			return filteredImages;
		},
		uploadMany(images) {
			images.forEach(image => {
				this.upload(image);
			});
		},
		upload(image) {
			this.images.push({
				uploading: true
			});

			this.$vapor.store(image, {
				visibility: 'public-read'
			}).then(response => {
				this.$set(this.images, this.images.findIndex(image => image.uploading), {
					uuid: response.uuid,
					path: response.key
				});

				this.$emit('input', this.storedImages());
			}).catch(() => {
				this.failedImages.push(image);
				this.images.splice(this.images.findIndex(image => image.uploading), 1);
			});
		},
		storedImages() {
			let images = this.images.filter(image => {
				return !image.uploading;
			});

			if (!images.length) {
				return null;
			}

			return this.isSingle ? images[images.length - 1] : images;
		},
		removeImage(id) {
			this.images.splice(id, 1);
			this.$emit('input', this.storedImages());
		},
		retryFailed(key) {
			this.upload(this.failedImages[key]);
			this.failedImages.splice(key, 1);
		}
	}
}
</script>

<style lang="scss">
.image-upload-container {
    width: 100%;
    padding: 0.25rem;
    background-color: $white;
    border: 3px dashed $dusty-rose;
    border-radius: 4px;

    .image-uploader {
        width: 100%;
        padding: 0.25rem;
        border-radius: 4px;
        text-align: center;

        .upload-preview {
            .file {
                padding: 0.5rem;
                background-color: $dusty-rose;
                border-radius: 4px;

                .file-cta {
                    border-radius: 4px;
                }
            }

            .image-preview {
                display: flex;
                flex-wrap: wrap;
                margin-top: 0.5rem;
            }
        }

        .uploader-instructions {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;

            i {
                padding-right: 1rem;
                font-size: 3rem;
            }

            .uploader-input-label {
                position: relative;
                cursor: pointer;

                .uploader-input {
                    width: 100%;
                    position: absolute;
                    opacity: 0;
                    z-index: -100;
                }

                span {
                    color: $mauve;
                    font-weight: bold;
                    text-decoration: underline;
                    
                    &:hover {
                        color: darken($mauve, 10%);
                    }
                }
            }
        }
    }

    &.is-over {
        .image-uploader {
            background-color: $grey-lighter;
        }
    }

    &.is-danger {
        border-color: $danger;
    }

    &.is-disabled {
        border-width: 1px;
        border-style: solid;

        .upload-preview {
            .file,
            .image-thumbnail a {
                display: none;
            }
        }
    }

    .failed-upload-container {
        margin-top: 0.25rem;
        padding: 0.25rem;
        background-color: $grey-lighter;
        border-radius: 4px;

        .columns:not(:last-child) {
            margin-bottom: -1.25rem;
        }
    }
}
</style>
