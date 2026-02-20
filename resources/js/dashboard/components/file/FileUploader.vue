<template>
		<div class="image-upload-container" :class="{ 'is-over': fileOver, 'is-disabled': disabled }" @dragenter.prevent="fileOver++" @dragleave.prevent="fileOver--" @dragover.prevent.stop @drop.prevent.stop="dropped">
				<div class="image-uploader">
						<div v-if="files.length" class="upload-preview">
								<div class="file is-right is-info">
										<label class="file-label">
												<input
														@change="browsed"
														type="file"
														class="file-input"
														:multiple="!isSingle"
														:disabled="disabled"
														:accept="acceptString"
												>
												<span class="file-cta">
														<span class="file-icon">
																<i class="fas fa-cloud-upload-alt"></i>
														</span>
														<span class="file-label">
																Browse {{ isSingle ? 'File' : 'Files' }}
														</span>
												</span>
										</label>
								</div>
								<div class="image-preview file-preview-container">
										<div v-for="(f, index) in files" :key="index" class="file-item">
												<span class="file-icon-large">
														<i v-if="f.uploading" class="fas fa-spinner fa-spin"></i>
														<i v-else-if="isPDF(f)" class="fas fa-file-pdf"></i>
														<i v-else-if="isWord(f)" class="fas fa-file-word"></i>
														<i v-else class="fas fa-file"></i>
												</span>
												<span class="file-name">{{ f.name }}</span>
												<a v-if="!disabled" @click.prevent="removeFile(index)" class="delete is-small"></a>
										</div>
								</div>
						</div>
						<div v-else class="uploader-instructions">
								<template v-if="!disabled">
										<i class="fas fa-cloud-upload-alt"></i>
										<span>Drop {{ isSingle ? 'file' : 'files' }} to upload, or&nbsp</span>
										<label class="uploader-input-label">
												<input
														@change="browsed"
														type="file"
														class="uploader-input"
														:multiple="!isSingle"
														:disabled="disabled"
														:accept="acceptString"
												>
												<span>Browse</span>
										</label>
								</template>
								<template v-else>
										<span>No {{ isSingle ? 'file' : 'files' }}.</span>
								</template>
						</div>
				</div>
				<div v-if="failedFiles.length" class="failed-upload-container">
						<div v-for="(file, index) in failedFiles" class="columns is-vcentered">
								<span class="column is-size-6 has-text-danger">{{ file.name }}</span>
								<p class="column is-narrow">
										<button @click.prevent="retryFailed(index)" class="button is-small is-outlined is-danger">
												<span class="icon"><i class="fas fa-redo-alt"></i></span>
										</button>
										<button @click.prevent="failedFiles.splice(index, 1)" class="button is-small is-outlined is-danger">
												<span class="icon"><i class="fas fa-times"></i></span>
										</button>
								</p>
						</div>
				</div>
		</div>
</template>

<script>
		export default {
				props: {
						value: [Array, Object],
						disabled: { type: Boolean, default: false },
						isSingle: { type: Boolean, default: false },
						maxSize: { type: Number, default: 1024 },
						acceptedTypes: {type: Array, default: () => ['application/pdf', 'pdf']}
				},
				data() {
						return {
								files: [],
								failedFiles: [],
								fileOver: 0
						}
				},
				created() {
						if (this.value) {
								this.files = this.isSingle ? [this.value] : this.value;
						}
				},
				computed: {
						acceptString() {
							return this.acceptedTypes.join(',');
						},
						size() {
								return (Math.round(this.maxSize / 10.24) / 100) + 'mb';
						},
				},
				methods: {
						dropped(event) {
								this.fileOver--;

								if (this.disabled) return;

								this.uploadMany(this.filter([...event.dataTransfer.files]));
						},
						browsed(event) {
								if (this.disabled) return;

								this.uploadMany(this.filter([...event.target.files]));
						},
						filter(files) {
								this.$emit('errors', []);

								const allowed = files.filter(f => {
										const ext = f.name.split('.').pop().toLowerCase();
										return this.acceptedTypes.includes(f.type) || this.acceptedTypes.includes(ext);
								});

								if (!allowed.length) {
										this.$emit('errors', [`Only ${this.acceptedTypes.join(', ')} files are allowed.`]);

										return [];
								}

								if (this.isSingle && allowed.length > 1) {
										this.$emit('errors', ['You may only upload 1 file.']);

										return [];
								}

								const filtered = allowed.filter(f => f.size <= this.maxSize * 1024);

								if (!filtered.length) {
										this.$emit('errors', [
												`The file(s) exceed maximum size of ${this.size}.`
										]);

										return [];
								}

								if (filtered.length < allowed.length) {
										this.$emit('errors', [
												`${allowed.length - filtered.length} file(s) exceed maximum size of ${this.size}.`
										]);
								}

								return filtered;
						},
						uploadMany(files) {
								files.forEach(f => this.upload(f));
						},
						upload(file) {
								if (this.isSingle) {
										this.files = this.files.filter(f => f.uploading);
								}

								this.files.push({
										name: file.name,
										uploading: true
								});

								this.$storage.store(file, {visibility: 'public-read'}).then(response => {
										const uploadingIndex = this.files.findIndex(f => f.uploading);

										if (uploadingIndex !== -1) {
												this.$set(this.files, uploadingIndex, {
														uuid: response.uuid,
														path: response.key,
														name: file.name,
														mime_type: file.type
												});
										} else {
												this.files.push({
														uuid: response.uuid,
														path: response.key,
														name: file.name,
														mime_type: file.type
												});
										}

										this.$emit('input', this.storedFiles());
								}).catch(() => {
										this.failedFiles.push(file);
										const idx = this.files.findIndex(f => f.uploading);
										if (idx !== -1) this.files.splice(idx, 1);
								});
						},
						storedFiles() {
								const files = this.files.filter(f => !f.uploading);

								if (!files.length) return null;

								return this.isSingle ? files[files.length - 1] : files;
						},
						removeFile(id) {
								this.files.splice(id, 1);
								this.$emit('input', this.storedFiles());
						},
						retryFailed(idx) {
								this.upload(this.failedFiles[idx]);
								this.failedFiles.splice(idx, 1);
						},
						isPDF(file) {
								if (!file || !file.name) return false;

								return file.name.toLowerCase().endsWith('.pdf');
						},
						isWord(file) {
								if (!file || !file.name) return false;

								return file.name.toLowerCase().endsWith('.doc') || file.name.toLowerCase().endsWith('.docx');
						},
				}
		}
</script>

<style scoped>
		.file-tag {
				display: flex;
				align-items: center;
				background: #f5f5f5;
				padding: .5rem;
				margin: .25rem;
				border-radius: 4px;
		}
		.file-tag span {
				margin-right: .5rem;
		}
		.file-preview-container {
				display: flex;
				flex-wrap: wrap;
				margin-top: 0.5rem;
		}
		.file-item {
				display: flex;
				align-items: center;
				background: #f5f5f5;
				padding: .5rem .75rem;
				border-radius: 4px;
				margin: .25rem;
		}
		.file-icon-large {
				font-size: 2rem;
				margin-right: .5rem;
				display: flex;
				align-items: center;
		}
		.file-name {
				margin-right: .5rem;
				font-weight: 600;
				max-width: 100%;
		}
</style>
