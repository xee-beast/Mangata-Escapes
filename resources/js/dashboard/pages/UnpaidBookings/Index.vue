<template>
    <card title="Bookings">
        <data-filters>
			<template v-slot:left>
				<data-filter v-if="meta.total > 10">
					<pagination-filter v-model="filters.paginate" @input="filterData()" />
				</data-filter>
			</template>
		</data-filters>
        <data-table class="is-size-6" table-class="is-fullwidth" :columns="['Group / Reservation Leader', 'Guests', 'Accommodation & Dates', 'Reservation', 'Status', 'Balance', 'Special Requests', 'Notes', 'Type', 'Actions']">
            <template v-if="bookings.length">
                <tr :style="{ color: booking.isPaymentArrangementActive ? '#0095ff' : '' }" v-for="(booking, index) in bookings" :key="booking.id">
                    <th>
                        <template v-if="booking.group">
                            {{ booking.group.brideFirstName + ' ' + booking.group.brideLastName + ' & ' + booking.group.groomFirstName + ' ' + booking.group.groomLastName }}
                        </template>
                        <template v-else>
                            {{ booking.reservationLeaderFirstName + ' ' + booking.reservationLeaderLastName }}
                        </template>
                    </th>
                    <td>
                        <ul style="list-style: disc outside; margin-left: 0.5rem;" :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
                            <template v-for="client in booking.clients">
                                <li v-for="guest in client.guests" :key="guest.id" :class="{ 'deleted': guest.deleted_at }">
                                    <span style="white-space: nowrap;">
                                        {{ guest.firstName }} {{ guest.lastName }}{{ printDateOfBirthIfChild(guest.birthDate, booking.group ? booking.group.eventDate : booking.checkIn) }} {{ guest.insurance ? '(TI)' : '' }}
                                        <img v-if="((booking.group && booking.group.transportation) || (!booking.group && booking.transportation)) && guest.transportation" :src="guest.transportation" src="data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAgAAAAIACAYAAAD0eNT6AAANvUlEQVR4nO3dXVobuRZAUeBjDgzA8x+SB8AofB/SuU0nGNvgcknaa72mQ1TVdaRNOT/Pp9PpCQBoedl7AQDA4wkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEGvey+Adbwfj6e91wCrezscnvdeA2t4Pp3s2VzPIQ/jEgfcQgDwJQc+zEsQ8BUBwH848GFdgoCPBAAOfQgSAwiAMAc/IAS6BECMQx84Rwy0CIAAhz5wKzGwPgGwMAc/8FNCYF0CYEEOfuDehMB6BMBCHPzA1oTAOgTAAhz8wKMJgfn5x4Am5/AH9mDvmZ83AJMyfMAovA2YkwCYjIMfGJUQmIuPACbi8AdGZo+aizcAEzBUwGy8DRifNwCDc/gDM7J3jc8bgEEZHmAV3gaMyRuAATn8gZXY08YkAAZjUIAV2dvG4yOAQRgOoMJHAmPwBmAADn+gxJ43BgGwM4MAFNn79icAdmQAgDJ74L4EwE48+AD2wj0JgB144AH+ZU/chwB4MA86wN/sjY8nAB7IAw5wnj3ysQTAg3iwAS6zVz6OAHgADzTA9eyZjyEANuZBBridvXN7/irgDXmAx+evJG0zo+Mzo9vxBmAjNhaAn7OXbkcAbMADC3A/9tRtCAAACBIAd6ZUAe7P3np/AuCOPKAA27HH3pcAuBMPJsD27LX3IwAAIEgA3IEiBXgce+59CIAf8iACPJ699+cEAAAECYAfUKAA+7EH/4wA+CYPHsD+7MXfJwAAIEgAfIPiBBiHPfl7BAAABAmAGylNgPHYm28nAG7gAQMYlz36NgIAAIIEwJWUJcD47NXXEwAAECQArqAoAeZhz76OAACAIAFwgZIEmI+9+zIBAABBAuALChJgXvbwrwkAAAgSAAAQJADO8OoIYH728vMEAAAECQAACBIAn/DKCGAd9vTPve69ANiTjQGo8gbgDw4EgPXY2/8mAAAgSAAAQJAA+MArIoB12eP/SwAAQJAAAIAgAQAAQQLgHz4bAlifvf5fAgAAggQAAAQJAAAIEgBPPhMCKLHn/yIAACBIAABAkAAAgCABAABBAgAAggQAAATlA8AfBwHosfcLAABIEgAAECQAACBIAABAkAAAgCABAABBAgAAggQAAAQJAAAIEgAAECQAACBIAABA0OveC6h7Oxye914DwB78gzz78gYAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAIAgAQAAQQIAAIIEAAAECQAACBIAABAkAAAgSAAAQJAAAICg12v/w/fj8bTFAt4Oh+ctvi4AjGqrM/Xp6fpz9WIAbLlIAOC+fp/bl0LgbAA4+AFgXpdC4NPfA+DwB4A1nDvT/woAhz8ArOWzs/3l0n8AAMzvzzP+5dwPAABr+XjW+3sAACDo5enJd/8AUPH7zPcGAACCXnz3DwAt78fjyRsAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIOjl7XB43nsRAMDjvB0Oz94AAEDQy9PTrxLYeyEAwPZ+n/neAABA0P8DwFsAAFjbx7P+5dwPAADr+POM/+sjABEAAGv57Gz/9PcAiAAAWMO5M/310k94Px5PWy0KANjGpW/mzwbAn19ACADA+K59i38xAG79ggDA10Y4U/09AAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQJAAAIEgAAECQAACAIAEAAEECAACCBAAABAkAAAgSAAAQ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" alt="Transportation" class="transportation-icon" style="height: 16px; width: auto; margin-left: 4px; vertical-align: middle;">
                                    </span>
                                    <br>
                                    {{ printTravelDates(booking, guest) }}
                                </li>
                            </template>
                        </ul>
                    </td>
                    <td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
                        <template v-if="booking.group">
                            <div v-for="(room, index) in booking.rooms" :key="room.id">
                                {{ room.name + ' - ' + room.hotel.name }}: {{ room.pivot.bed }} <br>
                                {{ $moment.utc(room.pivot.check_in).format('MMM DD') }} - {{ $moment.utc(room.pivot.check_out).format('MMM DD') }}
                                ({{ $moment.utc(room.pivot.check_out).diff($moment.utc(room.pivot.check_in), 'days') }} nights) 
                                <span v-if="index !== booking.rooms.length - 1">
                                    <br><br>
                                </span>
                            </div>
                        </template>
                        <template v-else>
                            <template v-if="booking.roomArrangements.length > 0">
								<span v-for="(roomArrangement, index) in booking.roomArrangements" :key="roomArrangement.id">
									{{ roomArrangement.room }} - {{ roomArrangement.hotel }}: {{ roomArrangement.bed }} <br>
									{{ $moment.utc(roomArrangement.checkIn).format('MMM DD') }} - {{ $moment.utc(roomArrangement.checkOut).format('MMM DD') }} ({{ $moment.utc(roomArrangement.checkOut).diff($moment.utc(roomArrangement.checkIn), 'days') }} nights)
									<span v-if="index !== booking.roomArrangements.length - 1">
										<br><br>
									</span>
								</span>
							</template>
							<template v-else>
								{{ booking.hotelAssistance ? booking.hotelPreferences : booking.hotelName }} - {{ booking.roomCategory ? booking.roomCategoryName : 'Room Category Unspecified' }} <br>
								{{ $moment.utc(booking.checkIn).format('MMM DD') }} - {{ $moment.utc(booking.checkOut).format('MMM DD') }} ({{ $moment.utc(booking.checkOut).diff($moment.utc(booking.checkIn), 'days') }} nights)
							</template>
                        </template>
                    </td>
                    <td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
                        <ul>
                            <li v-for="client in booking.clients" :key="client.id">{{ client.reservationCode }}{{ client.card ? ' : ' + client.card.type.toUpperCase() + ' ' + client.card.lastDigits : '' }}</li>
                        </ul>
                    </td>
                    <td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
                        {{ getBookingStatus(booking) }}
                    </td>
                    <td>
                        <div :class="{ 'is-striked': booking.deletedAt, 'has-text-right': true, 'disabled-text': booking.deletedAt }" style="display: inline-block">
                            ${{ booking.total.toFixed(2) }}
                            <br>
                            - ${{ booking.totalPayments.toFixed(2) }}
                            <hr style="margin: 0">
                            ${{ (booking.total - booking.totalPayments).toFixed(2) }}
                        </div>
                    </td>
                    <td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
                        <a v-if="booking.specialRequests" @click.prevent="showMessage(`Booking #${booking.order} - Special Requests`, booking.specialRequests)">
                            <i class="fas fa-eye"></i>
                        </a>
                        <template v-else>
                            -
                        </template>
                    </td>
                    <td :class="{ 'is-striked': booking.deletedAt, 'disabled-text': booking.deletedAt }">
                        <a v-if="booking.notes" @click.prevent="showMessage(`Booking #${booking.order} - Notes`, booking.notes)">
                            <i class="fas fa-eye"></i>
                        </a>
                        <template v-else>
                            -
                        </template>
                    </td>
                    <td>
                        {{ booking.group ? (booking.group.fit ? 'Group FIT' : '') : 'Individual FIT' }}
                    </td>
                    <td>
                        <template v-if="booking.group">
                            <a v-if="!booking.group.deletedAt && (booking.can.view || booking.can.update)" class="table-action" :class="{'has-text-danger': booking.pendingChanges || booking.guestChanges.length > 0}" :href="`${$dashboardBase}/groups/${booking.group.id}/bookings/${booking.id}`">
                                <i class="fas fa-info-circle"></i>
                            </a>
                            <a v-if="!booking.group.deletedAt && (booking.can.view || booking.can.update)" class="table-action" :class="{'has-text-danger': booking.pendingChanges || booking.guestChanges.length > 0}" :href="`${$dashboardBase}/groups/${booking.group.id}/bookings/${booking.id}`" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <table-actions v-if="!booking.group.deletedAt" :has-notifications="booking.can.viewPayments && !!booking.pendingPayments">
                                <div class="dropdown-item" v-if="booking.can.viewClients">
                                    <router-link :to="{ name: 'clients', params: {group: booking.group.id, booking: booking.id} }" class="table-action">
                                        View Clients
                                    </router-link>
                                </div>
                                <div class="dropdown-item" v-if="booking.can.viewPayments">
                                    <router-link :to="{ name: 'payments', params: {group: booking.group.id, booking: booking.id} }" class="table-action">
                                        View Payments
                                        <span v-if="booking.pendingPayments" class="notification-counter is-text">
                                            {{ booking.pendingPayments }}
                                        </span>
                                    </router-link>
                                </div>
    							<template v-if="!booking.group.fit || (booking.group.fit && (booking.can.confirm || booking.confirmedAt))">
                                    <div class="dropdown-item">
                                        <a :href="booking.invoiceUrl" target="_blank" class="table-action">View Invoice</a>
                                    </div>
                                    <div class="dropdown-item">
                                        <a :href="booking.travelDocumentsUrl" target="_blank" class="table-action">View Travel Docs</a>
                                    </div>
                                </template>
                            </table-actions>
                        </template>
                        <template v-else>
                            <a v-if="booking.can.view || booking.can.update" class="table-action" :class="{'has-text-danger': booking.pendingChanges}" :href="`${$dashboardBase}/individual-bookings/${booking.id}`">
                                <i class="fas fa-info-circle"></i>
                            </a>
                            <a v-if="booking.can.view || booking.can.update" class="table-action" :class="{'has-text-danger': booking.pendingChanges}" :href="`${$dashboardBase}/individual-bookings/${booking.id}`" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <table-actions :has-notifications="booking.can.viewPayments && !!booking.pendingPayments">
                                <div class="dropdown-item" v-if="booking.can.viewClients">
                                    <router-link :to="{ name: 'individual-bookings.clients', params: {id: booking.id} }" class="table-action">
                                        View Clients
                                    </router-link>
                                </div>
                                <div class="dropdown-item" v-if="booking.can.viewPayments">
                                    <router-link :to="{ name: 'individual-bookings.payments', params: {id: booking.id} }" class="table-action">
                                        View Payments
                                        <span v-if="booking.pendingPayments" class="notification-counter is-text">
                                            {{ booking.pendingPayments }}
                                        </span>
                                    </router-link>
                                </div>
    							<template v-if="booking.can.confirm || booking.confirmedAt">                                    
                                    <div class="dropdown-item">
                                        <a :href="booking.invoiceUrl" target="_blank" class="table-action">View Invoice</a>
                                    </div>
                                    <div class="dropdown-item">
                                        <a :href="booking.travelDocumentsUrl" target="_blank" class="table-action">View Travel Docs</a>
                                    </div>
                                </template>
                            </table-actions>
                        </template>
                    </td>
                </tr>
            </template>
            <tr v-else>
                <td>No records found...</td>
            </tr>
        </data-table>
        <paginator v-if="meta.total > 10" @change="filterData" :current-page="meta.current_page" :last-page="meta.last_page" :from="meta.from" :to="meta.to" :total="meta.total" />
        <modal :title="message.title" :is-active="message.show" @hide="message.show = false">
            {{ message.body }}
        </modal>
    </card>
</template>
    
<script>
    import Card from '@dashboard/components/Card';
    import DataTable from '@dashboard/components/table/Table';
    import Modal from '@dashboard/components/Modal';
    import TableActions from '@dashboard/components/table/Actions';
    import PaginationFilter from '@dashboard/components/pagination/Filter';
    import Paginator from '@dashboard/components/pagination/Paginator';
    import DataFilter from '@dashboard/components/table/Filter';
    import DataFilters from '@dashboard/components/table/Filters';
    
    export default {
        components: {
            Card,
            DataTable,
            Modal,
            TableActions,
            PaginationFilter,
            DataFilter,
            DataFilters,
            Paginator,
        },
        
        data() {
            return {
                bookings: [],
    			meta: {},
                filters: {
                    paginate: 10,
                    page: 1,
                },
                message: {
                    title: '',
                    body: '',
                    show: false
                },
            }
        },

        created() {
    		this.filters = Object.assign({}, this.filters, this.$route.query);
            this.fetchData();
        },

        computed: {
            query() {
                return {
                    ...this.filters
                }
            }
        },

        methods: {
            fetchData() {
                this.$http.get('/unpaid-bookings', {params: this.query})
                    .then(response => {
                        this.bookings = response.data.data;
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
                this.$store.commit('breadcrumbs', [
                    {
                        label: 'Dashboard',
                        route: 'home'
                    },
                    {
                        label: 'Unpaid Bookings',
                        route: 'unpaid-bookings'
                    }
                ]);
            },

            getBookingStatus(booking) {
                let status = '';

                if (booking.deletedAt) {
                    status = status + `Cancelled ${this.$moment(booking.deletedAt).calendar().toLowerCase()}.`;
                } else if (booking.confirmedAt) {
                    status = status + `Confirmed ${this.$moment(booking.confirmedAt).calendar().toLowerCase()}.`;
                } else {
                    status = status + `Pending since ${this.$moment(booking.createdAt).calendar().toLowerCase()}.`;
                }

                if ((booking.group && booking.group.fit) || !booking.group) {
                    booking.clients.forEach((client) => {
                        if (client.acceptedFitQuote) {
                            status = status + ` ${client.firstName} ${client.lastName} has accepted their FIT quote.`;
                        } else if (client.pendingFitQuote) {
                            status = status + ` ${client.firstName} ${client.lastName}'s FIT quote is pending.`;
                        } else if (client.discardedFitQuote) {
                            if (client.discardedFitQuote.isCancelled) {
                                status = status + ` ${client.firstName} ${client.lastName}'s FIT quote was cancelled.`;
                            } else {
                                status = status + ` ${client.firstName} ${client.lastName}'s FIT quote has expired.`;
                            }
                        } else {
                            status = status + ` ${client.firstName} ${client.lastName} has not received an FIT quote yet.`;
                        }
                    });	
                }

                return status;
            },

            getAge(birthDate, eventDate) {
                return this.$moment(eventDate).diff(birthDate, 'years');
            },

            printDateOfBirthIfChild(birthDate, eventDate) {
                let age = this.getAge(birthDate, eventDate);
    
                return (age < 18) ? ` (${this.$moment(birthDate).format('MM/DD/YYYY')})` : '';
            },

            printTravelDates(booking, guest) {
				let minCheckIn;
				let maxCheckOut;

                if (booking.group) {
                    const pivotDates = booking.rooms.map(room => ({
                        checkIn: this.$moment.utc(room.pivot.check_in),
                        checkOut: this.$moment.utc(room.pivot.check_out),
                    }));

                    minCheckIn = this.$moment.min(pivotDates.map(d => d.checkIn).filter(Boolean)).format('MM/DD/YYYY');
                    maxCheckOut = this.$moment.max(pivotDates.map(d => d.checkOut).filter(Boolean)).format('MM/DD/YYYY');
                } else {
                    if (booking.roomArrangements.length > 0) {
                    	let roomArrangementDates = booking.roomArrangements.map(room => ({
                    		checkIn: this.$moment(room.checkIn),
                    		checkOut: this.$moment(room.checkOut),
                    	}));

                    	minCheckIn = this.$moment.min(roomArrangementDates.map(d => d.checkIn).filter(Boolean)).format('MM/DD/YYYY');
                    	maxCheckOut = this.$moment.max(roomArrangementDates.map(d => d.checkOut).filter(Boolean)).format('MM/DD/YYYY');
                    } else {
                    	minCheckIn = this.$moment(booking.checkIn).format('MM/DD/YYYY');
                    	maxCheckOut = this.$moment(booking.checkOut).format('MM/DD/YYYY');
                    }
                }

				const guestCheckIn = this.$moment(guest.checkIn).format('MM/DD/YYYY');
				const guestCheckOut = this.$moment(guest.checkOut).format('MM/DD/YYYY');

				if (minCheckIn !== guestCheckIn || maxCheckOut !== guestCheckOut) {
					return `Travel Dates (${this.$moment(guestCheckIn, 'MM/DD/YYYY').format('MMM D')} - ${this.$moment(guestCheckOut, 'MM/DD/YYYY').format('MMM D')})`;
				}

				return '';
			},
            
            showMessage(title, message) {
                this.message.title = title;
                this.message.body = message;
                this.message.show = true;
            },
        }
    }
</script>
<style lang="scss">
    .disabled-text {
        color: #b3b3b3;
    }
    .transportation-icon {
        display: inline-block;
        opacity: 0.7;
    }
</style>
    