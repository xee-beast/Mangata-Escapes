<template>
	<card title="Clients" :booking-status="booking && booking.deletedAt ? 'cancelled' : 'active'">
		<template v-slot:action>
			<create-client v-if="can.create" :countries="countries" @created="fetchData()" button-class="is-outlined is-primary is-inverted" />
		</template>
		<template v-if="clients">
			<data-table class="is-size-6" table-class="is-fullwidth" :columns="['Client', 'Reservation Code', 'Email', 'Phone', 'Card On File', 'Travel Insurance', 'Actions']">
				<template v-if="clients.length">
					<tr v-for="client in clients">
						<th>{{ client.firstName }} {{ client.lastName }}</th>
						<td>{{ client.reservationCode }}</td>
						<td>
							{{ client.client.email }}
							<a @click.prevent="clip({key: 'Email', value: client.client.email})" class="has-text-grey-dark">
								<span class="icon"><i class="fas fa-copy"></i></span>
							</a>
						</td>
						<td>{{ client.phone }}</td>
						<td v-if="client.card"><span class="is-capitalized">{{ client.card.type }}</span> ending in {{ client.card.lastDigits }}.</td>
						<td v-else>-</td>
						<td v-if="client.insurance != null">{{ client.insurance ? 'Accepted' : 'Declined' }}{{ client.insuranceSignedAt != null ? ` at ${$moment(client.insuranceSignedAt).format('MM/DD/YYYY HH:mm:ss')}` : '' }}</td>
						<td v-else>Pending</td>
						<td>
							<a v-if="client.can.view || client.can.update" class="table-action" @click.prevent="show(client.id)">
								<i class="fas fa-info-circle"></i>
							</a>
							<a v-if="client.can.delete" class="table-action" @click.prevent="deleteClient = client">
								<i class="fas fa-trash"></i>
							</a>
							<table-actions :has-notifications="client.can.viewPayments && !!client.pendingPayments">
								<div class="dropdown-item" v-if="client.can.viewPayments">
									<router-link :to="{ name: 'individual-bookings.payments', params: {id: booking.id} }" class="table-action">
										View Payments
										<span v-if="client.pendingPayments" class="notification-counter is-text">
											{{ client.pendingPayments }}
										</span>
									</router-link>
								</div>
							</table-actions>
						</td>
					</tr>
				</template>
				<tr v-else>
					<td>No records found...</td>
				</tr>
			</data-table>

			<delete-client v-if="deleteClient" :client="deleteClient" @deleted="deleted" @canceled="deleteClient = null" />
		</template>
	</card>
</template>

<script>
	import Card from '@dashboard/components/Card';
	import CreateClient from '@dashboard/pages/IndividualBookings/Clients/Create';
	import DataTable from '@dashboard/components/table/Table';
	import DeleteClient from '@dashboard/pages/IndividualBookings/Clients/Delete';
	import TableActions from '@dashboard/components/table/Actions';

	export default {
		components: {
			Card,
			CreateClient,
			DataTable,
			DeleteClient,
			TableActions,
		},
		data() {
			return {
				clients: [],
				countries: null,
				booking: null,
				can: {},
				deleteClient: null,
			}
		},
		created() {
			this.fetchData();
		},
		methods: {
			fetchData() {
				this.$http.get(`/individual-bookings/${this.$route.params['id']}/clients`)
					.then(response => {
						this.clients = response.data.data;
						this.countries = response.data.countries;
						this.booking = response.data.booking,
						this.can = response.data.can;

						this.setBreadcrumbs();
					});
			},
			setBreadcrumbs() {
				this.$store.commit('breadcrumbs', [
					{
						label: 'Dashboard',
						route: 'home'
					},
					{
						label: 'Individual Bookings',
						route: 'individual-bookings'
					},
					{
						label: '#' + this.booking.order + ' ' + this.booking.reservationLeaderFirstName + ' ' + this.booking.reservationLeaderLastName,
						route: 'individual-bookings.show',
						params: {
							id: this.booking.id
						}
					},
					{
						label: 'Clients',
						route: 'individual-bookings.clients',
						params: {
							id: this.booking.id
						}
					}
				]);
			},
			show(id) {
				this.$router.push({
					name: 'individual-bookings.clients.show',
					params: {
						id: this.booking.id,
						client: id,
					}
				});
			},
			deleted() {
				this.deleteClient = null;
				this.fetchData();
			},
			clip(message) {
				let copyElement = document.createElement('textarea');
				copyElement.style.position = 'absolute';
				copyElement.style.top = '-999px';
				copyElement.innerText = message.value;

				document.body.appendChild(copyElement);

				copyElement.select();

				document.execCommand('copy');
				document.body.removeChild(copyElement);

				this.$store.commit('notification', {
					type: 'success',
					message: `${message.key} copied to clipboard.`,
					timeout: 2000
				});
			}
		}
	}
</script>
