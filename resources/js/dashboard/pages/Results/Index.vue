<template>
    <card v-if="true" title="Results">
        <data-filters>
            <template v-slot:left>
                <data-filter v-if="meta.total > 25">
                    <pagination-filter v-model="filters.paginate" @input="filterData()" />
                </data-filter>
            </template>
        </data-filters>
        <data-table class="is-size-6" table-class="is-fullwidth" :columns="['Couple / Reservation Leader', 'Supplier / ID', 'Room', 'Guests', 'Type', 'Actions']">
            <template v-if="results.length">
                <tr v-for="result in results">
                    <td>{{ result.name }}</td>
                    <td>{{ result.providerAbbreviation }} / {{ result.providerId }}</td>
                    <td>{{ result.roomNumber }}</td>
                    <td>
                        <ul style="list-style: disc outside; margin-left: 0.5rem;">
                            <li v-for="guest in result.guests" :key="guest.id" :class="{ 'deleted': guest.deleted_at }">
                                {{ guest.firstName }} {{ guest.lastName }}{{ printDateOfBirthIfChild(guest.birthDate, result.date) }} {{ guest.insurance ? '(TI)' : '' }}
                            </li>
                        </ul>
                    </td>
                    <td>{{ result.type }}</td>
                    <td>
                        <a :href="result.url" target="__blank">
                            <i class="fas fa-info-circle"></i>
                        </a>
                    </td>
                </tr>
            </template>
			<tr v-else>
				<td>No records found...</td>
			</tr>
        </data-table>
        <paginator v-if="meta.total > 25" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from" :to="meta.to" :total="meta.total" />
    </card>
</template>

<script>
    import Card from '@dashboard/components/Card';
    import DataTable from '@dashboard/components/table/Table';
    import DataFilter from '@dashboard/components/table/Filter';
    import DataFilters from '@dashboard/components/table/Filters';
    import PaginationFilter from '@dashboard/components/pagination/Filter';
    import Paginator from '@dashboard/components/pagination/Paginator';

    export default {
        components: {
            Card,
            DataTable,
            DataFilter,
            DataFilters,
            PaginationFilter,
            Paginator,
        },
        data() {
            return {
                results: [],
                meta: {},
                filters: {
                    paginate: 25,
                    page: 1,
                },
            }
        },
        created() {
            this.filters = Object.assign({}, this.filters, this.$route.query);
            this.setBreadcrumbs();
            this.fetchData();
        },
        watch: {
            '$route'(to, from) {
                if (to.name === 'results') {
                    this.filters = Object.assign({}, this.filters, to.query);
                    this.fetchData();
                }
            }
        },
        computed: {
            query() {
                return {
                    ...this.filters
                };
            }
        },
        methods: {
            fetchData() {
                this.$http.get('/results', {
                    params: this.query
                })
                    .then(response => {
                        let data = response.data;

                        if(response.data.data.length == 1) {
                            window.location.href = response.data.data[0].url;
                        } else {
                            this.results = data.data;
                            this.meta = data.meta || {};
                            this.setBreadcrumbs();
                        }
                    });
            },
            setBreadcrumbs() {
                this.$store.commit('breadcrumbs', [
                    {
                        label: 'Dashboard',
                        route: 'home'
                    },
                    {
                        label: 'Results',
                        route: 'results'
                    }
                ]);
            },
            filterData(page = '1') {
                this.$set(this.filters, 'page', page);

                if (JSON.stringify(this.$route.query) !== JSON.stringify(this.filters)) {
                    this.$router.replace({
                        query: this.filters
                    });

                    this.fetchData();
                }
            },
            printDateOfBirthIfChild(birthDate, date) {
                let age = this.$moment(date).diff(birthDate, 'years');
                return (age < 18) ? ` (${this.$moment(birthDate).format('MM/DD/YYYY')})` : '';
            }
        }
    }
</script>
