define([], function () {
    'use strict';

    return function (config) {
        var defaultMessage = 'frontend.event';
        var endpoint = config && config.collectUrl ? config.collectUrl : null;

        if (!endpoint || window.devSeq) {
            return;
        }

        function post(level, message, context) {
            var payload = JSON.stringify({
                level: level,
                message: String(message || defaultMessage),
                contextJson: JSON.stringify(context || {})
            });

            if (!payload) {
                return;
            }

            // `sendBeacon()` keeps logging best-effort during navigations and unloads.
            if (navigator.sendBeacon) {
                try {
                    navigator.sendBeacon(endpoint, new Blob([payload], {
                        type: 'application/json'
                    }));
                    return;
                } catch (error) {
                    // Fall through to `fetch()` when the browser rejects the beacon payload.
                }
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
            } catch (error) {
                // Frontend logging should never break the page.
            }
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

        // Emit a single bootstrap event to confirm the helper was loaded on the page.
        window.devSeq.debug('frontend.bootstrap.loaded', {
            pathname: window.location.pathname,
            search: window.location.search
        });
    };
});
