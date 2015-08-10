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

interface Diacritics {
    base:string;
    letters:string;
}

interface Response {
    fields:string[];
    results:any[];
}

var defaultDiacritics:Diacritics[] = [
    {base: 'a', letters: 'äâà'},
    {base: 'e', letters: 'éèëê'},
    {base: 'i', letters: 'ïî'},
    {base: 'o', letters: 'öô'},
    {base: 'u', letters: 'ùüû'},
    {base: 'c', letters: 'ç'},
    {base: 'oe', letters: 'œ'}
];

class glSearch {
    private minQueryLength:number = 2;
    private diacriticsMap:string[] = [];
    private urlServer:string = null;

    constructor(urlServer:string, minQueryLength:number = 2) {
        for (var i = 0; i < defaultDiacritics.length; i++) {
            var letters = defaultDiacritics[i].letters.split("");
            for (var j = 0; j < letters.length; j++) {
                this.diacriticsMap[letters[j]] = defaultDiacritics[i].base;
            }
        }

        this.urlServer = urlServer;
        this.minQueryLength = minQueryLength;
    }

    private removeDiacritics(sentence:string):string {
        if (!sentence)
            return '';
        var map = this.diacriticsMap;
        sentence = sentence.toLowerCase().replace(/[^\u0000-\u007E]/g, function (a) {
            return map[a] || a;
        });
        return sentence;
    }

    public toQuery(words:string[]):string {
        var length:number = words.length;
        var result:string = "";
        var first:boolean = true;
        for (var i = 0; i < length; i++) {
            if (words[i] !== "") {
                if (!first) {
                    result += " ";
                }
                result += words[i];
                if (words[i].indexOf(":") < 0) {
                    result += "*";
                }
                first = false;
            }
        }
        return result;
    }

    public normalize(sentence:string):string[] {
        if (!sentence)
            return [];
        sentence = this.removeDiacritics(sentence);
        var query = sentence.split(/[^a-z0-9:]+/i);

        //sort min length to max length
        query.sort(function (a, b) {
            return a.length - b.length;
        });

        //remove duplicate start with same character
        var result:string[] = [];
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
    }

    public highlights(query:string[], fields:string[], result) {
        var coords = result.highlights.split(/ /);
        var length = coords.length;

        var i:number = 0;

        var colnumInc = Array.apply(null, Array(fields.length)).map(function () {
            return 0
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
    }

    private httpGet(url:string, callback:(response:Response) => void) {
        var anHttpRequest = new XMLHttpRequest();
        anHttpRequest.onreadystatechange = function () {
            if (anHttpRequest.readyState == 4 && anHttpRequest.status == 200) {
                callback(JSON.parse(anHttpRequest.responseText));
            }
        };

        anHttpRequest.open("GET", url, true);
        anHttpRequest.send(null);
    }

    public query(words:string, callbackEach:(value:any) => void, callbackEnd:(values:any[]) => void, filter:string = null) {
        if (words.length < this.minQueryLength) {
            callbackEnd(null);
            return;
        }

        var query:string[] = this.normalize(words);
        var highlights = this.highlights;

        if (!filter) {
            filter = '';
        }

        var url = this.urlServer.replace('{q}', this.toQuery(query));
        url = url.replace('{f}', filter);
        this.httpGet(url, function (data:Response) {
            var fields:string[] = data.fields;
            var results = data.results;
            results.forEach(function (result) {
                highlights(query, fields, result);
                callbackEach(result.value);
            });
            callbackEnd(results);
        });
    }
}
