1. Выполнить установку зависимостей
    ```
    cp /opt/rbt/server/syslog/
    npm install
    ```
2. Создать и отредактировать конфиг:
    ```
    cp ./config.sample.json ./config.json
    ```
3. Добавить пользователя для запуска cервиса Intercom syslog:
    ````
    groupadd isyslog
    useradd -g isyslog -s /bin/true -d /dev/null isyslog
    
    директория для logfiles
    /var/log/syslog_intercom/ 
    ````
4. Logrotate для локального хранения сообщений
    ````
    echo '/var/log/syslog_intercom/*.log {
            daily
            missingok
            rotate 3
            compress
            notifempty
    }' > /etc/logrotate.d/syslog_intercom
    ````

5. Добавить сервисы systemd в соответствии с импользуемыми моделями домофонов (Beward, QTECH, Интерсвязь - Сокол )
    ###### BEWARD
    ````
    echo '[Unit]
    Description=Intercom syslog service for Beward
    Documentation=https://github.com/rosteleset/rbt/tree/main/doc
    After=network.target
    
    [Service]
    Type=simple
    WorkingDirectory=/opt/rbt/server/syslog/
    ExecStart=/usr/bin/node /opt/rbt/server/syslog/beward.js --config=beward
    Restart=on-failure
    User=isyslog
    KillMode=process
    StandardOutput=file:/var/log/syslog_intercom/syslog_intercom_beward.log
    StandardError=file:/var/log/syslog_intercom/syslog_intercom_beward.error.log
    
    [Install]
    WantedBy=multi-user.target' > /etc/systemd/system/syslog_intercom_beward.service
    ````

    ###### BEWARD, только для модели DS06A
    ````
    echo '[Unit]
    Description=Intercom syslog service for Beward DS06A
    Documentation=https://github.com/rosteleset/rbt/tree/main/doc
    After=network.target
    
    [Service]
    Type=simple
    WorkingDirectory=/opt/rbt/server/syslog/
    ExecStart=/usr/bin/node /opt/rbt/server/syslog/beward.js --config=beward_ds
    Restart=on-failure
    User=isyslog
    KillMode=process
    StandardOutput=file:/var/log/syslog_intercom/syslog_intercom_beward_ds06a.log
    StandardError=file:/var/log/syslog_intercom/syslog_intercom_beward_ds06a.error.log
    
    [Install]
    WantedBy=multi-user.target' > /etc/systemd/system/syslog_intercom_beward_ds06a.service
    ````

    ###### QTECH
    ````
    echo '[Unit]
    Description=Intercom syslog service for QTECH
    Documentation=https://github.com/rosteleset/rbt/tree/main/doc
    After=network.target
    
    [Service]
    Type=simple
    WorkingDirectory=/opt/rbt/server/syslog/
    ExecStart=/usr/bin/node /opt/rbt/server/syslog/qtech.js
    Restart=on-failure
    User=isyslog
    KillMode=process
    StandardOutput=file:/var/log/syslog_intercom/syslog_intercom_qtech.log
    StandardError=file:/var/log/syslog_intercom/syslog_intercom_qtech.error.log
    
    [Install]
    WantedBy=multi-user.target' > /etc/systemd/system/syslog_intercom_qtech.service
    ````

    ###### Интерсвязь (Сокол)
    ````
    echo '[Unit]
    Description=Intercom syslog service for Intersviaz
    Documentation=https://github.com/rosteleset/rbt/tree/main/doc
    After=network.target
    
    [Service]
    Type=simple
    WorkingDirectory=/opt/rbt/server/syslog/
    ExecStart=/usr/bin/node /opt/rbt/server/syslog/is.js
    Restart=on-failure
    User=isyslog
    KillMode=process
    StandardOutput=file:/var/log/syslog_intercom/syslog_intercom_is.log
    StandardError=file:/var/log/syslog_intercom/syslog_intercom_is.error.log
    
    [Install]
    WantedBy=multi-user.target' > /etc/systemd/system/syslog_intercom_is.service
    ````


6. Запуск Intercom syslog  сервисов
    ````
    sudo systemctl daemon-reload
    
    sudo systemctl enable syslog_intercom_beward.service && sudo systemctl start syslog_intercom_beward.service
    sudo systemctl enable syslog_intercom_beward_ds06a.service && sudo systemctl start syslog_intercom_beward_ds06a.service
    sudo systemctl enable syslog_intercom_qtech.service $$ sudo systemctl start syslog_intercom_qtech.service
    sudo systemctl enable syslog_intercom_is.service && sudo systemctl enable syslog_intercom_is.service
    
    ````