#### Firebase push notification service
1. Make file environment  ".env" or ".env_development" and edit APP_* vars
    ```shell
    cp .env_example .env
    ```
2. Make project config files
   ```
   assets/pushServiceAccountKey.json 
   assets/certificate-and-privatekey.pem
   ```
3. Add user and group:
    ```shell
    groupadd rbt
    useradd -g rbt -s /bin/true -d /dev/null rbt
    ```

4.  Make logging dir:
    ```shell
    mkdir -p /var/log/rbt_push_service/
    chown -R rbt:rbt /var/log/rbt_push_service/
    ```
5. Make logrotate:
    ```shell
    echo '/var/log/rbt_push_service/*.log {
        daily
        missingok
        rotate 7
        compress
        notifempty
        copytruncate
        dateext
        dateformat -%Y-%m-%d
    }' | tee /etc/logrotate.d/rbt_push
    ```
    Restart service
    ```shell   
    systemctl restart logrotate
    ```
6.  Make service:

    ```shell
    echo "[Unit]
    Description=SmartYard-Server push service
    Documentation=https://github.com/rosteleset/SmartYard-Server/tree/main/install
    After=network.target
    
    [Service]
    Type=simple
    WorkingDirectory=/opt/rbt/server/services/push
    ExecStart=/usr/bin/node /opt/rbt/server/services/push/push.js
    RestartSec=10
    Restart=always
    User=rbt
    Group=rbt
    LimitCORE=infinity
    KillMode=process
    StandardOutput=file:/var/log/rbt_push_service/push_service.log
    StandardError=file:/var/log/rbt_push_service/push_service.error.log
    SyslogIdentifier=rbt_push_service

    [Install]
    WantedBy=multi-user.target" | sudo tee /etc/systemd/system/rbt_push.service
    ```   

7. Enable and start service
    ```shell
    systemctl enable rbt_push.service 
    systemctl start rbt_push.service 
    systemctl status rbt_push.service 
    ```
