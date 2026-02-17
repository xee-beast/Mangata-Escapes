<template>
    <div>
        <p class="is-size-7 has-text-weight-normal">
            Travel Insurance is available for purchase and it is highly recommended that you add travel insurance to your package.
        </p>
        <p class="is-size-7 has-text-justified">
            The Travel Insurance that is available may cover you up to the total trip cost for trip cancellation or interruption for a covered reason and up to 75% for any reason 
            (this Cancel For Any Reason Benefit only applies if you add the travel insurance to your package within 14 days of booking). 
            Emergency Evacuation/Repatriation, Accidental Medical Expenses and Sickness Medical Expenses may be covered up to $25,000. 
            Also included is baggage protection and worldwide emergency assistance. It is a cancellation/interruption insurance with medical benefits. 
            Your health insurance may not cover you outside of the US. Accidents can happen and travel insurance can help protect you in the event you need medical attention. 
            For a brochure detailing what is included, please email us at {{ groupsEmail }} and I will send it to you via email. 
            The cost for travel insurance is dependant on the cost of your trip, the number of nights, and whether or not a flight is included. 
            To get an exact price of travel insurance, please email us for a quote. 
            The cost of travel insurance for each guest is due at time of booking. Please note that once purchased, travel insurance is nonrefundable.
        </p>
        <br>
        <div class="field">
            <div class="label">Do you wish to purchase travel insurance?</div>
            <div class="control">
                <div>
                    <label class="radio">
                        <input type="radio" :value="true" v-model="insurance.accept">
                        Yes, I would like to purchase travel insurance and understand that once purchased <b>the cost of travel insurance is non-refundable</b>.
                    </label>
                </div>
                <div>
                    <label class="radio">
                        <input type="radio" :value="false" v-model="insurance.accept">
                        No, I am <b>not interested in purchasing travel insurance</b> and acknowledge that I have been offered but choose to decline this coverage. I understand the risks in not purchasing travel protection.
                    </label>
                </div>
            </div>
            <p v-if="('insurance.accept' in errors)" class="help is-danger">{{ errors['insurance.accept'][0] }}</p>
            <div v-if="false === insurance.accept">
                <div class="control mt-10"> 
                    <label class="checkbox">
                        <input type="checkbox" v-model="insurance.declinedInsuranceAgreements.first" :class="{ 'is-danger': ('insurance.declinedInsuranceAgreements.first' in errors) }">
                        I understand that by declining travel insurance, that I will not be reimbursed for cancelling my reservation after {{ cancellationDate }}. 
                    </label>
                </div>
                <p v-if="('insurance.declinedInsuranceAgreements.first' in errors)" class="help is-danger">You must agree with the conditions.</p>
                <div class="control mt-10">
                    <label class="checkbox">
                        <input type="checkbox" v-model="insurance.declinedInsuranceAgreements.second" :class="{ 'is-danger': ('insurance.declinedInsuranceAgreements.second' in errors) }">
                        I understand that after {{ cancellationDate }}, I will not be able to cancel for a refund even if one or if one or more of the guests on this reservation is/are unable to attend for any reason, including but not limited to:
                        <ul style="list-style: inherit; padding: 0px 10px 0px 20px;">
                            <li>Illness</li>
                            <li>Testing positive for COVID</li>
                            <li>Pregnancy</li>
                            <li>Inability to have time off work approved</li>
                            <li>Military service</li>
                            <li>Weather-related issues including hurricanes, snow storms, or natural disasters that prevent you from being able to travel, or any other reason. </li>
                            <li>Or any other reason. </li>
                        </ul>
                    </label>
                </div>
                <p v-if="('insurance.declinedInsuranceAgreements.second' in errors)" class="help is-danger">You must agree with the conditions.</p>
                <div class="control mt-10">
                    <label class="checkbox">
                        <input type="checkbox" v-model="insurance.declinedInsuranceAgreements.third" :class="{ 'is-danger': ('insurance.declinedInsuranceAgreements.third' in errors) }">
                        I understand that the only way to protect my reservation is by purchasing travel insurance.
                    </label>
                </div>
                <p v-if="('insurance.declinedInsuranceAgreements.third' in errors)" class="help is-danger">You must agree with the conditions.</p>
                <div class="control mt-10">
                    <label class="checkbox">
                        <input type="checkbox" v-model="insurance.declinedInsuranceAgreements.fourth" :class="{ 'is-danger': ('insurance.declinedInsuranceAgreements.fourth' in errors) }">
                        I understand that if I did not purchase travel insurance and wish to cancel, reduce the guest count, downgrade my room category, or make any other changes to the reservation that would have resulted in a refund prior to {{ cancellationDate }}, that I will not receive a refund and I agree not to dispute my charges in this event.
                    </label>
                </div>
                <p v-if="('insurance.declinedInsuranceAgreements.fourth' in errors)" class="help is-danger">You must agree with the conditions.</p>
            </div>
        </div>
        <div class="field">
            <label class="label">Travel Insurance Signature</label>
            <p class="help for-label">
                Type your name here to confirm your decision above. You must sign this, whether you are purchasing or declining travel insurance!
            </p>
            <div class="control">
                <input type="text" v-model="insurance.signature" :placeholder="client" class="input is-capitalized" :class="{ 'is-danger': ('insurance.signature' in errors) }">
            </div>
            <p v-if="('insurance.signature' in errors)" class="help is-danger">{{ errors['insurance.signature'][0] }}</p>
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
            errors: {
                type: Object,
                default: () => ({})
            },
            client: {
                type: String,
                default: ''
            },
            cancellationDate: {
                type: String,
                default: ''
            },
            groupsEmail: {
              type: String,
              default: '',
            },
        },
        computed: {
            insurance: {
                get() {
                    return this.value;
                },
                set(newInsurance) {
                    this.$emit('input', newInsurance);
                }
            }
        }
    }
</script>
