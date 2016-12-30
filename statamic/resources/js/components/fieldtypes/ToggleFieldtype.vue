<template>
    <div class="toggle-fieldtype-wrapper">
        <div class="toggle-container" :class="{ 'on': isOn }" @click="toggle">
            <div class="toggle-slider">
                <div class="toggle-knob" tabindex="0" @keyup.prevent.space.enter="toggle"></div>
            </div>
        </div>
    </div>
</template>

<script>
module.exports = {
    props: ['name', 'data', 'config'],
    computed: {
        isOn: function () {
            return this.data === true;
        }
    },
    methods: {
        toggle: function () {
            this.data = !this.data;
        }
    }
};
</script>
