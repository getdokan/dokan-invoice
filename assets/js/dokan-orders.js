wp.hooks.addFilter(
    'dokan_orders_data_view_dataviews_actions',
    'dokan-invoice/orders-actions',
    (actions) => {
        const openDocument = (key) => (items) => {
            const order = Array.isArray(items) ? items[0] : items;
            const doc = order && order.actions && order.actions[key];
            if (doc && doc.url) {
                window.open(doc.url, '_blank', 'noopener,noreferrer');
            }
        };

        return [
            ...(Array.isArray(actions) ? actions : []),
            {
                id: 'dokan-invoice-download',
                label: 'View Invoice',
                callback: openDocument('invoice'),
            },
            {
                id: 'dokan-packing-slip-download',
                label: 'View Packing Slip',
                callback: openDocument('packing-slip'),
            },
        ];
    }
);
