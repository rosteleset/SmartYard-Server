### System requirements
Docker and Docker-compose 1.28.0+ must be installed. All scripts here are supposed to be run in *docker* directory of the FRS project. We use Ubuntu 20.04 as an example.
Get the source code of FRS:
```bash
cd ~
git clone --recurse-submodules https://github.com/rosteleset/frs.git
cd ~/frs/docker
```

### Install NVIDIA drivers (if you are using GPU)
NVIDIA drivers should be installed on the host. Steps are described [here](https://docs.nvidia.com/datacenter/tesla/tesla-installation-notes/index.html#ubuntu-lts) or you may run:

```bash
$ sudo ./setup_nvidia_drivers.sh
```
Reboot.

### Install NVIDIA Container Toolkit
Steps are described [here](https://docs.nvidia.com/datacenter/cloud-native/container-toolkit/install-guide.html#getting-started) or just run:
```bash
sudo ./setup_nvidia_container_toolkit.sh
```

### Create configuration
Set environment variables:
* FRS_HOST_WORKDIR - working directory on the host;
* MYSQL_DB - database name of a running MySQL container;
* MYSQL_PORT - MySQL port of a running container;
* MYSQLX_PORT - port for MySQL X plugin;
* MYSQL_PASSWORD - root password of a running MySQL container.
* WITH_GPU - GPU using flag.

And run *prepare_config.sh* script, for a example:
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
You should create TensorRT plans of models if you are using GPU:
```bash
sudo ./tensorrt_plans.sh
```
After these steps directory */opt/frs* should contain all necessary files for running FRS.

### Build FRS container
```bash
sudo ./build_frs.sh
```

### Run FRS
* With GPU:
```bash
sudo docker-compose -f /opt/frs/docker-compose-gpu.yml up
```
* Without GPU:
```bash
sudo docker-compose -f /opt/frs/docker-compose-cpu.yml up
```
