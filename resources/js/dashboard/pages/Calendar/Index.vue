<template>
  <card title="Calendar">
    <div class="field">
      <div class="field-body mb-4">
        <div class="field ">
          <label class="label">Filter by Event Type</label>
          <div class="control">
            <v-select
              v-model="calendar_events_filter"
              :options="calendar_events"
              multiple
              :filterable="true"
              label="name"
              :reduce="calendar_event => calendar_event.id"
              placeholder="Select event types to filter"
              @input="filterEvents"
            />
          </div>
        </div>
        <div class="field ">
          <label class="label">Filter by Year</label>
          <div class="control">
            <v-select
              :options="yearOptions"
              v-model="selectedYear"
              placeholder="Select a year"
              :clearable="false"
            />
          </div>
        </div>
      </div>
    </div>
    <div class="field mb-4">
      <label class="label">Search Events</label>
      <div class="field has-addons">
        <div class="control is-expanded">
          <input
            type="text"
            class="input"
            v-model="eventSearch"
            placeholder="Search Events"
          />
        </div>
        <div class="control">
          <button class="button is-info" @click="fetchEvents">
            Search
          </button>
        </div>
      </div>
    </div>

    <FullCalendar ref="fullCalendar" :options="calendarOptions" />

    <div class="modal" :class="{ 'is-active': showModal }">
      <div class="modal-background" @click="closeModal"></div>
      <div class="modal-card">
        <header class="modal-card-head">
          <p class="modal-card-title">{{ isEditMode ? 'Edit Event' : 'Add New Event' }}</p>
          <button class="delete" aria-label="close" @click="closeModal"></button>
        </header>
        <section class="modal-card-body">
          <div class="has-text-right mb-4">
            <a v-if="redirectionUrl" :href="redirectionUrl" class="button is-info" target="_blank">Go to Booking</a>
            <button v-if="isEditMode" class="button is-danger ml-2" @click="handleDelete" :class="{ 'is-loading': isLoading }" :disabled="isLoading">Delete Event</button>
          </div>
          <form @submit.prevent="handleSubmit">
            <div class="field">
              <label class="label is-required">Title</label>
              <div class="control">
                <input class="input" v-model="form.title" type="text" required />
              </div>
              <p class="help is-danger" v-if="errors.title">{{ errors.title[0] }}</p>
            </div>
            <div class="field">
              <label class="label">Description</label>
              <div class="control">
                <textarea class="textarea" v-model="form.description" required></textarea>
              </div>
              <p class="help is-danger" v-if="errors.description">{{ errors.description[0] }}</p>
            </div>
            <div class="field">
              <label class="label is-required">Start Date</label>
              <div class="control">
                <input class="input" v-model="form.start_date" type="date" :min="new Date().toISOString().split('T')[0]" required />
              </div>
              <p class="help is-danger" v-if="errors.start_date">{{ errors.start_date[0] }}</p>
            </div>
            <div class="field">
              <label class="label is-required">End Date</label>
              <div class="control">
                <input class="input" v-model="form.end_date" type="date" :min="new Date().toISOString().split('T')[0]" :max="endDateMax" required />
              </div>
              <p class="help is-danger" v-if="errors.end_date">{{ errors.end_date[0] }}</p>
            </div>
            <div class="field">
              <label class="label">Booking</label>
              <div class="control">
                <v-select
                  v-model="form.booking_id"
                  :options="bookings"
                  :filterable="true"
                  @open="onOpen"
                  label="label"
                  :reduce="booking => booking.id"
                  @search="(query) => (search = query)"
                >
                  <template #list-footer>
                    <li v-if="hasMoreBookings" class="btn-loadmore">
                      <button type="button" @click="fetchBookings(null)" :disabled="isLoading" class="button btn-loadmore">Load More</button>
                    </li>
                  </template>
                </v-select>
                <small>If you want to add an event about a specific booking, select a booking from the dropdown otherwise leave it empty</small>
              </div>
              <p class="help is-danger" v-if="errors.booking_id">{{ errors.booking_id[0] }}</p>
            </div>
            <div class="field">
              <label class="label is-required">Calendar Event Type</label>
              <div class="control">
                <v-select
                  v-model="form.calendar_event_id"
                  :options="calendar_events"
                  :filterable="true"
                  label="name"
                  :reduce="calendar_event => calendar_event.id"
                >
                </v-select>
              </div>
              <p class="help is-danger" v-if="errors.calendar_event_id">{{ errors.calendar_event_id[0] }}</p>
            </div>
          </form>
        </section>
        <footer class="modal-card-foot">
          <button v-if="isEditMode" class="button is-inverted is-primary" @click="handleSubmit(false)" :class="{ 'is-loading': isLoading }" :disabled="isLoading">Copy</button>
          <button class="button is-primary" @click="handleSubmit(true)" :class="{ 'is-loading': isLoading }" :disabled="isLoading">Save</button>
          <button class="button" @click="closeModal" :disabled="isLoading">Cancel</button>
        </footer>
      </div>
    </div>
  </card>
</template>
<script>
  import FullCalendar from '@fullcalendar/vue';
  import dayGridPlugin from '@fullcalendar/daygrid';
  import timeGridPlugin from '@fullcalendar/timegrid';
  import interactionPlugin from '@fullcalendar/interaction';
  import Card from '@dashboard/components/Card';
  import VSelect from 'vue-select';
  import 'vue-select/dist/vue-select.css';
  import debounce from 'lodash/debounce';

  export default {
    name: 'CalendarComponent',

    components: {
      FullCalendar,
      Card,
      'v-select': VSelect
    },

    data() {
      return {
        showModal: false,
        isEditMode: false,
        form: {
          event_id: '',
          title: '',
          description: '',
          start_date: '',
          end_date: '',
          booking_id: '',
          calendar_event_id: '',
        },
        errors: {},
        redirectionUrl: null,
        bookings: [],
        search: '',
        limit: 20,
        currentPage: 1,
        hasMoreBookings: true,
        isLoading: false,
        selectedYear: new Date().getFullYear(),
        yearOptions: Array.from({ length: (new Date().getFullYear()+ 25) - 2021 + 1 }, (_, i) => 2021 + i),
        suppressYearWatcher: false,
        hasFetched: false,
        calendar_events: [],
        calendar_events_filter: [],
        eventSearch: '',
        calendarOptions: {
          plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
          initialView: 'dayGridMonth',
          eventOrderStrict: true,
          eventOrder: 'calendar_event_type',
          events: [],
          eventClick: this.handleEventClick,
          datesSet: this.handleDatesSet,
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'addEventButton dayGridMonth,timeGridWeek,timeGridDay',
          },
          customButtons: {
            addEventButton: {
              text: 'Add Event',
              click: this.openModal,
            },
          },

          eventContent: function(info) {
            const titleEl = document.createElement('div');
            const dueAmountEl = document.createElement('div');
            titleEl.innerHTML = info.event.title;

            if (info.event.extendedProps.due_amount) {
              dueAmountEl.innerHTML = `Due Amount: ${info.event.extendedProps.due_amount}`;
            }

            const containerEl = document.createElement('div');
            containerEl.appendChild(titleEl);
            containerEl.appendChild(dueAmountEl);

            return { domNodes: [containerEl] };
          }
        }
      };
    },

    created() {
      this.fetchEvents();
      this.debouncedFetchBookings = debounce(this.fetchBookings, 500);
      this.fetchCalendarEvents();
    },

    computed: {
      hasNextPage() {
        return this.hasMoreBookings;
      },

      endDateMax() {
        if (!this.form.start_date) return null;
        const start = new Date(this.form.start_date);
        const maxDate = new Date(start);
        maxDate.setDate(start.getDate() + 14);
        return maxDate.toISOString().split('T')[0];
      }
    },

    methods: {
      filterEvents() {
        this.fetchEvents();
      },

      fetchEvents() {
        this.isLoading = true;

        this.$http.get(`/calendar?calendar_events_filter=${this.calendar_events_filter}&search=${encodeURIComponent(this.eventSearch)}&year=${this.selectedYear}`)
          .then((response) => {
            this.calendarOptions.events = response.data;
          })
          .finally(() => {
            this.isLoading = false;
          });
      },

      fetchBookings(bookingId = null) {
        if (!this.hasMoreBookings || this.isLoading) return;
        this.isLoading = true;

        this.$http.get(`/calendar/bookings?page=${this.currentPage}&search=${this.search.trim()}&booking_id=${bookingId}`)
          .then((response) => {
            const newBookings = response.data.data;

            if (newBookings.length > 0) {
              this.bookings = [...this.bookings, ...newBookings];
              this.currentPage++;
              this.hasFetched = true;
            } else {
              this.hasMoreBookings = false;
            }

            this.isLoading = false;
          }).catch((error) => {
            this.isLoading = false;
          });
      },

      async fetchCalendarEvents() {
        this.$http.get(`all/calendar-events`)
          .then((response) => {
            this.calendar_events = response.data;
          });
      },

      handleSearch(query) {
        this.search = query;
        this.resetBookings();
        this.fetchBookings();
      },

      handleDatesSet(info) {
        const visibleYear = new Date(info.view.currentStart).getFullYear();

        if (this.selectedYear !== visibleYear) {
          this.suppressYearWatcher = true;
          this.selectedYear = visibleYear;
        }
      },

      handleEventClick(info) {
        const groupId = info.event.extendedProps.group_id;
        const bookingId = info.event.extendedProps.booking_id;
        const eventId = info.event.extendedProps.event_id;

        if (eventId) {
          if (groupId && bookingId) {
            this.redirectionUrl = `/groups/${groupId}/bookings/${bookingId}`;
          } else if (bookingId) {
            this.redirectionUrl = `/individual-bookings/${bookingId}`;
          } else {
            this.redirectionUrl = null;
          }

          this.openEditModal(info.event);
        } else {
          if (groupId && bookingId) {
            window.open(`/groups/${groupId}/bookings/${bookingId}`, '_blank');
          } else if (groupId) {
            window.open(`/groups/${groupId}`, '_blank');
          } else if (bookingId) {
            window.open(`/individual-bookings/${bookingId}`, '_blank');
          }
        }
      },

      openModal() {
        this.showModal = true;
        document.body.style.overflow = 'hidden';
      },

      async openEditModal(event) {
        this.errors = {};
        this.currentPage = 1;

        await this.fetchBookings(event.extendedProps.booking_id);

        this.form.event_id = event.extendedProps.event_id;
        this.form.title = event.title;
        this.form.description = event.extendedProps.description;
        this.form.start_date = event.startStr;

        const endDate = new Date(event.endStr);
        endDate.setDate(endDate.getDate() - 1);
        const formattedEndDate = endDate.toISOString().split('T')[0];
        this.form.end_date = formattedEndDate;

        this.form.booking_id = event.extendedProps.booking_id;
        this.form.calendar_event_id = event.extendedProps.calendar_event_type;
        this.isEditMode = true;
        this.showModal = true;

        document.body.style.overflow = 'hidden';
      },

      closeModal() {
        this.showModal = false;
        this.isLoading = false;
        this.resetForm();
        document.body.style.overflow = 'initial';
      },

      resetForm() {
        this.form = {
          event_id: '',
          title: '',
          description: '',
          start_date: '',
          end_date: '',
          booking_id: '',
          calendar_event_id: '',
        };

        this.isEditMode = false;
        this.redirectionUrl = null;
        this.resetBookings();
        this.errors = {};
      },

      resetBookings() {
        this.bookings = [];
        this.currentPage = 1;
        this.hasMoreBookings = true;
      },

      handleSubmit(isNotDuplicate) {
        this.isLoading = true;
        const url = this.isEditMode && isNotDuplicate
          ? '/calendar/events/update'
          : '/calendar/events/store';

        this.$http.post(url, this.form)
          .then((response) => {
            this.fetchEvents();
            this.closeModal();
            this.errors = {};
          })
          .catch((error) => {
            if (error.response && error.response.status === 422) {
              this.errors = error.response.data.errors || {};
            }
            this.isLoading = false;
          });
      },

      handleDelete() {
        if (confirm('Are you sure you want to delete this event?')) {
          this.isLoading = true;
          this.$http.delete('/calendar/events/delete/' + this.form.event_id)
            .then((response) => {
              this.fetchEvents();
              this.closeModal();
            });
        }
      },

      onOpen() {
        if (this.hasNextPage && !this.hasFetched) {
          this.fetchBookings();
        }
      },

      goToSelectedYear(year) {
        if (year) {
          const calendarApi = this.$refs.fullCalendar.getApi();
          calendarApi.gotoDate(`${year}-01-01`);
        }
      }
    },

    watch: {
      search(newSearch, oldSearch) {
        if(newSearch.trim() !== "" && newSearch.trim() !== oldSearch.trim()){
          this.resetBookings();
          this.debouncedFetchBookings();
        }

        if (newSearch.trim() == "") {
          this.resetBookings();
          this.fetchBookings();
        }
      },

      selectedYear(newYear) {
        if (this.suppressYearWatcher) {
          this.suppressYearWatcher = false;
        } else {
          this.goToSelectedYear(newYear);
        }

        this.fetchEvents();
      },
    },
  };
</script>
<style>
  .modal-card-body {
    max-height: 70vh;
    overflow-y: auto;
  }

  .loader {
    text-align: center;
    color: #bbbbbb;
    position: absolute;
    top:0;
  }

  .btn-loadmore{
    display: flex;
    justify-content: center;
  }

  .btn-loadmore button{
    text-decoration: underline;
    border: 0px;
    outline:none;
  }

  .fc-daygrid-event{
    white-space: normal !important;
  }
</style>
