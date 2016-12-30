module.exports = {

    mixins: [Dossier],

    data: function() {
        return {
            ajax: {
                get: cp_url('configure/addons/get'),
                delete: cp_url('configure/addons/delete')
            },
            tableOptions: {
                checkboxes: false,
                partials: {
                    cell: `
                        <a :href="item.settings_url" v-if="item.settings_url && column.field === 'name'">{{ item[column.label] }}</a>
                        <template v-else>{{ item[column.label] }}</template>`,
                    actions: `
                        <li v-if="item.settings_url"><a :href="item.settings_url">Settings</a></li>`
                }
            }
        }
    },

    methods: {
        refresh: function () {
            window.location = cp_url('configure/addons/refresh');
        }
    }

};
