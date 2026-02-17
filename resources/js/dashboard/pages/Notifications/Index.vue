<template>
<card title="Notifications">
	<template v-if="notifications">
		<data-table class="is-size-6" table-class="is-fullwidth"
			:columns="['Name', 'Timeline', 'Actions']">
			<template v-if="notifications && notifications.length">
				<tr v-for="(notification, index) in notifications" :key="notification.class">
					<td>{{ notification.name }}</td>
					<td>
                            <span v-if="!notification.is_active" class="has-text-grey">
                                Disabled
                            </span>
                            <template v-else>
                                <span v-if="notification.next_run" class="has-text-grey">
                                    {{ notification.next_run }}
                                </span>
                                <span v-else class="has-text-grey">
                                    Manual
                                </span>
                            </template>
                        </td>
					<td>
						<a class="table-action" @click.prevent="previewNotification(notification)" title="Preview HTML">
							<i class="fas fa-eye"></i>
						</a>
                            <table-actions>
                                <div class="dropdown-item">
                                    <a href="#" class="table-action" @click.prevent="toggleNotification(notification)">
                                        {{ notification.is_active ? 'Disable Notification' : 'Enable Notification' }}
                                    </a>
                                </div>
                                <div class="dropdown-item">
                                    <router-link 
                                        :to="{ 
                                            name: 'notification-logs', 
                                            params: { 
                                                notification: notification.short_class
                                            } 
                                        }" 
                                        class="table-action"
                                    >
                                        Notification Logs
                                    </router-link>
                                </div>
                            </table-actions>
						</td>
				</tr>
			</template>
			<tr v-else>
				<td colspan="3">No notifications found...</td>
			</tr>
		</data-table>
	</template>

	<!-- Preview Modal -->
	<modal :is-active="showPreviewModal" :title="currentNotification ? currentNotification.name : ''" @hide="closePreviewModal">
		<div v-if="isLoadingPreview" class="has-text-centered p-5">
			<span class="icon is-large">
				<i class="fas fa-spinner fa-spin fa-2x"></i>
			</span>
			<p class="mt-3">Loading preview...</p>
		</div>
		<div v-else-if="previewError" class="notification is-danger">
			{{ previewError }}
		</div>
		<div v-else-if="previewHtml" class="notification-preview" v-html="previewHtml"></div>
		<template v-slot:footer>
			<button @click="closePreviewModal" class="button is-dark is-outlined">Close</button>
		</template>
	</modal>
</card>
</template>

<script>
import Card from '@dashboard/components/Card';
import DataTable from '@dashboard/components/table/Table';
import Modal from '@dashboard/components/Modal';
import TableActions from '@dashboard/components/table/Actions';

export default {
	name: 'Notifications',
	components: {
		Card,
		DataTable,
		Modal,
		TableActions
	},

	data() {
		return {
			notifications: null,
			meta: {},
			can: {},
			isLoading: false,
			showPreviewModal: false,
			isLoadingPreview: false,
			previewHtml: null,
			previewError: null,
			currentNotification: null
		}
	},

	created() {
		this.fetchData();
	},

	methods: {
		formatDate(dateString) {
			if (!dateString) return 'N/A';
			const date = new Date(dateString);
			return date.toLocaleDateString('en-US', { 
				month: 'short', 
				day: 'numeric',
				hour: '2-digit',
				minute: '2-digit',
				hour12: true
			});
		},
		formatDateTime(dateString) {
			if (!dateString) return 'N/A';
			const date = new Date(dateString);
			return date.toLocaleString('en-US', { 
				year: 'numeric',
				month: 'long', 
				day: 'numeric',
				hour: '2-digit',
				minute: '2-digit',
				second: '2-digit',
				hour12: true
			});
		},
		setBreadcrumbs() {
			this.$store.commit('breadcrumbs', [{
				label: 'Dashboard',
				route: 'home'
			}, {
				label: 'Notifications',
				route: 'notifications'
			}]);
		},

		fetchData() {
			this.isLoading = true;
			this.$http.get('/notifications')
			.then(response => {
				const data = response.data;
				this.notifications = data.data;
				this.meta = data.meta || {};
				this.can = data.can || {};

				this.setBreadcrumbs();
			})
			.finally(() => {
				this.isLoading = false;
			});
		},

		previewNotification(notification) {
			this.currentNotification = notification;
			this.showPreviewModal = true;
			this.previewError = null;
			this.previewHtml = null;
			this.isLoadingPreview = true;

			// Fetch the preview HTML
			this.$http.get(`/notifications/preview/${encodeURIComponent(notification.class)}`)
				.then(response => {
					this.previewHtml = response.data;
				})
				.catch(error => {
					console.error('Error loading notification preview:', error);
					this.previewError = 'Failed to load preview. Please try again.';
				})
				.finally(() => {
					this.isLoadingPreview = false;
				});
		},

		closePreviewModal() {
			this.showPreviewModal = false;
		},

		toggleNotification(notification) {
			const newStatus = !notification.is_active;
			this.$http.put(`/notifications/status/${encodeURIComponent(notification.class.replace(/\\/g, '_'))}`, {
				is_active: newStatus
			})
			.then(() => {
				notification.is_active = newStatus;
				this.$store.commit('notification', {
					type: 'success',
					message: `Notification ${newStatus ? 'activated' : 'deactivated'} successfully`
				});
			})
			.catch(error => {
				console.error('Error toggling notification status:', error);
				this.$store.commit('notification', {
					type: 'error',
					message: `Failed to ${newStatus ? 'activate' : 'deactivate'} notification`
				});
			});
		}
	}
};
</script>

<style scoped>
.code {
	font-family: monospace;
	background: #f5f5f5;
	padding: 2px 5px;
	border-radius: 3px;
}

.button {
	margin-right: 5px;
}
</style>
