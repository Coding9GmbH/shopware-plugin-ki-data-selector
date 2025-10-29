Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: null,
    key: 'kidata_selector',
    roles: {
        viewer: {
            privileges: [
                'kidata_query_log:read',
                'kidata_saved_query:read'
            ],
            dependencies: []
        },
        editor: {
            privileges: [
                'kidata_query_log:create',
                'kidata_saved_query:create',
                'kidata_saved_query:update'
            ],
            dependencies: [
                'kidata_selector.viewer'
            ]
        },
        deleter: {
            privileges: [
                'kidata_saved_query:delete'
            ],
            dependencies: [
                'kidata_selector.editor'
            ]
        }
    }
});
