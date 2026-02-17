<template>
    <tbody>
        <tr v-if="comparable">
            <th width="30%">{{ index }}</th>
            <td width="35%" :class="{'has-background-danger-light has-text-danger': change.before !== change.after && change.before != null}" v-html="change.before"></td>
            <td width="35%" :class="{'has-background-success-light has-text-success': change.before !== change.after && change.after != null}" v-html="change.after"></td>
        </tr>
        <template v-else>
            <tr v-if="hasComparable(change)" class="has-background-light">
                <th colspan="3">{{ currentIndex }}</th>
            </tr>
            <tr>
                <td colspan="3" class="has-nested-table">
                    <table class="table is-fullwidth is-size-6">
                        <guest-change v-for="(change, key) in change" :key="key" :change="change" :index="key" :deep-index="currentIndex" />
                    </table>
                </td>
            </tr>
        </template>
    </tbody>
</template>

<script>
export default {
    name: 'guest-change',
    props: {
        index: String,
        change: [String, Number, Object],
        deepIndex: {
            type: String,
            default: '',
        },
    },
    computed: {
        comparable() {
            return this.isComparable(this.change);
        },
        currentIndex() {
            return this.deepIndex + (this.deepIndex == '' ? '' : ' / ') + this.index;
        },
    },
    methods: {
        isComparable(change) {
            return ('before' in change) && ('after' in change);
        },
        hasComparable(change) {
            return Object.values(change).some(this.isComparable);
        },
    },
}
</script>
