(function () {
    const GATEWAY_NAME = 'payinstore';
    const settings = window.wc.wcSettings.getSetting(GATEWAY_NAME + '_data', {});
    const label = window.wp.htmlEntities.decodeEntities(settings.title);

    const Content = () => {
        return window.wp.htmlEntities.decodeEntities(settings.description || '');
    };

    const Label = () => {
        const elements = [];

        elements.push(label);

        if (settings.icon) {
            elements.push(
                Object(window.wp.element.createElement)('img', {
                    src: settings.icon,
                    alt: label,
                    style: {
                        marginRight: '8px',
                        height: '24px',
                        verticalAlign: 'middle'
                    }
                })
            );
        }

        return Object(window.wp.element.createElement)('span', null, ...elements);
    };

    const Block_Gateway = {
        name: GATEWAY_NAME,
        // label: label,
        label: Object(window.wp.element.createElement)(Label, null),
        content: Object(window.wp.element.createElement)(Content, null),
        edit: Object(window.wp.element.createElement)(Content, null),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    };

    window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
})();

