### Custodial Dogecash Payment Gateway

## How to install
-------------------
1. Import struct.sql into your MySQL database
2. Create a file called config.php with these contents:
    ```php
    $host = "localhost";
    $port = "56750";
    $username = "rpcuser";
    $password = "rpcpassword";
    $db_host = "localhost";
    $db_port = "3306";
    $db_database = "mysqldb";
    $db_username = "mysqlusername";
    $db_password = "mysqlpassword";
    ```
    Adjust the config to your needs
3. Host the webserver with the files!


## API Methods
--------------
### api.php:
Parameters:
- address: Your DogeCash Address

Returns if error:
```json
{
    "status": 400/500,
    "message": str
}
```
Returns if no error:
```json
{
    "status": 200,
    "api_key": str
}
```

### request.php:
Parameters:
- api_key: Your unique api key
- invoice: Unique invoid ID
- amount: Amount requested

Returns if error:
```json
{
    "status": 400/500,
    "message": str
}
```

Returns if no error:
```json
{
    "status": 200,
    "address": str
}
```

### return.php:
Parameters:
- api_key: Your unique api key
- invoice: Unique invoid ID

Returns if error:
```json
{
    "status": 400/500,
    "message": str
}
```

Returns if no error:
```json
{
    "status": 200,
    "inv_status": str
}
```

### address.php
Parameters:
Parameters:
- api_key: Your unique api key
- invoice: Unique invoid ID

Returns if error:
```json
{
    "status": 400/500,
    "message": str
}
```

Returns if no error:
```json
{
    "status": 200,
    "address": str
}
```
