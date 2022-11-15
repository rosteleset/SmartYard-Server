<?php
    //internal API endpoints
    use internal\services\router;
    use internal\actions\actions;
    
    Router::post("/lastSeen", Actions::class,"lastSeen");
    Router::post("/getStreamID", Actions::class,"getStreamID");
    Router::post("/openDoor", Actions::class,"openDoor");
    Router::post("/callFinished", Actions::class,"callFinished");
    Router::post("/setRabbitGates", Actions::class,"setRabbitGates");

    //Test endpoints
    Router::post("/test", Actions::class, "test");
    Router::get("/health", Actions::class, "health");