<a href="https://roadrunner.dev" target="_blank">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://github.com/roadrunner-server/.github/assets/8040338/e6bde856-4ec6-4a52-bd5b-bfe78736c1ff">
    <img align="center" src="https://github.com/roadrunner-server/.github/assets/8040338/040fb694-1dd3-4865-9d29-8e0748c2c8b8">
  </picture>
</a>

# RoadRunner PHP API DTO

[![PHP Version Require](https://poser.pugx.org/roadrunner-php/roadrunner-api-dto/require/php)](https://packagist.org/packages/roadrunner-php/roadrunner-api-dto)
[![Latest Stable Version](https://poser.pugx.org/roadrunner-php/roadrunner-api-dto/v/stable)](https://packagist.org/packages/roadrunner-php/roadrunner-api-dto)
[![Total Downloads](https://poser.pugx.org/roadrunner-php/roadrunner-api-dto/downloads)](https://packagist.org/packages/roadrunner-php/roadrunner-api-dto)

This is a GitHub repository and Composer package that provides PHP Data Transfer Object (DTO) messages for RoadRunner
API protocol buffer files, available at https://github.com/roadrunner-server/api.

With this repository, you can use pre-generated DTO messages for the following RoadRunner plugins:

- Centrifugo
- HTTP
- Jobs
- KV
- Lock
- Plugins
- Service
- Status
- WebSockets

The repository contains the pre-generated DTO files, and you can include the package in your PHP project to use them for
making RPC calls to the RoadRunner server.

## Installation

You can install the package. Simply run the following command:

```bash
composer require roadrunner-php/roadrunner-api-dto
```

## Generating DTOs

If you would like to generate the DTOs yourself, you can use the `generate.sh` script. This script will generate the
DTOs for all of the plugins, and place them in the `generated/` directory.

But before that, you will need to compile the `grpc_php_plugin`. Follow [this instruction](https://github.com/grpc/grpc/blob/master/src/php/README.md#grpc_php_plugin-protoc-plugin) to build the file.

Then run:

```bash
./generate.sh
```

> Note: You will need to have the `protoc` >= 3.15 binary installed on your system.

## Contribution

Contributions are welcome! If you would like to contribute to this project, please open an issue or pull request.

## License

This repository is licensed under the MIT license. See [LICENSE](./LICENSE) for more information.
