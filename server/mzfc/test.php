#!/usr/bin/php -q
<?php

    require_once __DIR__ . '/mongodb/autoload.php';

    $mongo = new MongoDB\Client();
    $db = $mongo->tt;
    $collection = $db->issues;

/*
    $insertOneResult = $collection->insertOne([
        'username' => 'admin',
	'email' => 'admin@example.com',
	'name' => 'Admin User',
	'attachments' => [ "file_3", "file_4"],
    ]);

    printf("Inserted %d document(s)\n", $insertOneResult->getInsertedCount());
*/

//    var_dump($insertOneResult->getInsertedId());


// выбрать документ кромен некоторых полей
//    $cursor = $collection->find([ "_id" => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ], [ "projection" => [ "email" => 0 ] ]);
//    $document = $collection->findOne([ "_id" => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ], [ "projection" => [ "username" => 0, "email" => 0 ] ]);
//
//    print_r($document);
/*
    $cursor = $collection->find([], [ 'sort' => [ '_id' => -1 ], 'projection' => [ 'username' => 0, 'email' => 0 ] ]);

    foreach ($cursor as $document) {
	print_r(json_decode(json_encode($document), true));
    }
*/
// добавить элемент в массив
//    $collection->updateOne([ "_id" => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ], [ '$push' => [ "attachments" => "file_3" ] ]);

// удалить элемент из массива
//    $collection->updateOne([ "_id" => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ], [ '$unset' => [ "attachments.1" => 1 ] ]);
//    $collection->updateOne([ "_id" => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ], [ '$pull' => [ "attachments" => null ] ]);

// установить элемент в массиве
//    $collection->updateOne([ "_id" => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ], [ '$set' => [ "attachments.1" => [ "name" => "file_X", "size" => 1024, "date" => "2022-07-28 14:40:00.000" ] ] ]);

// изменить поле в документе
//    $collection->updateOne([ "_id" => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ], [ '$set' => [ "email" => "mmikel@mail.ru" ] ]);

// удалить поле из документа
//    $collection->updateOne([ "_id" => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ], [ '$unset' => [ "email" => 1 ] ]);

//    $cursor = $collection->find([ "_id" => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ]);
//
//    foreach ($cursor as $document) {
//	print_r($document);
//    }

//    print_r($collection->distinct("attachments", [ "_id" => [ '$ne' => new MongoDB\BSON\ObjectID("62e261802600caa2f90f3ac2") ] ]));

// put file to database
/*
    $bucket = (new MongoDB\Client)->tt->selectGridFSBucket();

    $stream = $bucket->openUploadStream('my-file.txt');

    $contents = "bla-bla-bla";

    fwrite($stream, $contents);

    $id = $bucket->getFileIdForStream($stream);

    fclose($stream);

    echo "$id\n";
*/

// get file from database
/*
    $bucket = (new MongoDB\Client)->tt->selectGridFSBucket();

    $fileId = new MongoDB\BSON\ObjectId("62e50b9e27124546700e3d92");

    $stream = $bucket->openDownloadStream($fileId);
    $contents = stream_get_contents($stream);

    echo $contents;

    echo "\n";
*/

// delete file from database
/*
    $fileId = new MongoDB\BSON\ObjectId("62e50aae98aafdd0e0031f62");

    $bucket = (new MongoDB\Client)->tt->selectGridFSBucket();

    $bucket->delete($fileId);
*/

// get metadata
/*
    $fileId = new MongoDB\BSON\ObjectId("62e5087a61aeaad7eb02e6a2");

    $bucket = (new MongoDB\Client)->tt->selectGridFSBucket();

    $stream = $bucket->openDownloadStream($fileId);

    $metadata = $bucket->getFileDocumentForStream($stream);

    print_r($metadata);
*/

// set metadata
/*
    $fileId = new MongoDB\BSON\ObjectId("62e5087a61aeaad7eb02e6a2");

    $fsFiles = "fs.files";
    $collection = (new MongoDB\Client)->tt->$fsFiles;

    $collection->updateOne([ "_id" => $fileId ], [ '$set' => [ "email" => "mmikel@mail.ru" ] ]);
*/

// drop index
/*
    $collection->dropIndex("fullText");
*/

// create full-text search index for username and name fields
/*
    $collection->createIndex([ "username" => "text", "name" => "text" ], [ "default_language" => "russian", "name" => "fullText" ]);
*/

// list indexes
/*
    print_r(array_map(function ($indexInfo) {
            print_r($indexInfo);
         return ['v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName(), 'ns' => $indexInfo->getNamespace()];
     }, iterator_to_array($collection->listIndexes())));
*/

// fullText search
/*
    $cursor = $collection->find([ '$text' => [ '$search' => "ADMIN" ] ]);

    foreach ($cursor as $document) {
        print_r(json_decode(json_encode($document), true));
    }
*/