<template>
    <card title="Deleted Groups">    
        <template v-if="groups">
            <data-filters>
                <template v-slot:left>
                    <data-filter v-if="meta.total > 10">
                        <pagination-filter v-model="filters.paginate" @input="filterData()" />
                    </data-filter>
                </template>
                <data-filter>
                    <form-field>
                        <control-select v-model="filters.agent" @input="filterData()" class="is-small"
                            :options="[ { value: '', text: 'All Agents' }, ...agents ]" default-value="" />
                    </form-field>
                </data-filter>  
                <data-filter>
                    <form-field>
                        <control-select v-model="filters.provider" @input="filterData()" class="is-small"
                            :options="[ { value: '', text: 'All Suppliers' }, ...providers ]" default-value="" />
                    </form-field>
                </data-filter>
                <data-filter>
                    <form-field>
                        <control-input v-model="filters.search" @enter="filterData()" class="is-small" placeholder="Search Deleted Groups" />
                        <template v-slot:addon>
                            <control-button @click="filterData()" class="is-small is-link"><i class="fas fa-search"></i></control-button>
                        </template>
                    </form-field>
                </data-filter>
            </data-filters>
            <data-filters>
                <data-filter>
                    <form-field>
                        <label class="checkbox">
                            <input type="checkbox" v-model="filters.old" @change="filterData()">
                            Show Old
                        </label>
                    </form-field>
                </data-filter>
            </data-filters>
            <data-table class="is-size-6" table-class="is-fullwidth"
                :columns="['Couple', 'Supplier / ID', 'Destination', 'Date', 'Balance Due', 'Agent', 'Actions']">
                <template v-if="groups.length">
                    <tr v-for="group in groups">
                        <th>{{ group.brideLastName }} &amp; {{ group.groomLastName }}</th>
                        <td>{{ group.provider.abbreviation }} / {{ group.providerId }}</td>
                        <td>{{ group.destination.name }} - {{ group.hotels.map(hotel => hotel.name).join(' & ') || 'Pending Hotel' }}</td>
                        <td>{{ $moment(group.eventDate).format('MMMM Do, YYYY') }}</td>
                        <td>{{ $moment(group.dueDate).format('MMMM Do, YYYY') }}</td>
                        <td>{{ group.agent.firstName }} {{ group.agent.lastName }}</td>
                        <td>
                            <a v-if="group.can.view || group.can.update" class="table-action" @click.prevent="show(group.id)">
                                <i class="fas fa-info-circle"></i>
                            </a>
                            <a v-if="group.can.update" class="table-action" @click.prevent="restoreGroup = group">
								<i class="fas fa-trash-restore"></i>
							</a>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td>No records found...</td>
                </tr>
            </data-table>
                <paginator v-if="meta.total > 10" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from"
                    :to="meta.to" :total="meta.total" />
                <restore-group v-if="restoreGroup" :group="restoreGroup" @restored="restored" @canceled="restoreGroup = null" />
            </template>
    </card>
    </template>
    
    <script>
    import Card from '@dashboard/components/Card';
    import ControlButton from '@dashboard/components/form/controls/Button';
    import ControlInput from '@dashboard/components/form/controls/Input';
    import ControlSelect from '@dashboard/components/form/controls/Select';
    import CreateGroup from '@dashboard/pages/Groups/Create';
    import DataFilter from '@dashboard/components/table/Filter';
    import DataFilters from '@dashboard/components/table/Filters';
    import DataTable from '@dashboard/components/table/Table';
    import DeleteGroup from '@dashboard/pages/Groups/Delete';
    import FormField from '@dashboard/components/form/Field';
    import PaginationFilter from '@dashboard/components/pagination/Filter';
    import Paginator from '@dashboard/components/pagination/Paginator';
    import RestoreGroup from '@dashboard/pages/Groups/Restore';

    export default {
        components: {
            Card,
            ControlButton,
            ControlInput,
            ControlSelect,
            CreateGroup,
            DataFilter,
            DataFilters,
            DataTable,
            DeleteGroup,
            FormField,
            PaginationFilter,
            Paginator,
            RestoreGroup
        },
        data() {
            return {
                groups: [],
                agents: [],
                providers: [],
                meta: {},
                can: {},
                filters: {
                    paginate: 10,
                    page: 1
                },
                restoreGroup: null
            }
        },
        created() {
            this.filters = Object.assign({}, this.filters, this.$route.query);
    
            this.fetchData();
        },
        computed: {
            query() {
                return '?' + Object.keys(this.filters).map(key => key + '=' + this.filters[key]).join('&');
            }
        },
        methods: {
            fetchData() {
                this.$http.get('/trash' + this.query)
                    .then(response => {
                        this.groups = response.data.data;
                        this.agents = response.data.agents;
                        this.providers = response.data.providers;
                        this.can = response.data.can;
                        this.meta = response.data.meta;
                        this.setBreadcrumbs();
                    });
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
            setBreadcrumbs() {
                this.$store.commit('breadcrumbs', [{
                        label: 'Dashboard',
                        route: 'home'
                    },
                    {
                        label: 'Deleted Groups',
                        route: 'trash'
                    }
                ]);
            },
            show(id) {
                this.$router.push({
                    name: 'groups.show',
                    params: {
                        id: id
                    }
                });
            },
			restored() {
				window.location.href = '/groups/' + this.restoreGroup.id;
			},
        }
    }
    </script>
    