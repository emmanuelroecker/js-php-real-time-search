/**
 * Glicer Search Client Tests
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
 * File : glsearch-test.ts
 *
 */

/// <reference path="qunit.d.ts" />
/// <reference path="../src/glsearch.ts" />

test("remove diacritics characters", function () {
    var search = new glSearch("");
    var result = search.normalize("école");
    equal(result, "ecole");
    result = search.normalize("ça été un être chère cœur chez les zoulous");
    deepEqual(result, ["ca",
        "un",
        "ete",
        "les",
        "etre",
        "chez",
        "chere",
        "coeur",
        "zoulous"]);
    result = search.normalize('äâàéèëêïîöôùüûœç');
    deepEqual(result, ["aaaeeeeiioouuuoec"]);
});
