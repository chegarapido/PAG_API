# PAG-API

This is a simple webservice to interact with most useful methods in Pagseguro Payments Gateway API.

# Summary
1. [Getting Started](#start-here)
    1.1. [Dependencies](#dependencies)
    1.2. [Setting up](#setup)
    1.3. [SSL Certificates](#ssl)
    1.4. [Ambients](#ambients)
    1.5. [API Testers Collections](#collections)
2. [Authentication](#auth)
3. Endpoints
    3.1. [Crypt](#e-crypt)
    3.2. [Orders](#e-orders)
    3.3. [Charges](#e-charges)

# Getting started <a id='start-here'/>

### Dependencies <a id='dependencies'/>

This Microservice utilize the following dependencies:

- PHP >= 7.2
- cURL >= 6.x
- GuzzleHttp 7.4
- PHP-cURL extension enabled
- Composer >= 1.x

Starting from the point that this microservice does not consumes database features, you can try to setup with a docker container with external network access. Note that this is not the focus from this service and may also not be supported.

### Setting Up <a id='setup'/>

In order to get project working, you just need to clone this repository, and run the following command from project root:

```bash
composer update
```

After that, if the project is self-contained into a Apache or Nginx server folder (like /var/www or /srv/http, in linux) you already ready to use the service. If you are just testing and want to run in a localhost server, you can use PHP built-in server to run the project locally from the following command in project root:

```bash
php -d=display_errors -S localhost:8080
```

This will setup project in ``http://localhost:8080`` with debug mode on.

### SSL Certificates <a id='ssl'/>

The project already has default Pagseguro SSL certificates enabled and downloaded in ``certificates`` folder. If you want to use a custom one .key and/or .pem files, you just have to replace the pre-built files with the same name for each of them, or put they in the certificates folder and change ``libs/Pagseguro/API.php`` class to use the custom files.

### Ambients <a id='ambients'/>

As off Pagseguro, this microservice will allow two ambients to run your endpoints, and it must be defined in body as you will see bellow. The supported ambients are:

| Name | Description | Pagseguro equivalent |
| ---- | ----------- | -------------------- |
| homolog | Testing ambient. In this ambient mode, any operation, payment or charge will have no effects in real word, and you can use any testing data. | sandbox
| prod | Production ambient. Actions here will have effect in real word, and this ambient will not accept testing data. Only use this mode after you has tested everything in homolog ambient. | production |

### API Testers Collections <a id='collections'/>

By default, this repository already comes with Insomnia JSON collection for all endpoints available, with complete version of each of them, that contains all available parameters, and simple version that contains just the required body params.

To setup Insomnia collection, you have to load the JSON file as you always does, and setup the params ``url``, ``auth_token``, ``ambient`` and ``fake_authorization`` in Local environment variables section.

# Authentication

Except the endpoints listed in [Crypt](#e-crypt) section, all the other endpoints are protected by the same authentication method, that has te following logic:

1. You need to setup a Authentication basic token, that you can change in ``libs/Http/Request/Middlewares/AuthOnly.php`` class file. This token will be included in each request (including crypt endpoints) in the header ``Authentication: Basic TOKEN_HERE``
2. You need to pass your Pagseguro developer credentials. To be more secure, we use a own  encryption method to this param. You will use crypt endpoints to generate a base64 token that is full-encrypted, and you will pass in all requests the header ``Authorization: Bearer BASE64_TOKEN``

# Endpoints

### Crypt <a id='e-crypt'/>

- **POST** - /encrypt.php - Will encrypt all request body and return the base64 string.
```json
{
    "body": {
        "client_id": "YOUR_CLIENT_ID",
        "client_secret": "YOUR_CLIENT_SECRET",
        "bearer": "YOUR_PAGSEGURO_BEARER_TOKEN"
    },
    "responses": {
        "200": {
            "message": "Encrypted with success",
            "data": {
                "string": "Your encrypted base64"
            }
        }
    }
}
```

### Orders <a id='e-orders'/>

- **POST** - /order.php - Create orders
```json
{
    "body": {
        "ambient": {
            "type": "string",
            "values": ["homolog", "prod"]
        },
        "payment_method": {
            "type": "string",
            "values": ["pix", "credit_card"]
        },
        "items": {
            "description": "Items in your order",
            "type": "array",
            "items": {
                "description": "A single object that represents a single item",
                "type": "object",
                "keys": {
                    "name": "string",
                    "price": {
                        "description": "Unit price to this item",
                        "type": "int"
                    },
                    "quantity": {
                        "description": "Item quantity in order",
                        "type": "int",
                        "required": false,
                        "default": 1
                    },
                    "custom_id": {
                        "description": "Custom ID field to your item",
                        "type": ["int", "string"],
                        "required": false,
                        "default": "UNIX Timestamp that the item was added"
                    }
                }
            }
        },
        "credit_card": {
            "type": "object",
            "required": false,
            "required_with": "payment_method = credit_card",
            "keys": {
                "number": ["int", "string"],
                "cvv": ["int", "string"],
                "expires_month": {
                    "description": "Month that the card expires",
                    "type": "string",
                },
                "expires_year": {
                    "description": "Year that the card expires",
                    "type": "string",
                },
                "holder": "string",
                "payment_description": {
                    "description": "Payment short description to user",
                    "type": "string"
                }
            }
        },
        "metadata": {
            "type": ["object", "array"],
            "description": "Store here all data that you want to recovery post"
        },
        "callbacks": {
            "description": "Callbacks webhooks to status updates in order",
            "type": "array",
            "required": false,
            "items": "string"
        },
        "custom_id": {
            "description:": "A custom ID to your order",
            "type": ["int", "string"],
            "required": false
        }
    }
}
```

- **GET** - /get_order.php - Search by a order.
```json
{
    "query": {
        "ambient": {
            "type": "string",
            "values": ["homolog", "prod"]
        },
        "order_id": {
            "description": "Your Pagseguro order ID (not the custom)",
            "type": "string"
        }
    }
}
```

### Charges <a id='e-charges'/>

- **POST** - /charge.php - Make a new charge
```json
{
    "body": {
        "ambient": {
            "type": "string",
            "values": ["homolog", "prod"]
        },
        "payment_method": {
            "type": "string",
            "values": ["debit_card", "credit_card"]
        },
         "card": {
            "type": "object",
            "required": true,
            "keys": {
                "number": ["int", "string"],
                "cvv": ["int", "string"],
                "expires_month": {
                    "description": "Month that the card expires",
                    "type": "string",
                },
                "expires_year": {
                    "description": "Year that the card expires",
                    "type": "string",
                },
                "holder": "string",
                "store_manufaturer": {
                    "description": "Your store's manufaturer name",
                    "type": "string"
                }
            }
        },
        "use_recurrence": {
            "description": "Set if charge must to be recurent.",
            "type": "bool",
            "default": false
        },
        "metadata": {
            "type": ["object", "array"],
            "description": "Store here all data that you want to recovery post"
        },
        "callbacks": {
            "description": "Callbacks webhooks to status updates in order",
            "type": "array",
            "required": false,
            "items": "string"
        },
        "custom_id": {
            "description:": "A custom ID to your order",
            "type": ["int", "string"],
            "required": false
        }
    }
}
```

- **DELETE** - /cancel-charge.php - Cancel a charge.
```json
{
    "query": {
        "ambient": {
            "type": "string",
            "values": ["homolog", "prod"]
        },
        "charge_id": {
            "description": "Your Pagseguro charge ID (not the custom)",
            "type": "string"
        }
    }
}
```