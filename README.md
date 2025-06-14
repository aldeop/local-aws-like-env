# Local Development Environment

This project provides a container-based local development environment designed to closely mirror an AWS production stack. 

## Setup Instructions

### Prerequisites

You must have the following tools installed on your local machine:
* [Git]
* [DockerDesktop]
* [mkcert]

### 1. Clone the Repository

```bash
git clone https://github.com/aldeop/local-aws-like-env.git
cd aws-like-local-env
```


### 1a. Initialize Docker Swarm

```bash
docker swarm init
```

### 2. Create Secret Files

Create a files with a passwords (don't commit it to Git).
```bash
echo "yoursecretrootpassword" > db_root_password.txt
echo "yoursecretdbpassword" > db_password.txt
```

Add the file to your .gitignore
```bash
echo "db_root_password.txt" >> .gitignore
echo "db_password.txt" >> .gitignore 
```

### 3. Create Docker Secrets
```bash
docker secret create db_root_password db_root_password.txt
docker secret create db_password db_password.txt
```

### 4. Generate SSL Certificate

This step creates the trusted SSL certificate for local HTTPS (run one-time command per machine).
```bash
mkcert -install
```

Generate the certificate for our local domain
```bash
mkcert -key-file traefik/certs/key.pem -cert-file traefik/certs/cert.pem "app.localhost"
```

### 5. Build the Application Image

Docker Swarm does not build images on deploy, so we should build application image first.
The tag 'php-fpm-app:latest' should match the image name in the stack file
```bash
docker build -t php-fpm-app:latest ./php
```

### 6. Deploy the Stack

Deploy the entire stack to the Swarm. We'll name our stack `aws-env`.
```bash
docker stack deploy -c docker-compose.yml aws-env
```

It may take few minutes for all services to start. 
To check the status use command `docker service ls`.


## How to Use the Environment

|        Service        |                      Address                   |
|:----------------------|:-----------------------------------------------|
| **PHP Application**   | [https://app.localhost](https://app.localhost) |
| **Traefik Dashboard** | [http://localhost:8080](http://localhost:8080) |
| **Database Host**     | `mysql-aurora` (for the PHP app)               |
| **AWS SDK Endpoint**  | `http://localstack:4566`                       |


### Scaling Services

To scale manually the PHP-FPM service to 3 replicas run:
``` docker service scale aws-env_php-fpm=3 ```

Traefik will automatically start load-balancing across all available replicas.

### Management Commands

* **List running services:** `docker service ls`
* **View logs for a service:** `docker service logs aws-env_php-fpm`
* **Shut down the entire stack:** `docker stack rm aws-env`

