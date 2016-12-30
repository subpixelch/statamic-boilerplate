<template>
    <textarea class="form-control" v-model="data" v-el:textarea v-elastic></textarea>
</template>

<script>
module.exports = {
    props: ['name', 'data', 'config']
};
</script>
