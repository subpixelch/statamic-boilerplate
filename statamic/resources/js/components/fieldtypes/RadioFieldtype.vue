<template>
    <div class="radio-fieldtype-wrapper">
        <ul class="list-unstyled">
            <li v-for="option in config.options">
                <input type="radio" v-model="data" :value="option.value" :id="name + '-' + $index" />
                <label :for="name + '-' + $index">{{ option.text }}</label>
            </li>
        </ul>
    </div>
</template>

<script>
module.exports = {
    props: ['name', 'data', 'config']
};
</script>
