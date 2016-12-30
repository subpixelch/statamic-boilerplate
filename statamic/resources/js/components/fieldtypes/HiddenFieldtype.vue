<template>
    <input type="hidden" :name="name" v-model="data" />
</template>

<script>
module.exports = {
    props: ['data', 'config', 'name'],
};
</script>
