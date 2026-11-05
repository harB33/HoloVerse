<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__), '.env.local');
$dotenv->safeLoad();
\Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->safeLoad();

final class DatabaseResult
{
    public function __construct(private PDOStatement $statement)
    {
    }

    public function fetch_assoc(): ?array
    {
        $row = $this->statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function fetch_all(int $mode = PDO::FETCH_ASSOC): array
    {
        return $this->statement->fetchAll($mode);
    }
}

final class DatabaseStatement
{
    private array $boundValues = [];
    private array $boundTypes = [];
    private bool $executed = false;
    public int $affected_rows = 0;

    public function __construct(private PDOStatement $statement)
    {
    }

    public function bind_param(string $types, mixed &...$values): bool
    {
        if (strlen($types) !== count($values) || preg_match('/[^idsb]/', $types) === 1) {
            throw new InvalidArgumentException('Provide a valid bind type for each bound value.');
        }

        foreach ($values as $index => &$value) {
            $position = $index + 1;
            $this->boundValues[$position] = &$value;
            $this->boundTypes[$position] = $types[$index];
        }
        unset($value);

        return true;
    }

    public function execute(): bool
    {
        foreach ($this->boundValues as $position => $value) {
            $pdoType = $value === null
                ? PDO::PARAM_NULL
                : match ($this->boundTypes[$position]) {
                    'i' => PDO::PARAM_INT,
                    'b' => PDO::PARAM_LOB,
                    default => PDO::PARAM_STR,
                };
            $this->statement->bindValue($position, $value, $pdoType);
        }

        $this->executed = $this->statement->execute();
        $this->affected_rows = $this->statement->rowCount();

        return $this->executed;
    }

    public function get_result(): DatabaseResult
    {
        if (!$this->executed) {
            throw new LogicException('Execute the statement before fetching its result.');
        }

        return new DatabaseResult($this->statement);
    }

    public function close(): void
    {
        $this->statement->closeCursor();
    }
}

final class DatabaseConnection
{
    public function __construct(private PDO $pdo)
    {
    }

    public function prepare(string $query): DatabaseStatement
    {
        return new DatabaseStatement($this->pdo->prepare($query));
    }

    public function query(string $query): DatabaseResult
    {
        return new DatabaseResult($this->pdo->query($query));
    }

    public function begin_transaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
}

$dsn = getenv('SUPABASE_DB_DSN');
$username = getenv('SUPABASE_DB_USER');
$password = getenv('SUPABASE_DB_PASSWORD');
$databaseUrl = getenv('SUPABASE_DB_URL');

if (!$dsn && $databaseUrl) {
    $connection = parse_url($databaseUrl);
    if (
        $connection === false
        || !in_array($connection['scheme'] ?? '', ['postgres', 'postgresql'], true)
        || empty($connection['host'])
        || empty($connection['path'])
        || empty($connection['user'])
        || !array_key_exists('pass', $connection)
    ) {
        throw new RuntimeException('SUPABASE_DB_URL must be a valid PostgreSQL connection URL.');
    }

    $parameters = [];
    parse_str($connection['query'] ?? '', $parameters);
    $username = rawurldecode($connection['user']);
    $password = rawurldecode($connection['pass']);
    $database = rawurldecode(ltrim($connection['path'], '/'));
    $host = $connection['host'];
    $port = $connection['port'] ?? 5432;
    $sslMode = $parameters['sslmode'] ?? 'require';
    $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode={$sslMode}";
}

if (!$dsn || !$username || $password === false) {
    throw new RuntimeException('Set SUPABASE_DB_URL or SUPABASE_DB_DSN, SUPABASE_DB_USER, and SUPABASE_DB_PASSWORD to connect to Supabase.');
}
if (!str_starts_with($dsn, 'pgsql:')) {
    throw new RuntimeException('SUPABASE_DB_DSN must be a PostgreSQL PDO DSN.');
}
if (!in_array('pgsql', PDO::getAvailableDrivers(), true)) {
    throw new RuntimeException('The PHP pdo_pgsql extension is required to connect to Supabase.');
}

$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_TIMEOUT => 5,
]);
$conn = new DatabaseConnection($pdo);
