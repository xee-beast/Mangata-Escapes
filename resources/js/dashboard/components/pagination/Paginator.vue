<template>
<div class="columns is-vcentered">
	<div class="column">
		<span v-if="from && to && total" class="is-size-6">Showing results {{ from }} - {{ to }} of {{ total }}.</span>
	</div>
	<div class="column is-narrow">
		<nav class="pagination is-centered">
			<a @click.prevent="changePage(currentPage - 1)" class="pagination-previous" :disabled="disablePrevious">Previous</a>
			<a @click.prevent="changePage(currentPage + 1)" class="pagination-next" :disabled="disableNext">Next</a>
			<ul class="pagination-list">
				<li><a @click.prevent="changePage(1)" class="pagination-link" :class="{ 'is-current': 1 == currentPage }">1</a></li>
				<li v-if="currentPage > 2"><a class="pagination-ellipsis">&hellip;</a></li>
				<li v-for="index in 3" v-if="showPageNumber(index - 2)">
					<a @click.prevent="changePage(currentPage + index - 2)" class="pagination-link"
						:class="{ 'is-current': index == 2}">{{ currentPage + index - 2 }}</a>
				</li>
				<li v-if="currentPage < (lastPage - 1)"><a class="pagination-ellipsis">&hellip;</a></li>
				<li v-if="lastPage > 1">
					<a @click.prevent="changePage(lastPage)" class="pagination-link"
						:class="{ 'is-current': lastPage == currentPage }">{{ lastPage }}</a>
				</li>
			</ul>
		</nav>
	</div>
</div>
</template>

<script>
export default {
	props: {
		currentPage: Number,
		lastPage: Number,
		from: Number,
		to: Number,
		total: Number

	},
	computed: {
		disablePrevious() {
			return !(this.currentPage > 1);
		},
		disableNext() {
			return !(this.currentPage < this.lastPage);
		}
	},
	methods: {
		showPageNumber(number) {
			return ((this.currentPage + number) > 1) && ((this.currentPage + number) < this.lastPage)
		},
		changePage(newPage) {
			if (newPage >= 1 && newPage <= this.lastPage && newPage != this.currentPage) {
				this.$emit('change', newPage.toString());
			}
		}
	}
}
</script>
