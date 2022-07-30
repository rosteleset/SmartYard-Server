#!/usr/bin/php -q
<?php

    require_once __DIR__ . '/mongodb/autoload.php';

    $collection = (new MongoDB\Client)->tt->issues;


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

    $cursor = $collection->find([], [ 'sort' => [ '_id' => -1 ], 'projection' => [ 'username' => 0, 'email' => 0 ] ]);

    foreach ($cursor as $document) {
	print_r(json_decode(json_encode($document), true));
    }

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
