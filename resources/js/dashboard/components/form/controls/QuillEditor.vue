<template>
	<div class="editor-container" :class="{'is-readonly': readonly}">
		<div ref="editor"></div>
	</div>
	</template>
	
	<script>
	import Quill from 'quill';
	
	// Register divider format
	const Embed = Quill.import('blots/block');
	class DividerBlot extends Embed {
		static create() {
			const node = super.create();
			node.setAttribute('class', 'ql-divider');
			return node;
		}
	}
	DividerBlot.blotName = 'divider';
	DividerBlot.tagName = 'div';
	Quill.register(DividerBlot);
	
	export default {
		props: {
			value: {
				type: String,
				default: ''
			},
			readonly: {
				type: Boolean,
				default: false
			}
		},
		data() {
			return {
				quill: null
			}
		},
		watch: {
			value: function() {
				if (this.quill.root.innerHTML != this.value) {
					this.quill.root.innerHTML = this.value;
				}
			},
			readonly: function() {
				if (this.readonly) {
					this.quill.disable();
				} else {
					this.quill.enable();
				}
			}
		},
		mounted() {
			this.quill = new Quill(this.$refs.editor, {
				modules: {
					toolbar: [
						[{
							'header': [false, 1, 2, 3, 4, 5, 6]
						}],
						['bold', 'italic', 'underline'],
						[{
							'color': []
						}],
						[{
							'align': []
						}],
						[{
							'list': 'bullet'
						}, {
							'list': 'ordered'
						}],
						['link'],
						['divider'] // Add divider button
					]
				},
				readOnly: this.readonly,
				theme: 'snow'
			});
	
			// Add handler for divider button
			const toolbar = this.quill.getModule('toolbar');
			toolbar.addHandler('divider', () => {
				const range = this.quill.getSelection(true);
				this.quill.insertText(range.index, '\n', Quill.sources.USER);
				this.quill.insertEmbed(range.index + 1, 'divider', true, Quill.sources.USER);
				this.quill.setSelection(range.index + 2, Quill.sources.SILENT);
			});
	
			this.quill.root.innerHTML = this.value || '';
	
			this.quill.on('text-change', () => {
				this.$emit('input', this.quill.root.innerHTML);
			})
		},
		beforeDestroy() {
			this.quill.off('text-change');
		}
	}
	</script>
	
	<style lang="scss">
	.editor-container {
		.ql-toolbar {
			font-family: inherit !important;
			border: 1px solid $border;
			border-bottom: 0;
			border-top-left-radius: $radius;
			border-top-right-radius: $radius;
		}
	
		.ql-container {
			font-family: inherit !important;
			height: 350px;
			border: 1px solid $border !important;
			border-bottom-left-radius: $radius;
			border-bottom-right-radius: $radius;
		}
	
		&.is-danger {
			.ql-container {
				border-color: $danger !important;
			}
		}
	
		&.is-readonly {
			.ql-toolbar {
				display: none;
			}
		}
	
		.ql-divider {
			border: none;
			border-top: 1px solid $border;
			margin: 1rem 0;
		}
	
		.ql-snow .ql-toolbar button.ql-divider:before {
			content: '—';
		}
	}
	</style>