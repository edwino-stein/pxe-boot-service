# PXE Boot Service

### Docker development container

```bash
# To build docker development image
$ docker build -t pxe-boot-service:dev -f docker/dev.dockerfile .

# To run docker development container
$ docker run --rm -v $PWD/application:/var/www/localhost/application -p 8080:8080 pxe-boot-service:dev
```
