# Local Environment Design Document

## Services Overview

| Container       |      AWS Equivalent         |
|-----------------|-----------------------------|
| `traefik`       | Elastic Load Balancer (ELB) |
| `nginx`         | Front-facing ECS service    |
| `php-fpm`       | ECS container               |
| `mysql-aurora`  | Aurora RDS                  |
| `localstack`    | Amazon S3                   |

* **Production-like Stack:** Uses Nginx, PHP-FPM, MySQL, and LocalStack to simulate ELB, ECS, RDS, and S3.
* **Automatic HTTPS:** Traefik and `mkcert` provide trusted certs for local development.
* **Service Discovery:** Traefik automatically discovers and routes traffic to services.
* **Service Scaling:** Easily scale services up or down with a single command to test performance and load. ``` docker service scale aws-env_php-fpm=3 ```
* **Secure Secret Management:** Passwords are managed via Docker Secrets.


## 1. Explanation of the Flow:

1. Open https://app.localhost.

2. Traefik receives the request as it's bound to ports 80 and 443 on the host machine. 
   It handles the TLS termination using the mkcert certificate, and redirects HTTP requests to HTTPS.

3. Traefik forwards the request to the Nginx container, which is listening on its internal port 80.

4. Request Handling:
4a. (Static Files): If the request is for a static asset like a CSS file or an image, Nginx serves it directly and the request is complete.
4b. (PHP Files): If the request is for a .php file, Nginx passes it to the php-fpm service via the FastCGI protocol.

5. Application & Data Logic:
5a. (Database): The PHP code executes and communicates with the mysql container for any database operations.
5b. (AWS Services): The PHP code communicates with the localstack container for any AWS calls, such as uploading a file to S3.

This entire process happens within the isolated Docker Swarm `overlay` network, simulating a private cloud VPC.


## 2. Core Architecture Philosophy

The environment is built on a container-based architecture with **Docker Swarm**. This approach was chosen to accurately simulate a production cloud orchestrator like AWS ECS.

* **Declarative State:** The `docker stack deploy` command ensures the running environment always matches this declared state.
* **Service Resiliency:** Swarm's design, which includes restart policies and health checks, encourages the development of resilient services.
* **Native Scaling:** The ability to scale services declaratively or imperatively provides a way to test application behavior under load.


## 3. Tool Selection & Rationale

Each component of the stack was chosen to map directly to a production AWS service.

### Container Orchestration (ECS/EC2 Simulation)
* **Tool:** **Docker Swarm**
* **Rationale:** To address the requirement for "scaling/orchestration," this environment uses Docker Swarm. It provides a more accurate simulation of how ECS manages services than `docker-compose` alone. Key benefits include:
    * **Scaling:** The `deploy.replicas` key in the docker-compose file allows us to define how many instances of a service should run. We can dynamically scale services with `docker service scale`, and Traefik will automatically adjust its load balancing pool.
    * **Health Checks & Resiliency:** Swarm actively monitors the health of services and will restart containers that fail.

### Database (RDS Aurora Simulation)
* **Tool:** **Official MySQL Docker Image (`mysql:8.0`)**
* **Rationale:** We use the official Docker image for the database engine our RDS Aurora instance is compatible with. This allows application code to interact with a database that behaves identically to production. Data is persisted using a named Docker volume.

### Load Balancing & Proxy (ELB & HTTPS Simulation)
* **Tool:** **Traefik + mkcert**
* **Rationale:** Traefik is configured to use its **Swarm Provider**, allowing it to dynamically create routes by inspecting the Swarm's state, rather than just standalone containers. The combination with `mkcert` provides a seamless, professional local HTTPS experience without browser warnings.

### Object Storage (S3 Simulation)
* **Tool:** **LocalStack**
* **Rationale:** LocalStack provides a unified mock AWS endpoint (`http://localstack:4566`) for all simulated AWS services. This allows the PHP application's AWS SDK to be configured simply and consistently, providing an accurate simulation of the real AWS environment.

### Web Server & Application Runtime
* **Tool:** **Nginx + PHP-FPM**
* **Rationale:** This high-performance pattern separates the web server (Nginx) from the application processor (PHP-FPM), which is more scalable and resource-efficient than traditional module-based setups.


## 4. Secret Management

To avoid hardcoding passwords and sensitive information, this environment utilizes **Docker Secrets**.

* **Tool:** Docker Secrets (Native Swarm feature)
* **Rationale:** This is the most secure method for handling sensitive data in a Swarm environment and closely mirrors how secrets are managed in cloud platforms like AWS Secrets Manager.
* **Mechanism:** Passwords are stored in local text files (excluded from Git). These files are used to create managed `docker secret` objects. The Swarm securely mounts these secrets into the specified containers as files in an in-memory filesystem. The application and database images are configured to read passwords directly from these files, ensuring secrets never appear in `docker inspect` output, environment variables, or container logs.


## 5. Networking Strategy

All services are connected to a custom Docker **overlay** network (`aws-network`). The `overlay` driver is essential for Swarm mode, as it allows containers to communicate with each other regardless of which node they are running on in a potential multi-node cluster. This provides a more realistic simulation of a production VPC than a standard bridge network.

