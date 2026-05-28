<?php

   /**
    * @api {post} /mobile/address/getStories get a list of storeis
    * @apiVersion 1.0.0
    * @apiDescription **метод готов**
    *
    * @apiGroup Address
    *
    * @apiHeader {String} authorization authorization token
    *
    * @apiSuccess {Object[]} - array of stories
    * @apiSuccess {String} -.imageUrl url of the story image
    * @apiSuccess {String} -.title title of the story
    * @apiSuccess {String} -.subtitle subtitle of the story
    * @apiSuccess {String} -.url url to the story content
    * @apiSuccess {String="popup", "view", "openApp"} -.presentMethod presentation method
    */

   auth();

   response();
