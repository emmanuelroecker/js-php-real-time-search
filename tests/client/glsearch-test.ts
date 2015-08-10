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
/// <reference path="../../src/glsearch.ts" />

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

    result = search.normalize("economi econo uni universel");
    deepEqual(result, ["economi", "universel"]);
});


test("to query", function () {
    var search = new glSearch("");
    var query = search.toQuery(["maison", "voiture"]);

    equal(query, "maison* voiture*");
});

test("highlights", function () {
    var search = new glSearch("");

    var data = {value: {field1: "j'aime le word1", field2: "je préfère le word25 qui est meilleur"}, highlights: "0 0 10 5 1 1 14 6"};

    search.highlights(["word1", "word2"], ["field1", "field2"], data);

    equal(data.value.field1, "j'aime le <b>word1</b>");
    equal(data.value.field2, "je préfère le <b>word2</b>5 qui est meilleur");
});

test("query on server", function (assert) {
    var done = assert.async();

    var search = new glSearch("http://localhost:1349/search.php?q={q}&f={f}");

    search.query("rest chaponnay", function (value) {
        setTimeout(function () {
            assert.equal(value.title, "Aklé - Le Comptoir à Mezzés");
            assert.equal(value.tags, "<b>rest</b>aurant libanais monde");
            assert.equal(value.address, "108 rue <b>Chaponnay</b>");
            done();
        },500);
    }, function (values) {
    });
});

test("query on server 2", function (assert) {
    var done = assert.async();

    var search = new glSearch("http://localhost:1349/search.php?q={q}&f={f}");

    search.query("tags:cinema", function (value) {
    }, function (values) {
        setTimeout(function () {
            console.log(values);
            assert.equal(values.length, 2);
            assert.equal(values[0].value.title,"Cinéma Comoedia");
            assert.equal(values[1].value.title,"Le Zola");
            done();
        },500);
    });
});


test("query on server with filter 1", function (assert) {
    var done = assert.async();

    var search = new glSearch("http://localhost:1349/search.php?q={q}&f={f}");

    search.query("lyon", function (value) {
        setTimeout(function () {
            assert.equal(value.title, "Gym Suédoise <b>Lyon</b>");
            done();
        },500);
    }, function (values) {
    }, 'gps IS NULL');
});

test("query on server with filter 2", function (assert) {
    var done = assert.async();

    var search = new glSearch("http://localhost:1349/search.php?q={q}&f={f}");

    search.query("lyon", function (value) {
    }, function (values) {
        setTimeout(function () {
            assert.equal(values.length, 3);
            done();
        },500);
    }, 'gps IS NOT NULL');
});