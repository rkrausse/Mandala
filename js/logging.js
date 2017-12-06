'use strict';

var LOG_ENUM = {
    // log every single step
    TRACE: 1,
    // log debug informations
    DEBUG: 2,
    // log info
    INFO: 3,
    // log warnings
    WARN: 4,
    // log errors
    ERROR: 5,
    // do not log
    QUIET: 99

};

// Full version of `log` that:
// * Prevents errors on console methods when no console present.
// * Exposes a global 'log' function that preserves line numbering and formatting.
(function() {
    var noop = function() {
        // This is intentional
    };
    var methods = ['assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error', 'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log', 'markTimeline', 'profile',
        'profileEnd', 'table', 'time', 'timeEnd', 'timeStamp', 'trace', 'warn'
    ];
    var length = methods.length;
    window.console = window.console || {};
    var console = window.console;

    while (length--) {
        var method = methods[length];

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop;
        }
    }

    if (Function.prototype.bind) {
        if (settings.debug.loglevel <= LOG_ENUM.TRACE) {
            window.logTrace = Function.prototype.bind.call(console.log, console);
        } else {
            window.logTrace = noop;
        }
        if (settings.debug.loglevel <= LOG_ENUM.DEBUG) {
            window.logDebug = Function.prototype.bind.call(console.log, console);
        } else {
            window.logDebug = noop;
        }
        if (settings.debug.loglevel <= LOG_ENUM.INFO) {
            window.logInfo = Function.prototype.bind.call(console.info, console);
        } else {
            window.logInfo = noop;
        }
        if (settings.debug.loglevel <= LOG_ENUM.WARN) {
            window.logWarn = Function.prototype.bind.call(console.warn, console);
        } else {
            window.logWarn = noop;
        }
        if (settings.debug.loglevel <= LOG_ENUM.ERROR) {
            window.logError = Function.prototype.bind.call(console.error, console);
        } else {
            window.logError = noop;
        }
    } else {
        if (settings.debug.loglevel <= LOG_ENUM.TRACE) {
            window.logTrace = function() {
                Function.prototype.apply.call(console.log, console, arguments);
            };
        } else {
            window.logTrace = noop;
        }
        if (settings.debug.loglevel <= LOG_ENUM.DEBUG) {
            window.logDebug = function() {
                Function.prototype.apply.call(console.log, console, arguments);
            };
        } else {
            window.logDebug = noop;
        }
        if (settings.debug.loglevel <= LOG_ENUM.INFO) {
            window.logInfo = function() {
                Function.prototype.apply.call(console.info, console, arguments);
            };
        } else {
            window.logInfo = noop;
        }
        if (settings.debug.loglevel <= LOG_ENUM.WARN) {
            window.logWarn = function() {
                Function.prototype.apply.call(console.warn, console, arguments);
            };
        } else {
            window.logWarn = noop;
        }
        if (settings.debug.loglevel <= LOG_ENUM.ERROR) {
            window.logError = function() {
                Function.prototype.apply.call(console.error, console, arguments);
            };
        } else {
            window.logError = noop;
        }
    }
})();