#### Firebase push notification service
1. Make file environment  ".env_production" or ".env_development" and edit APP_* vars
    ```
    cp .env_example .env_production
    ```
2. Make project config files 
   ```
   assets/pushServiceAccountKey.json 
   assets/certificate-and-privatekey.pem
   ```
3. Add user and group:
    ```
    groupadd rbt
    useradd -g rbt -s /bin/true -d /dev/null rbt
    ```

4.  Make logging dir:
    ````
    mkdir -p /var/log/rbt_push_service/
    chown -r rbt:rbt /var/log/rbt_push_service/
    ````
5. Make logrotate:
    ```
    touch /etc/logrotate.d/rbt_push
    
    /var/log/rbt_push_service/*.log {
            daily
            missingok
            rotate 3
            compress
            notifempty
    }
   
    systemctl restart logrotate
    ```

6.  Make service:
    ````
    touch /etc/systemd/system/rbt_push.service
    ````

    ```
    [Unit]
    Description=SmartYard-Server push service
    Documentation=https://github.com/rosteleset/SmartYard-Server/tree/main/install
    After=network.target
    
    [Service]
    Type=simple
    WorkingDirectory=/opt/rbt/services/push
    ExecStart=/usr/bin/node /opt/rbt/services/push/push.js
    RestartSec=10
    Restart=always
    User=rbt
    Group=rbt
    LimitCORE=infinity
    KillMode=process
    StandardOutput=file:/var/log/rbt_push_service/push_service.log
    StandardError=file:/var/log/rbt_push_service/push_service.error.log
    
    [Install]
    WantedBy=multi-user.target
    ```
7. Enable and start service
    ```
    systemctl enable rbt_push.service 
    systemctl start rbt_push.service 
    systemctl status rbt_push.service 
    ```