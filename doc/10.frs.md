### Требования к системе
В системе должны быть установлены docker и docker-compose 1.28.0+.  Запуск приводимых ниже скриптов предполагает, что вы находитесь в директории *docker* исходного кода проекта. Все инструкции приведены на примере ОС Ubuntu 20.04.
Получение исходников:
```bash
cd ~
git clone --recurse-submodules https://github.com/rosteleset/frs.git
cd ~/frs/docker
```

### Установка драйверов NVIDIA (если используется GPU)
На хост-сервер необходимо установить драйвера GPU. Можно использовать [описание](https://docs.nvidia.com/datacenter/tesla/tesla-installation-notes/index.html#ubuntu-lts) или выпполнить команду:

```bash
$ sudo ./setup_nvidia_drivers.sh
```
Перезагрузить систему.

### Установка NVIDIA Container Toolkit
Можно воспользоваться инструкцией [здесь](https://docs.nvidia.com/datacenter/cloud-native/container-toolkit/install-guide.html#getting-started) или выполнить команду:
```bash
sudo ./setup_nvidia_container_toolkit.sh
```

### Подготовка конфигурации
Используются переменные окружения:
* FRS_HOST_WORKDIR - рабочая директория FRS на хост-сервере;
* MYSQL_DB - название базы данных внутри контейнера MySQL;
* MYSQL_PORT - порт внутри контейнера MySQL;
* MYSQLX_PORT - порт для X плагина MySQL;
* MYSQL_PASSWORD - пароль для пользователя root внутри контейнера MySQL.
* WITH_GPU - признак использования GPU.

Например:
```bash
sudo \
FRS_HOST_WORKDIR=/opt/frs \
MYSQL_DB=db_frs \
MYSQL_PORT=3306 \
MYSQLX_PORT=33060 \
MYSQL_PASSWORD=123123 \
WITH_GPU=1 \
./prepare_config.sh
```
Если используется GPU, то необходимо создать TensorRT планы для работы нейронных сетей с помощью команды:
```bash
sudo ./tensorrt_plans.sh
```
После выполнения этих команд в директории */opt/frs* хост-сервера появятся необходимые файлы для работы FRS.

### Сборка контйнера FRS
```bash
sudo ./build_frs.sh
```

### Запуск FRS
* С использованием GPU:
```bash
sudo docker-compose -f /opt/frs/docker-compose-gpu.yml up
```
* Без GPU:
```bash
sudo docker-compose -f /opt/frs/docker-compose-cpu.yml up
```
