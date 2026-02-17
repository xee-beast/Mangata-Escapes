<template>
  <card :title="notificationClass ? `Logs: ${notificationClass}` : 'Notification Logs'">
    <template v-slot:action>
      <control-button @click="fetchData" class="is-outlined is-primary is-inverted" :class="{ 'is-loading': isLoading === 'fetchData' }">
        <span class="icon">
          <i class="fas fa-sync-alt"></i>
        </span>
        <span>Refresh</span>
      </control-button>
    </template>

    <template v-if="logs">
      <data-filters>
        <template v-slot:left>
          <data-filter v-if="meta.total > 10">
            <pagination-filter v-model="filters.per_page" @input="filterData()" />
          </data-filter>
        </template>
        <data-filter>
          <form-field>
            <control-select v-model="filters.time_filter" @input="filterData()" class="is-small"
              :options="[
                { value: '', text: 'All Time' },
                { value: 'minute', text: 'Last Minute' },
                { value: '5minutes', text: 'Last 5 Minutes' },
                { value: '30minutes', text: 'Last 30 Minutes' },
                { value: 'hour', text: 'Last Hour' },
                { value: 'day', text: 'Last 24 Hours' },
                { value: 'week', text: 'Last 7 Days' },
                { value: 'month', text: 'Last 30 Days' }
              ]" 
              default-value="" />
          </form-field>
        </data-filter>
      </data-filters>

      <data-table class="is-size-6" table-class="is-fullwidth"
        :columns="['ID', 'Parameters', 'Created At']">
        <tr v-if="logs.length === 0">
          <td colspan="3" class="has-text-centered">No logs found</td>
        </tr>
        <tr v-for="log in logs" :key="log.id">
          <td>{{ log.id }}</td>
          <td class="is-fullwidth">
            <pre class="log-parameters" style="width: 100%; white-space: pre-wrap; word-wrap: break-word;">{{ formatParameters(log.parameters) }}</pre>
          </td>
          <td>{{ formatDate(log.created_at) }}</td>
        </tr>
      </data-table>

      <paginator 
        v-if="meta.total > 10" 
        @change="filterData" 
        :current-page="meta.current_page" 
        :last-page="meta.last_page" 
        :from="meta.from"
        :to="meta.to" 
        :total="meta.total" />
    </template>
  </card>
</template>

<script>
import Card from '@dashboard/components/Card';
import ControlButton from '@dashboard/components/form/controls/Button';
import ControlSelect from '@dashboard/components/form/controls/Select';
import DataFilter from '@dashboard/components/table/Filter';
import DataFilters from '@dashboard/components/table/Filters';
import DataTable from '@dashboard/components/table/Table';
import FormField from '@dashboard/components/form/Field';
import PaginationFilter from '@dashboard/components/pagination/Filter';
import Paginator from '@dashboard/components/pagination/Paginator';

export default {
  name: 'NotificationLogs',
  
  components: {
    Card,
    ControlButton,
    ControlSelect,
    DataFilter,
    DataFilters,
    DataTable,
    FormField,
    PaginationFilter,
    Paginator
  },

  data() {
    return {
      isLoading: '',
      logs: null,
      meta: {},
      filters: {
        per_page: 20,
        page: 1,
        time_filter: ''
      },
      notificationClass: this.$route.params.notification || ''
    };
  },

  created() {
    this.filters = Object.assign({}, this.filters, this.$route.query);
    this.setBreadcrumbs();
    this.fetchData();
  },

  computed: {
    query() {
      return Object.keys(this.filters)
        .filter(key => this.filters[key] !== '' && this.filters[key] !== null)
        .reduce((obj, key) => {
          obj[key] = this.filters[key];
          return obj;
        }, {});
    }
  },

  methods: {
    setBreadcrumbs() {
      this.$store.commit('breadcrumbs', [
        {
          label: 'Dashboard',
          route: 'home'
        },
        {
          label: 'Notifications',
          route: 'notifications'
        },
        {
          label: 'Logs'
        }
      ]);
    },

    fetchData() {
      this.isLoading = 'fetchData';
      
      this.$http.get(`/notifications/logs/${this.notificationClass}`, { params: this.query })
        .then(response => {
          this.logs = response.data.data;
          this.meta = response.data.meta;
          this.isLoading = '';
        })
        .catch(error => {
          console.error('Error fetching notification logs:', error);
          this.$store.commit('error', 'Failed to load notification logs');
        })
        .finally(() => {
          this.isLoading = '';
        });
    },

    filterData(page = '1') {
      this.filters.page = page;
      this.$router.push({
        name: 'notification-logs',
        params: { notification: this.notificationClass },
        query: this.query
      });
      this.fetchData();
    },

    formatDate(dateString) {
      return this.$moment(dateString).format('YYYY-MM-DD HH:mm:ss');
    },

    formatParameters(params) {
      if (!params) return 'No parameters';
      try {
        if (typeof params === 'string') {
          params = JSON.parse(params);
        }
        return JSON.stringify(params, null, 2);
      } catch (e) {
        return 'Invalid parameters';
      }
    }
  }
};
</script>

<style scoped>
.log-parameters {
  max-width: 400px;
  max-height: 150px;
  overflow: auto;
  white-space: pre-wrap;
  word-break: break-word;
  background: #f5f5f5;
  padding: 0.5em;
  border-radius: 4px;
  font-size: 0.9em;
  margin: 0;
}

.pagination-wrapper {
  margin-top: 1.5rem;
  display: flex;
  justify-content: center;
}

.select select {
  min-width: 180px;
}
</style>