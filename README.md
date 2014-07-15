# GeoJSON to SQL

A PHP library for saving data from a GeoJSON file to an SQl database using PDO.

### Installation

`php composer.phar require m1ke/geojson-sql`

### Authors

Written by [Mike Lehan](http://twitter.com/m1ke) and [StuRents.com](http://sturents.com).

### Usage

* `process_and_save()` Returns the `$polygon` array formatted for inclusion in a query. Also accepts a PDO statement if you want to save in a database.
* `process_with_query(PDO $db,$table,$name)` Creates a PDO query with a single value which is replaced with the polygon

### Example

    require __DIR__.'/vendor/autoload.php';

    $db=new PDO('mysql:host=localhost;dbname=database','user','pass');

    $file_name='geojson.json';

    $geojson = new GeoJsonSql($file_name);
    $geojson->process_with_query($db,'table','polygon_field');
