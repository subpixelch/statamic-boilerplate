<template>
    <input
        type="search"
        :placeholder="translate('cp.search')"
        @keydown.esc="reset"
        v-model="keyword"
        class="filter-control search"
    />
</template>

<script>
module.exports = {
    props: ['keyword'],
    methods: {
        reset: function() {
            this.keyword = '';
        }
    }
};
</script>
