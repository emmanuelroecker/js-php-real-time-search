/**
 * Glicer Search Client
 *
 * Typescript
 *
 * @category  GLICER
 * @author    Emmanuel ROECKER
 * @author    Rym BOUCHAGOUR
 * @copyright 2015 GLICER
 * @license   GNU 2
 * @link      http://dev.glicer.com/
 *
 * Created : 30/07/15
 * File : glsearch.ts
 *
 */
var defaultDiacritics = [
    { base: 'a', letters: 'äâà' },
    { base: 'e', letters: 'éèëê' },
    { base: 'i', letters: 'ïî' },
    { base: 'o', letters: 'öô' },
    { base: 'u', letters: 'ùüû' },
    { base: 'c', letters: 'ç' },
    { base: 'oe', letters: 'œ' }
];
var glSearch = (function () {
    function glSearch(urlServer, minQueryLength) {
        if (minQueryLength === void 0) { minQueryLength = 2; }
        this.minQueryLength = 2;
        this.diacriticsMap = [];
        this.urlServer = null;
        for (var i = 0; i < defaultDiacritics.length; i++) {
            var letters = defaultDiacritics[i].letters.split("");
            for (var j = 0; j < letters.length; j++) {
                this.diacriticsMap[letters[j]] = defaultDiacritics[i].base;
            }
        }
        this.urlServer = urlServer;
        this.minQueryLength = minQueryLength;
    }
    glSearch.prototype.removeDiacritics = function (sentence) {
        if (!sentence)
            return '';
        var map = this.diacriticsMap;
        sentence = sentence.toLowerCase().replace(/[^\u0000-\u007E]/g, function (a) {
            return map[a] || a;
        });
        return sentence;
    };
    glSearch.prototype.toQuery = function (words) {
        var length = words.length;
        var result = "";
        var first = true;
        for (var i = 0; i < length; i++) {
            if (words[i] !== "") {
                if (!first) {
                    result += " ";
                }
                result += words[i] + "*";
                first = false;
            }
        }
        return result;
    };
    glSearch.prototype.normalize = function (sentence) {
        if (!sentence)
            return [];
        sentence = this.removeDiacritics(sentence);
        var query = sentence.split(/[^a-z0-9]+/i);
        //sort min length to max length
        query.sort(function (a, b) {
            return a.length - b.length;
        });
        //remove duplicate start with same character
        var result = [];
        var length = query.length;
        for (var i = 0; i < length; i++) {
            var word = query[i];
            var ok = true;
            for (var j = i + 1; j < length; j++) {
                if (query[j].indexOf(word) === 0) {
                    ok = false;
                    break;
                }
            }
            if (ok) {
                result.push(word);
            }
        }
        return result;
    };
    glSearch.prototype.highlights = function (query, fields, result) {
        var coords = result.highlights.split(/ /);
        var length = coords.length;
        var i = 0;
        var colnumInc = Array.apply(null, Array(fields.length)).map(function () {
            return 0;
        });
        while (i < length) {
            var colnum = coords[i++];
            var itemnum = coords[i++];
            var offset = parseInt(coords[i++]) + colnumInc[colnum];
            var size = coords[i++];
            var value = result.value[fields[colnum]];
            var queryLength = query[itemnum].length;
            result.value[fields[colnum]] = value.substr(0, offset) + "<b>" + value.substr(offset, queryLength) + "</b>" + value.substr(offset + queryLength);
            colnumInc[colnum] += 7;
        }
    };
    glSearch.prototype.httpGet = function (url, callback) {
        var anHttpRequest = new XMLHttpRequest();
        anHttpRequest.onreadystatechange = function () {
            if (anHttpRequest.readyState == 4 && anHttpRequest.status == 200) {
                callback(JSON.parse(anHttpRequest.responseText));
            }
        };
        anHttpRequest.open("GET", url, true);
        anHttpRequest.send(null);
    };
    glSearch.prototype.query = function (words, callbackEach, callbackEnd, filter) {
        if (filter === void 0) { filter = null; }
        if (words.length < this.minQueryLength) {
            callbackEnd(null);
            return;
        }
        var query = this.normalize(words);
        var highlights = this.highlights;
        if (!filter) {
            filter = '';
        }
        var url = this.urlServer.replace('{q}', this.toQuery(query));
        url = url.replace('{f}', filter);
        this.httpGet(url, function (data) {
            var fields = data.fields;
            var results = data.results;
            results.forEach(function (result) {
                highlights(query, fields, result);
                callbackEach(result.value);
            });
            callbackEnd(results);
        });
    };
    return glSearch;
})();
//# sourceMappingURL=glsearch.js.map