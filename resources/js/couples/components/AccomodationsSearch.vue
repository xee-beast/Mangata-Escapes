<template>
    <div>
      <div v-if="!group.acceptsNewBookings" class="padding-2-dot-5-rem subtitle is-size-4-mobile has-text-secondary has-text-weight-normal has-text-centered">Sorry, we cannot receive new bookings at this time</div>
        <template v-else>
            <div class="container">
                <div class="is-warm-gray p-50">
                    <div class="field-body mb-15">
                        <div class="field">
                            <label class="input-search-label">Travel Dates</label>
                            <div class="control has-icons-right">
                                <v-date-picker v-model="travelDates" is-range 
                                  :min-date="getStartDate()" :max-date="getEndDate()"
                                  :step="1"
                                  :color="selectedColor"
                                    :columns="$screens({ sm: 2 }, 1)">
                                    <template v-slot="{ inputValue, inputEvents }">
                                        <input
                                            :value="inputValue.start ? inputValue.start + ' - ' + inputValue.end : ''"
                                          v-on="inputEvents.start"
                                          :class="'input is-warm-gray' + (('checkIn' in accommodationSearchErrors) || ('checkOut' in accommodationSearchErrors) ? ' is-danger' : '')"
                                      />
                                    </template>
                                </v-date-picker>
                                <span class="icon is-right">
                                    <i class="fas fa-calendar-alt" style="color:#995C64;"></i>
                                </span>
                            </div>
                          <p v-if="('checkIn' in accommodationSearchErrors) || ('checkOut' in accommodationSearchErrors)" class="help is-danger">{{ [...(accommodationSearchErrors['checkIn'] || []), ...(accommodationSearchErrors['checkOut'] || [])][0] }}</p>
                        </div>
                        <div class="field">
                          <label class="input-search-label">Adults</label>
                          <div class="control">
                              <control-input type="number" v-model="accommodationSearch.adults" class="is-warm-gray"/>
                            </div>
                          <p v-if="'adults' in accommodationSearchErrors" class="help is-danger">{{ accommodationSearchErrors['adults'][0] }}</p>
                        </div>
                        <div class="field">
                          <label class="input-search-label">Children</label>
                          <div class="control">
                              <control-input type="number" v-model="accommodationSearch.children" @input="setBirthDatesInput" class="is-warm-gray"/>
                            </div>
                          <p v-if="'children' in accommodationSearchErrors" class="help is-danger">{{ accommodationSearchErrors['children'][0] }}</p>
                        </div>
                    </div>
                    <div class="field-body mb-15">
                        <div class="field" v-for="(date, index) in accommodationSearch.birthDates">
                            <label class="label">Child {{ (index + 1).toString().padStart(2, '0') }} birth date</label>
                            <div class="control">
                                <v-date-picker value="date" @input="setBirthDate(index, $event)"
                                  :max-date="getCurrentDate()"
                                  :popover="{ visibility: 'focus' }"
                                  :color="selectedColor"
                                  >
                                    <template v-slot="{ inputValue, inputEvents }">
                                      <input
                                          placeholder="MM/DD/YYYY"
                                            :class="'input is-warm-gray' + ((`child.${index}.birthDate` in accommodationSearchErrors) ? ' is-danger' : '')"
                                          :value="inputValue"
                                          v-on="inputEvents"
                                      />
                                    </template>
                                </v-date-picker>
                            </div>
                          <p v-if="`birthDates.${index}` in accommodationSearchErrors" class="help is-danger">{{ accommodationSearchErrors[`birthDates.${index}`][0] }}</p>
                        </div>
                    </div>
                    <div class="field-body">
                        <div class="field fade-in">
                            <button class="button" @click="searchAccommodations">
                                Search Accommodations
                            </button>
                        </div>
                    </div>
                  <div v-if="isLoading" class="padding-2-dot-5-rem subtitle is-size-4-mobile has-text-secondary has-text-weight-normal has-text-centered">Loading Rooms...</div>
                    <div v-if="!isLoading" class="container">
                        <div v-if="group.hotels && group.hotels.length > 0" class="hotels-search-results">
                            <div class="search-result-room mb-60" v-for="hotel in group.hotels">
                                <div v-if="hotel.rooms" class="rooms">
                                    <div class="room-card" v-for="room in hotel.rooms">
                                        <div class="room-card-layout">
                                            <div class="room-card-image-column">
                                              <img v-if="room.image" :src="room.image.storagePath" :alt="room.name + ', ' + hotel.name" loading="lazy" class="room-card-image">
                                            </div>
                                            <div class="room-card-details-column">
                                                <h1 class="room-card-hotel-name">{{ hotel.name.toUpperCase() }}</h1>
                                                <h3 class="room-card-room-name">{{ room.name }}</h3>

                                                <div class="room-card-info-table">
                                                    <div class="room-card-info-row">
                                                        <span class="room-card-info-label">Room Size</span>
                                                      <span class="room-card-info-value">{{ room.size || 'N/A' }}</span>
                                                    </div>
                                                    <div class="room-card-info-row">
                                                        <span class="room-card-info-label">Room View</span>
                                                      <span class="room-card-info-value">{{ room.view || 'N/A' }}</span>
                                                    </div>
                                                    <div class="room-card-info-row">
                                                        <span class="room-card-info-label">Bedding Type</span>
                                                      <span class="room-card-info-value">{{ formatBeds(room.beds) }}</span>
                                                    </div>
                                                    <div class="room-card-info-row">
                                                        <span class="room-card-info-label">Max Occupancy</span>
                                                        <span class="room-card-info-value">{{ room.formattedMaxOccupancy }}</span>
                                                    </div>
                                                </div>

                                                <div v-if="room.soldOut" class="room-card-sold-out">
                                                    <p class="has-text-weight-bold has-text-danger">Sold Out</p>
                                                </div>
                                                <div v-else class="room-card-booking-section">
                                                    <div v-if="!group.is_fit" class="room-card-pricing">
                                                        <span class="room-card-dates">{{ room.dates }}:</span>
                                                        <div class="room-card-price-display">
                                                            <span class="price-label">Price:</span>
                                                          <span class="price-value">${{ room.subTotal.toFixed(2) }}</span>
                                                        </div>
                                                    </div>
                                                  <booking-form :group=group :hotels=hotels :countries=countries :customBooking=accommodationSearch :customHotel="room.hotelId" :customRoom="room.roomId" customCssClass="room-card-book-now-btn" customButtonText="BOOK NOW" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                      <div v-if="0 == group.hotels.length" class="padding-2-dot-5-rem subtitle is-size-4-mobile has-text-secondary has-text-weight-normal has-text-centered">Sorry. We don't have rooms according your search.</div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import ControlInput from '@dashboard/components/form/controls/Input';
import Glide from '@glidejs/glide';

export default {
    components: {
        ControlInput
    },
    props: {
        group: {
            type: Object,
            required: true
        },
        hotels: {
            type: Array,
            required: true
        },
        countries: {
            type: Array,
            required: true
        }

    },
    data() {
        return {
            options: [
              {name: 0, value: 0},
              {name: 1, value: 1},
              {name: 2, value: 2},
              {name: 3, value: 3},
              {name: 4, value: 4},
              {name: 5, value: 5},
              {name: 6, value: 6},
              {name: 7, value: 7},
              {name: 8, value: 8},
              {name: 9, value: 9},
              {name: 11, value: 11},
              {name: 12, value: 12},
              {name: 13, value: 13},
              {name: 14, value: 14},
              {name: 15, value: 15},
              {name: 16, value: 16},
              {name: 17, value: 17},
            ],
            accommodationSearch: {
                checkIn: null,
                checkOut: null,
                adults: 0,
                children: 0,
                birthDates: [],
            },
            accommodationSearchErrors: {},
            show: false,
            isLoading: false,
            selectedColor: 'pink',
        }
    },
    computed: {
        travelDates: {
            get() {
                return {
                    start: this.accommodationSearch.checkIn,
                    end: this.accommodationSearch.checkOut
                }
            },
            set(dates) {
              if (! dates) {
                    this.accommodationSearch.checkIn = null;
                    this.accommodationSearch.checkOut = null;

                    return;
                }

                this.accommodationSearch.checkIn = dates.start instanceof Date ? dates.start.toDateString() : null;
                this.accommodationSearch.checkOut = dates.end instanceof Date ? dates.end.toDateString() : null;
            }
        }
    },
    methods: {
        formatBeds(beds) {
            if (!beds || !beds.length) return 'N/A';
            return beds.join(' or ');
        },
        getStartDate() {
            var date = new Date(this.group.date);
            date.setDate(date.getDate() - 10);
            return date;
        },
        getEndDate() {
            var date = new Date(this.group.date);
            date.setDate(date.getDate() + 10);
            return date;
        },
        getCurrentDate() {
            return new Date();
        },
        setBirthDatesInput() {
            let count = this.accommodationSearch.children;
            let current = this.accommodationSearch.birthDates.length;

            if (current < count) {
                for (let i = current; i < count; i++) {
                    this.accommodationSearch.birthDates.push(null);
                }
            } else if (current > count) {
                this.accommodationSearch.birthDates.splice(count);
            }
        },
        setBirthDate(index, date) {
            this.accommodationSearch.birthDates[index] = date instanceof Date ? date : null;
        },
        searchAccommodations() {
            this.accommodationSearchErrors = {};
            this.isLoading = true;

            let request = this.$http.post(`/groups/${this.group.id}/new-booking/search`, this.accommodationSearch)
                .then(response => {
                    this.group.hotels = response.data;

                  setTimeout(function() {
                        [].forEach.call(document.querySelectorAll('.hotel-images .glide'), glider => {
                            function toggleGlider(glide, slides) {
                                let width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

                                if ((width <= 768 && slides <= 1) || (width > 768 && width <= 1024 && slides <= 2) || (width > 1024 && slides <= 4)) {
                                    glide.disable();
                                } else if (glide.disabled) {
                                    glide.enable();
                                }
                            }

                            let slides = glider.querySelectorAll('.glide__slide').length;

                            if (slides) {
                                let glide = new Glide(glider, {
                                    type: 'carousel',
                                    perView: (slides < 4 ? slides : 4),
                                    gap: 24,
                                    autoplay: 2500,
                                    keyboard: false,
                                    animationDuration: 1000,
                                    breakpoints: {
                                        1024: {
                                            perView: (slides < 2 ? slides : 2)
                                        },
                                        768: {
                                            perView: 1
                                        }
                                    }
                                });

                                glide.mount();
                                toggleGlider(glide, slides);

                                window.addEventListener('resize', () => {
                                    toggleGlider(glide, slides);
                                });
                            }
                        });
                    }, 1000);
                })
                .catch(error => {
                    if (error.response.status == 422) {
                        this.accommodationSearchErrors = error.response.data.errors;
                    }

                    this.group.hotels = [];
                });

            request.then(() => {
                this.isLoading = false;
            });
        }
    }
}
</script>
