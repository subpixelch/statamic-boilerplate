<template>
    <div class="taxonomy-fieldtype">
        <relate-fieldtype :data.sync="data" :name="name" :config="adjustedConfig"></relate-fieldtype>
    </div>
</template>

<script>
module.exports = {

    props: ['data', 'config', 'name'],


    computed: {

        adjustedConfig() {
            let c = this.config;
            c.create = true;
            return c;
        }

    }
};
</script>
