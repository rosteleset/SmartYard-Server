```
apt install postgresql
```

```
su -l postgres
psql
```

```
DROP DATABASE IF EXISTS rbt;
DROP USER IF EXISTS rbt;
CREATE DATABASE rbt;
CREATE USER rbt WITH ENCRYPTED PASSWORD 'rbt';
GRANT ALL ON DATABASE rbt TO rbt;
\c rbt;
GRANT ALL ON SCHEMA public TO rbt;
```