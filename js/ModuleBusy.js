'use strict';
var moduleBusy = angular.module('ModuleBusy', []);

moduleBusy.service('ServiceBusy', [function() {

    var busyArray = [];

    this.addBusyItem = function(moduleIdentifier, sequenceNumber) {
        var currentModuleBusyStatus = _findElementsInList('id', moduleIdentifier, busyArray);
        // entry already exists, try to add to internal array
        if (_isSetAndNonEmptyArray(currentModuleBusyStatus)) {
            // only add item if it does NOT exist already && the new one is positive
            if (currentModuleBusyStatus[0].items.indexOf(sequenceNumber) === -1) {
                currentModuleBusyStatus[0].items.push(sequenceNumber);
            }
        }
        // entry does not exist; create
        else {
            busyArray.push({
                id: moduleIdentifier,
                items: [sequenceNumber]
            });
        }
        return _stillBusy(moduleIdentifier);
    };

    function _stillBusy(moduleIdentifier, sequenceNumber) {
        var currentModuleBusyStatus = _findElementsInList('id', moduleIdentifier, busyArray);
        if (_isSetAndNonEmptyArray(currentModuleBusyStatus)) {
            if (_isSet(sequenceNumber)) {
                return currentModuleBusyStatus[0].items.indexOf(sequenceNumber) !== -1;
            } else {
                return currentModuleBusyStatus[0].items.length !== 0;
            }
        }
        return false;
    }

    this.stillBusy = function(moduleIdentifier, sequenceNumber) {
        // no identifier given
        if (!_isSet(moduleIdentifier)) {
            var retval = false;
            // look in all modules for the sequence number
            for (var busyIndex = 0; busyIndex < busyArray.length; busyIndex++) {
                retval |= (busyArray[busyIndex].items.length > 0);
            }
            return retval;
        }
        return _stillBusy(moduleIdentifier, sequenceNumber);
    };

    this.removeBusyItem = function(moduleIdentifier, sequenceNumber) {
        // if module identifier is not set, call clearBusyItems()
        if (!_isSet(moduleIdentifier)) {
            this.clearBusyItems();
        }
        // if sequence number is not set, call clearBusyItems()
        if (!_isSet(sequenceNumber)) {
            this.clearBusyItems(moduleIdentifier);
        }

        // if the sequence number is set, do stuff
        var currentModuleBusyStatus = _findElementsInList('id', moduleIdentifier, busyArray);
        if (_isSetAndNonEmptyArray(currentModuleBusyStatus)) {
            var itemIndex = currentModuleBusyStatus[0].items.indexOf(sequenceNumber);
            if (itemIndex !== -1) {
                currentModuleBusyStatus[0].items.splice(itemIndex, 1);
            }
            return _stillBusy(moduleIdentifier);
        }
        return false;
    };

    this.clearBusyItems = function(moduleIdentifier) {
        if (!_isSet(moduleIdentifier)) {
            // remove everything if not specified - empty array, don't replace them
            for (var i = 0; i < busyArray.length; i++) {
                busyArray[i].items.splice(0, busyArray[i].items.length);
            }
            return;
        }

        var currentModuleBusyStatus = _findElementsInList('id', moduleIdentifier, busyArray);
        if (_isSetAndNonEmptyArray(currentModuleBusyStatus)) {
            currentModuleBusyStatus[0].items.splice(0, currentModuleBusyStatus[0].items.length);
        } else {
            busyArray.push({
                id: moduleIdentifier,
                items: []
            });
        }
    };

}]);