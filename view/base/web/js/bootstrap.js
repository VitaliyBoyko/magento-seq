define([], function () {
    'use strict';

    return function (config) {
        var endpoint = config && config.collectUrl ? config.collectUrl : null;

        if (!endpoint || window.devSeq) {
            return;
        }

        function post(level, message, context) {
            var payload = JSON.stringify({
                level: level,
                message: String(message || 'frontend.event'),
                context: context || {}
            });

            if (!payload) {
                return;
            }

            if (navigator.sendBeacon) {
                try {
                    navigator.sendBeacon(endpoint, new Blob([payload], {
                        type: 'application/json'
                    }));
                    return;
                } catch (error) {}
            }

            if (typeof fetch !== 'function') {
                return;
            }

            try {
                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: payload,
                    keepalive: true,
                    credentials: 'same-origin'
                });
            } catch (error) {}
        }

        window.devSeq = {
            debug: function (message, context) {
                post('Debug', message, context);
            },
            info: function (message, context) {
                post('Info', message, context);
            },
            warn: function (message, context) {
                post('Warning', message, context);
            },
            error: function (message, context) {
                post('Error', message, context);
            }
        };

        window.vitaliiBoikoSeq = window.devSeq;
        window.devSeq.debug('frontend.bootstrap.loaded', {
            pathname: window.location.pathname,
            search: window.location.search
        });
    };
});
