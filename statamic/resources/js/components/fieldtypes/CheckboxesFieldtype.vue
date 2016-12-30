<template>
    <ul class="list-unstyled">
        <li v-for="option in config.options">
            <input type="checkbox"
                   name="{{ name }}[]"
                   :id="name + '-' + $index"
                   :value="option.value"
                   v-model="data"
    		/>
            <label :for="name + '-' + $index">{{ option.text }}</label>
        </li>
    </ul>
</template>

<script>
module.exports = {

    props: ['name', 'data', 'config'],

    ready: function() {
        if (typeof this.config === 'string') {
            this.config = JSON.parse(this.config);
        }

        if ( ! this.data) {
            this.data = [];
        }
    }
};
</script>
