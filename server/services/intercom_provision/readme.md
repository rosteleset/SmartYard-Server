### Draft
Prototype provision server for client intercom (Akuvox SIP indoor intercom).  
SIP intercom get actual config by serial on startup from url http://rbt-example.com:9992/provision/{{SERIAL_NUMBER}}.cfg


TODO:  
- implement api methods in SmartYard-Server 
  ```
  `http://${API_ADDRESS}/internal/intercom/${serial}`
  ```
- add table for register client SIP intercom "house_flats_intercoms"

    ```
    id,  flat_id , inntercom_id AS serial,
    ```