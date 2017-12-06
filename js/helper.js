'use strict';

/**
 * Extension to Object class to get the number of properties from an object.
 *
 * @param obj
 * @returns {Number}
 */
Object.size = function(obj) {
    var size = 0;
    for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
            size++;
        }
    }
    return size;
};

/**
 * Extension to String in order to get half-decent hash values (for example to create database IDs).
 */
String.prototype.hashCode = function() {
    var hash = 0;
    if (this.length === 0) {
        return hash;
    }
    for (var i = 0; i < this.length; i++) {
        hash = ((hash << 5) - hash) + this.charCodeAt(i);
        // Convert to 32bit integer
        hash = hash | 0;
    }
    return hash;
};

/**
 * Try to convert an object into a number (while accepting dots AND commas).
 *
 * @param obj
 */
function numberPlus(obj) {
    if ((typeof obj === 'number') || (typeof obj === 'string')) {
        return Number(String(obj).replace(/,/, '.'));
    }
    return NaN;
}

/**
 * Returns 'true' if the object is defined and not 'null'.
 *
 * @param obj
 * @returns {Boolean}
 */
function _isSet(obj) {
    return (typeof obj !== 'undefined') && (obj !== null);
}

/**
 * Returns 'true' if the object is defined and a number (strings will be converted).
 *
 * @param obj
 * @returns {Boolean}
 */
function _isSetAndNumber(obj) {
    return _isSet(obj) && (typeof obj === 'number') && (!isNaN(numberPlus(obj)));
}

/**
 * Returns 'true' if the object is defined, not 'null' and a number at least zero.
 *
 * @param obj
 * @returns {Boolean}
 */
function _isSetAndAtLeastZero(obj) {
    return _isSetAndNumber(obj) && obj >= 0;
}

/**
 * Returns 'true' if the object is defined, not 'null' and a number bigger than zero.
 *
 * @param obj
 * @returns {Boolean}
 */
function _isSetAndBiggerThanZero(obj) {
    return _isSetAndNumber(obj) && obj > 0;
}

/**
 * Returns 'true' if the object is defined, not 'null' and a string.
 */
function _isSetAndString(obj) {
    return _isSet(obj) && (typeof obj === 'string');
}
/**
 * Returns 'true' if the object is defined, not 'null' and a string that is not empty.
 *
 * @param obj
 * @returns {Boolean}
 */
function _isSetAndNoEmptyString(obj) {
    return _isSet(obj) && (typeof obj === 'string') && (String(obj) !== '');
}

/**
 * Returns 'true' if the object is defined, not 'null' and of type 'array'.
 *
 * @param obj
 * @returns {Boolean}
 */
function _isSetAndArray(obj) {
    return _isSet(obj) && Array.isArray(obj);
}

/**
 * Returns 'true' if the object is defined, not 'null', of type 'array' and contains at least one element.
 *
 * @param obj
 * @returns {Boolean}
 */
function _isSetAndNonEmptyArray(obj) {
    return _isSetAndArray(obj) && (obj.length > 0);
}

/**
 * Does precise rounding/trimming of numbers. https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/round
 *
 * @param type
 * @param value
 * @param exp
 * @returns {Number}
 */
function decimalAdjust(type, value, exp) {
    // If the exp is undefined or zero...
    if ((typeof exp === 'undefined') || (+exp === 0)) {
        return Math[type](value);
    }

    value = +value;
    exp = +exp;

    // If the value is not a number or the exp is not an integer...
    if (isNaN(value) || !((typeof exp === 'number') && ((exp % 1) === 0))) {
        return NaN;
    }
    // Shift
    value = value.toString().split('e');
    value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
    // Shift back
    value = value.toString().split('e');
    return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
}

// Decimal round
if (!Math.round10) {
    Math.round10 = function(value, exp) {
        return decimalAdjust('round', numberPlus(value), exp);
    };
}
// Decimal floor
if (!Math.floor10) {
    Math.floor10 = function(value, exp) {
        return decimalAdjust('floor', numberPlus(value), exp);
    };
}
// Decimal ceil
if (!Math.ceil10) {
    Math.ceil10 = function(value, exp) {
        return decimalAdjust('ceil', numberPlus(value), exp);
    };
}

/**
 * Puts leading characters in front of a string to fill it to a certain length.
 *
 * @param input
 * @param padChar
 * @param numberOfChars
 * @returns {String}
 */
function _leadingChar(input, padChar, numberOfChars) {
    var inputString = '' + input;
    if (inputString.length >= numberOfChars) {
        return inputString;
    }
    var retString = '';
    for (var count = 0; count < numberOfChars - inputString.length; count++) {
        retString += padChar;
    }
    return retString + inputString;
}

/**
 * Makes sure the first letter of a string is in upper case.
 *
 * @param str
 * @returns
 */
function _ucfirst(str) {
    if (!_isSetAndNoEmptyString(str)) {
        return str;
    }

    return str.charAt(0).toUpperCase() + str.substr(1);
}

/**
 * New try on a cleaner _getProperty() function.
 */
function _getProperty(obj, path) {
    if (!_isSet(obj)) {
        return undefined;
    }
    if (!_isSet(path) || path === '') {
        return obj;
    }
    // determine the number of parts
    var pathParts = (path.indexOf('.') !== -1) ? path.split('.') : [path];
    for (var partIndex = 0; partIndex < pathParts.length; partIndex++) {
        var currentPart = pathParts[partIndex];
        // check whether we're in unknown territory
        if ((currentPart.indexOf('[') === -1) && (typeof obj[currentPart] === 'undefined')) {
            return undefined;
        }
        // encountered an array field
        if (currentPart.indexOf('[') !== -1) {
            // try to read the index
            var indexMatch = /(.*)\[(\d+)\].*/g.exec(currentPart);
            // check whether the next object is an array and worth searching through
            if (!_isSetAndNonEmptyArray(obj[indexMatch[1]])) {
                return undefined;
            }
            // see whether the index can be found in the object
            if (Number(indexMatch[2]) in obj[indexMatch[1]]) {
                obj = obj[indexMatch[1]][Number(indexMatch[2])];
            } else {
                return undefined;
            }
        } else {
            // normal path
            obj = obj[currentPart];
        }
    }
    // if we get here, return 'obj'
    return obj;
}

/**
 * Find and return the first/all element(s) from an array, which has/have a given value in a given path. The path can also contain further arrays which must be
 * marked by '[]'.
 *
 * @param path
 *          path to value in form of a string like "header", "bond.header" or "bonds[].header"
 * @param value
 *          value to compare with
 * @param list
 *          the list to look in
 * @param wrapped
 *          give back the answer in form of .element (containing the found element in question) and .index (the position of this element in the given list)
 * @param all
 *          boolean value if you're looking for all occurences (or just the first)
 * @param comparison
 *          either a boolean saying that you're testing for equality or a function that does the comparison itself and return true if the value is to be
 *          included
 * @returns an array which is either empty or contains the found element(s) if wrapping was enabled every result has .element referencing the found element and
 *          .index
 */
function _findElementsInList(path, value, list, wrapped, limit, comparison) {
    var foundElements = [];

    // all params must be set
    if (!_isSet(path)) {
        logError('find: path invalid: ' + path);
        return foundElements;
    }
    if ((typeof value === 'undefined')) {
        logError('find: value invalid: ' + value);
        return foundElements;
    }
    if (!_isSetAndArray(list)) {
        logError('find: list invalid');
        return foundElements;
    }
    // prepare optional parameters
    if (!_isSet(wrapped) || (typeof wrapped !== 'boolean')) {
        wrapped = false;
    }
    if (!_isSet(limit) || ((typeof limit !== 'boolean') && (typeof limit !== 'number'))) {
        limit = false;
    }
    if ((typeof limit === 'number') && (limit === 0)) {
        limit = -1;
    }
    if (typeof limit === 'boolean') {
        limit = limit ? -1 : 1;
    }
    if (!_isSet(comparison) || ((typeof comparison !== 'boolean') && (typeof comparison !== 'function'))) {
        comparison = true;
    }

    // if path is empty, compare object directly
    if (path === '') {
        for (var listIndexC = 0; listIndexC < list.length; listIndexC++) {
            if (
                ((typeof comparison === 'boolean') && comparison && (list[listIndexC] === value)) ||
                ((typeof comparison === 'boolean') && !comparison && (list[listIndexC] !== value)) ||
                ((typeof comparison === 'function') && comparison(list[listIndexC], value))
            ) {
                if (wrapped) {
                    foundElements.push({
                        index: listIndexC,
                        element: list[listIndexC]
                    });
                } else {
                    foundElements.push(list[listIndexC]);
                }
                if (foundElements.length === limit) {
                    break;
                }
            }
        }
        return foundElements;
    }

    var parts = (path.indexOf('.') !== -1) ? path.split('.') : [path];

    // search for first array part, if any
    var firstArrayPart = -1;
    for (var partIndex = 0; partIndex < parts.length; partIndex++) {
        if (parts[partIndex].indexOf('[') !== -1) {
            firstArrayPart = partIndex;
            break;
        }
    }
    // last part is an array
    if (firstArrayPart === (parts.length - 1)) {
        for (var listIndexD = 0; listIndexD < list.length; listIndexD++) {
            var property = _getProperty(list[listIndexD], path.substring(0, path.length - 2));
            if (_isSetAndArray(property) && _isSetAndNonEmptyArray(_findElementsInList('', value, property, false, false, comparison))) {
                if (wrapped) {
                    foundElements.push({
                        index: listIndexD,
                        element: list[listIndexD]
                    });
                } else {
                    foundElements.push(list[listIndexD]);
                }
                if (foundElements.length === limit) {
                    break;
                }
            }
        }
    } else {
        // at least one array part left
        if (firstArrayPart !== -1) {
            for (var listIndexA = 0; listIndexA < list.length; listIndexA++) {
                var subPath = parts.slice(firstArrayPart + 1).join('.');
                var subListName = parts.slice(0, firstArrayPart + 1).join('.');
                var subList = _getProperty(list[listIndexA], subListName.substring(0, subListName.indexOf('[')));
                if (!_isSet(subList) || !_isSetAndArray(subList)) {
                    continue;
                }
                // TODO: maybe recursively validate 'all' parameter
                if (_isSetAndNonEmptyArray(_findElementsInList(subPath, value, subList, false, false, comparison))) {
                    if (wrapped) {
                        foundElements.push({
                            index: listIndexA,
                            element: list[listIndexA]
                        });
                    } else {
                        foundElements.push(list[listIndexA]);
                    }
                    if (foundElements.length === limit) {
                        break;
                    }
                }
            }
        } else {
            for (var listIndexB = 0; listIndexB < list.length; listIndexB++) {
                if (
                    ((typeof comparison === 'boolean') && comparison && (_getProperty(list[listIndexB], path) === value)) ||
                    ((typeof comparison === 'boolean') && !comparison && (_getProperty(list[listIndexB], path) !== value)) ||
                    ((typeof comparison === 'function') && comparison(_getProperty(list[listIndexB], path), value))
                ) {
                    if (wrapped) {
                        foundElements.push({
                            index: listIndexB,
                            element: list[listIndexB]
                        });
                    } else {
                        foundElements.push(list[listIndexB]);
                    }
                    if (foundElements.length === limit) {
                        break;
                    }
                }
            }
        }
    }
    return foundElements;
}

/**
 * Removes elements from list, if selectFunction returns true.
 *
 * @param list           array which will be filtered
 * @param selectFunction selection function which returning true if element has to be removed
 *
 * @returns filtered list
 */
function _removeElementsFromArray(list, selectFunction) {
    // get all indexes to remove
    var removeList = [];
    for (var indexA = 0; indexA < list.length; indexA++) {
        var element = list[indexA];
        if (selectFunction(element)) {
            removeList.push(indexA);
        }
    }
    // remove them (reversed order!)
    for (var indexB = removeList.length - 1; indexB >= 0; indexB--) {
        list.splice(removeList[indexB], 1);
    }
}

/**
 * Compare two arrays and get all the elements that were added, removed or are part of both. Additionally a path can be specified, to point the function to a
 * distinctive property of the objects inside the array. ATTENTION! 'added' elements are from 'after', 'removed' & 'unchanged' elements are from 'before'.
 *
 * @param before
 * @param after
 * @param path
 * @returns an object { added : [], removed : [], unchanged : [] }
 */
function _compareArrays(before, after, path) {
    if (!_isSetAndArray(before) || !_isSetAndArray(after)) {
        logError('compare: params invalid');
        return null;
    }
    if (!_isSet(path)) {
        path = '';
    }
    // find all removed elements
    var removedElements = [];
    var unchangedElements = [];
    for (var beforeIndex = 0; beforeIndex < before.length; beforeIndex++) {
        if (_isSetAndNonEmptyArray(_findElementsInList(path, _getProperty(before[beforeIndex], path), after))) {
            unchangedElements.push(before[beforeIndex]);
        } else {
            removedElements.push(before[beforeIndex]);
        }
    }
    var addedElements = [];
    for (var afterIndex = 0; afterIndex < after.length; afterIndex++) {
        if (!_isSetAndNonEmptyArray(_findElementsInList(path, _getProperty(after[afterIndex], path), unchangedElements))) {
            addedElements.push(after[afterIndex]);
        }
    }
    return {
        added: addedElements,
        removed: removedElements,
        unchanged: unchangedElements,
        hasChanged: (addedElements.length > 0) || (removedElements.length > 0)
    };
}

function _convertObjectToArray(obj, valueOnly) {
    if (!_isSet(obj) || (typeof obj !== 'object')) {
        return [];
    }

    if (!_isSet(valueOnly)) {
        valueOnly = false;
    }

    if (!valueOnly) {
        return Object.keys(obj).map(function(key) {
            return {
                'key': key,
                'value': obj[key]
            };
        });
    } else {
        return Object.keys(obj).map(function(key) {
            return obj[key];
        });
    }
}

function _clone(obj) {
    return JSON.parse(JSON.stringify(obj));
}

function _substr(str, start, length) {
    if (!_isSet(str)) {
        return '';
    }
    if (!_isSetAndNumber(start)) {
        return str;
    }
    if (!_isSetAndNumber(length)) {
        return str.substr(start);
    }
    return str.substr(start, length);
}

function _flatten(obj, prefix) {
    var flatten = {};
    if (!_isSet(prefix)) {
        prefix = '';
    }

    for (var index in obj) {
        if (obj.hasOwnProperty(index)) {
            if (typeof obj[index] === 'object') {
                var flatsub = _flatten(obj[index]);
                for (var subindex in flatsub) {
                    if (flatsub.hasOwnProperty(subindex)) {
                        flatten[prefix + index + '.' + subindex] = flatsub[subindex];
                    }
                }
            } else {
                flatten[prefix + index] = obj[index];
            }
        }
    }
    return flatten;
}

function _createDummyArray(size) {
    var returnArray = [];
    if (size > 0) {
        for (var count = 0; count < size; count++) {
            returnArray.push(count);
        }
    }
    return returnArray;
}

// cycle through filters based on path and values
function _toggleFilter(filterList, path, values, singlePathOnly) {
    var pathInFilter = _findElementsInList('path', path, filterList, true);
    // list is currently filtered
    if (_isSetAndNonEmptyArray(pathInFilter)) {
        var valueIndex = values.indexOf(pathInFilter[0].element.value) + 1;
        // currently on last value; remove filter
        if (valueIndex === values.length) {
            filterList.splice(pathInFilter[0].index, 1);
        }
        // further values to cycle through
        else {
            pathInFilter[0].element.value = values[valueIndex];
        }
    }
    // filter by first value
    else {
        if (_isSet(singlePathOnly) && singlePathOnly) {
            // YES! it's basically a .clear() ... DO NOT REPLACE THE LIST (by '= []')
            filterList.splice(0, filterList.length);
        }
        filterList.push({
            'path': path,
            'value': values[0]
        });
    }
};