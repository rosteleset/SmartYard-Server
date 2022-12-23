```
curl https://get.acme.sh | sh -s email=<your-email@example.com>
```

```
acme.sh --set-default-ca --server letsencrypt
```

```
acme.sh --issue -d example.com -w /var/www/html
```

```
acme.sh --install-cert -d example.com --key-file /etc/ssl/key.pem --fullchain-file /etc/ssl/cert.pem --reloadcmd "service nginx force-reload"
```