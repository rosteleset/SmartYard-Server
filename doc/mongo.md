download from 

```
https://www.mongodb.com/try/download/community
```

```
wget https://fastdl.mongodb.org/linux/mongodb-linux-x86_64-ubuntu2004-6.0.3.tgz
```

run (without auth and clustering, localhost only)

```
./mongod --dbpath ../data --directoryperdb
```