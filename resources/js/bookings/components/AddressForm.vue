<template>
    <div>
        <div class="field">
            <label class="label">Country</label>
            <div class="select is-fullwidth" :class="{ 'is-danger': ('address.country' in errorBag) }">
                <select v-model="address.country">
                    <option :value="null" disabled></option>
                    <option v-for="country in countries" :key="country.id" :value="country.id">{{ country.name }}</option>
                    <option value="0">Other...</option>
                </select>
            </div>
            <div v-if="address.country == 0" class="control">
                <input type="text" v-model="address.otherCountry" placeholder="Please specify" class="input" :class="{ 'is-danger': ('address.otherCountry' in errorBag) }">
            </div>
            <p v-if="('address.country' in errorBag) || ('address.otherCountry' in errorBag)" class="help is-danger">{{ [...(errorBag['address.country'] || []), ...(errorBag['address.otherCountry'] || [])][0] }}</p>
        </div>
        <div class="field">
            <div class="field-body">
                <div class="field">
                    <label class="label">{{ selectedCountry['division'] ? selectedCountry['division'] : 'State/Province' }}</label>
                    <div v-if="selectedCountry['states']" class="select is-fullwidth" :class="{ 'is-danger': ('address.state' in errorBag) }">
                        <select v-model="address.state">
                            <option :value="null" disabled></option>
                            <option v-for="state in selectedCountry['states']" :key="state.id" :value="state.id">{{ state.name }} ({{ state.abbreviation }})</option>
                        </select>
                    </div>
                    <div v-else class="control">
                        <input type="text" v-model="address.otherState" class="input" :class="{ 'is-danger': ('address.otherState' in errorBag) }">
                    </div>
                    <p v-if="('address.state' in errorBag) || ('address.otherState' in errorBag)" class="help is-danger">{{ [...(errorBag['address.state'] || []), ...(errorBag['address.otherState'] || [])][0] }}</p>
                </div>
                <div class="field">
                    <label class="label">City</label>
                    <div class="control">
                        <input type="text" v-model="address.city" class="input" :class="{ 'is-danger': ('address.city' in errorBag) }">
                    </div>
                    <p v-if="('address.city' in errorBag)" class="help is-danger">{{ errorBag['address.city'][0] }}</p>
                </div>
            </div>
        </div>
        <div class="field">
            <label class="label">Address Line 1</label>
            <div class="control">
                <input type="text" v-model="address.line1" class="input" :class="{ 'is-danger': ('address.line1' in errorBag) }">
            </div>
            <p v-if="('address.line1' in errorBag)" class="help is-danger">{{ errorBag['address.line1'][0] }}</p>
        </div>
        <div class="field">
            <div class="field-body">
                <div class="field">
                    <label class="label">Address Line 2 (Optional)</label>
                    <div class="control">
                        <input type="text" v-model="address.line2" class="input" :class="{ 'is-danger': ('address.line2' in errorBag) }">
                    </div>
                    <p v-if="('address.line2' in errorBag)" class="help is-danger">{{ errorBag['address.line2'][0] }}</p>
                </div>
                <div class="field is-narrow">
                    <label class="label">Zip/Postal Code</label>
                    <div class="control">
                        <input type="text" v-model="address.zipCode" class="input" :class="{ 'is-danger': ('address.zipCode' in errorBag) }">
                    </div>
                    <p v-if="('address.zipCode' in errorBag)" class="help is-danger">{{ errorBag['address.zipCode'][0] }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            value: {
                type: Object,
                required: true
            },
            errorBag: {
                type: Object,
                default: () => ({})
            },
            countries: {
                type: Array,
                required: true
            }
        },
        data() {
            return {}
        },
        computed: {
            address: {
                get() {
                    return this.value;
                },
                set(value) {
                    this.$emit('input', value);
                }
            },
            selectedCountry() {
                return this.countries.find(country => country.id == this.address.country) || {};
            }
        }
    }
</script>
