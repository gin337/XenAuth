
# XenAuth 💫

A simple auth system with integrated hwd check designed to be fast and friendly for your XenForo Forum. 🗝️





[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://choosealicense.com/licenses/mit/)


## Installation 👋

- 1.Put auth.php inside your webroot where xenforo is located.
- 2.Create a new config.json in /var/config/
```json
{
    "SQL_HOST": "localhost",
    "SQL_USER": "root", 
    "SQL_PASS": "password", 
    "SQL_DB": "db",
    
    "MASTER_KEY": "dontsharethiskey"

    
}

```


- 3.Create a new table in forum db named xf_user_info
```sql
CREATE TABLE xf_user_info (
  user_id INT PRIMARY KEY,
  hwid VARCHAR(255) NOT NULL
);

```
Use the examples i made or read down below how to make a request.


## API Reference 🚀

#### Get Status

```http
  GET /auth.php?status
```

| Response | Type     | Description                |
| :-------- | :------- | :------------------------- |
| `200` | `json` | Will return 200 if everything is fine |

#### Logging in

```http
  POST /auth.php?compare
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `username`| `string` | **Required** |
| `password`| `string` | **Required** |
| `hwid`    | `string` | **Required** |




## C# Example 🧊

```csharp
private static readonly string apiurl = "https://yourforum/auth.php";

// Define API Url
```

Check if API is reachable
```csharp
bool StatusCheck = await XenAuth.XenAuth.Status(apiurl);
Console.WriteLine(StatusCheck);

// Returns true if connected.
```

Logging in
```csharp
int login = await XenAuth.XenAuth.Compare("username", "password", apiurl);

// Returns the Response Code
```

A simple Example
```csharp
using XenAuth;

static void Main(string[] args)
{

Console.WriteLine("Status: " + XenAuth.XenAuth.Status("https://yourforum/auth.php").Result);
// Get Status
Console.WriteLine("Logging in: " + XenAuth.XenAuth.Compare("username", "password", "https://yourforum/auth.php").Result);
// Log in
Console.ReadKey();

}

```
## Feedback 💖

Hope you like my first public project i will try
to maintain.
If you have questions or want to contribute.
ɠιɳ#7777 my discord.

