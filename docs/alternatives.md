# Alternatives to brick/lock

The following projects offer overlapping functionality with brick/lock:

- **[symfony/lock](https://symfony.com/doc/current/components/lock.html)**

  **Key similarity:** symfony/lock and brick/lock share similar APIs for simple use cases:
    - `LockFactory::create()`
    - `LockInterface::acquire()`, `release()`

  **Key difference:** symfony/lock is designed around TTL-based locks, while brick/lock is designed around locks tied to the database connection.

- **[php-lock/lock](https://github.com/php-lock/lock)**

  **Key similarity:** When using MySQL, MariaDB or PostgreSQL backends, php-lock and brick/lock offer the same lock retention and auto-release characteristics.

  **Key difference:** php-lock is built around two specific patterns rather than traditional `acquire()`/`release()` methods:
    - `synchronized()` closures
    - `check()->then()` (double-checked locking)

## Detailed comparison

### Lock lifetime management

|                        | brick/lock | symfony/lock           | php-lock/lock                   |
|------------------------|------------|------------------------|---------------------------------|
| Tied to the connection | ✔          | `PostgreSqlStore` only | `MySQLMutex`, `PostgreSQLMutex` |
| TTL-based              |            | All other stores       | Other mutex implementations     |

**Tied to the connection**: Locks are tied to the database connection. Locks can be maintained for as long as necessary, provided the database connection remains active. If the process crashes and the database connection is lost, locks are automatically released.

**TTL-based**: Locks use TTL-based expiration. Locks expire automatically after a specified time, requiring periodic `refresh()` calls to maintain them. If `refresh()` calls are missed or delayed, the lock may expire while work is still in progress. If the process crashes, locks persist until the TTL expires.

> [!TIP]
> **TTL != timeout**: TTL determines how long you can keep a lock once you have it, while timeout determines how long you'll wait to get a lock.

### API Features

|                           | brick/lock | symfony/lock | php-lock/lock |
|---------------------------|------------|--------------|---------------|
| acquire, blocking         | ✔          | ✔            |               |
| acquire, non-blocking     | ✔          | ✔            |               |
| acquire, with timeout     | ✔          |              |               |
| release                   | ✔          | ✔            |               |
| wait, blocking            | ✔          |              |               |
| wait, with timeout        | ✔          |              |               |
| synchronize, blocking     | ✔          |              | ✔             |
| synchronize, non-blocking | ✔          |              | ✔             |
| synchronize, with timeout | ✔          |              | ✔             |
| atomic multi-key locking  | ✔          |              |               |
| double-checked locking    |            |              | ✔             |
| shared locks              |            | ✔            |               |

### Backend support

|            | brick/lock | symfony/lock | php-lock/lock |
|------------|------------|--------------|---------------|
| MySQL      | ✔          | ✔            | ✔             |
| MariaDB    | ✔          | ✔            | ✔             |
| PostgreSQL | ✔          | ✔            | ✔             |
| SQLite     |            | ✔            | ✔             |
| Oracle     |            | ✔            |               |
| SQL Server |            | ✔            |               |
| MongoDB    |            | ✔            |               |
| Redis      |            | ✔            | ✔             |
| ZooKeeper  |            | ✔            |               |
| Memcached  |            | ✔            | ✔             |
| Semaphore  |            | ✔            | ✔             |
| `flock()`  |            | ✔            | ✔             |

### Database connection support

|               | brick/lock | symfony/lock | php-lock/lock |
|---------------|------------|--------------|---------------|
| PDO           | ✔          | ✔            | ✔             |
| Doctrine DBAL | ✔          | ✔            |               |

### Lock name length limit

|            | brick/lock | symfony/lock                | php-lock/lock |
|------------|------------|-----------------------------|---------------|
| MySQL      | Unlimited  | Depends on table definition | 64 chars      |
| MariaDB    | Unlimited  | Depends on table definition | 64 chars      |
| PostgreSQL | Unlimited  | Depends on store *️         | Unlimited     |

*️ depends on table definition with `PdoStore` and `DoctrineDbalStore`, unlimited with `PostgreSqlStore`

