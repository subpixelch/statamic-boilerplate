<template>
    <div class="form-fieldtype">
        <relate-fieldtype :data.sync="data" :name="name" :config="config"></relate-fieldtype>
    </div>
</template>

<script>
module.exports = {
    props: ['data', 'config', 'name']
};
</script>
