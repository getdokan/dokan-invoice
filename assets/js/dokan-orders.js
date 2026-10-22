wp.hooks.addFilter(
    'dokan_orders_data_view_dataviews_actions',
    'dokan-invoice/orders-actions',
    (actions) => {
        const getDocument = (items, key) => {
            const order = Array.isArray(items) ? items[0] : items;
            return order && order.actions && order.actions[key];
        };

        const openDocument = (key) => (items) => {
            const doc = getDocument(items, key);
            if (doc && doc.url) {
                window.open(doc.url, '_blank', 'noopener,noreferrer');
            }
        };

        // Mirror the legacy dashboard's created/printed indicator.
        const documentLabel = (label, key) => (items) => {
            const doc = getDocument(items, key);
            if (doc && doc.printed) {
                return label + ' (Printed)';
            }
            if (doc && doc.exists) {
                return label + ' (Created)';
            }
            return label;
        };

        return [
            ...(Array.isArray(actions) ? actions : []),
            {
                id: 'dokan-invoice-download',
                label: documentLabel('View Invoice', 'invoice'),
                callback: openDocument('invoice'),
            },
            {
                id: 'dokan-packing-slip-download',
                label: documentLabel('View Packing Slip', 'packing-slip'),
                callback: openDocument('packing-slip'),
            },
        ];
    }
);
