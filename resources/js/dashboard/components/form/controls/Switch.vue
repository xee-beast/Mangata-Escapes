<template>
    <div class="field">
        <input type="checkbox" 
               class="switch" 
               :id="id"
               :checked="value"
               :disabled="disabled"
               @change="$emit('input', $event.target.checked)">
        <label :for="id" class="switch-label">
            <slot>{{ value ? 'Active' : 'Inactive' }}</slot>
        </label>
    </div>
</template>

<script>
export default {
    props: {
        value: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            id: 'switch-' + Math.random().toString(36).substr(2, 9)
        }
    }
}
</script>

<style lang="scss" scoped>
.switch {
    position: absolute;
    opacity: 0;
}

.switch-label {
    position: relative;
    display: inline-block;
    cursor: pointer;
    padding-left: 3.5rem;
    padding-top: 0.2rem;
}

.switch-label:before {
    content: '';
    position: absolute;
    left: 0;
    width: 3rem;
    height: 1.5rem;
    background: #e0e0e0;
    border-radius: 1.5rem;
    transition: background 0.3s;
}

.switch-label:after {
    content: '';
    position: absolute;
    left: 0.25rem;
    top: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: white;
    transition: transform 0.3s;
}

.switch:checked + .switch-label:before {
    background: #e5dcdf;
}

.switch:checked + .switch-label:after {
    transform: translateX(1.5rem);
}

.switch:disabled + .switch-label {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
