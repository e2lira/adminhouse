<?php
declare(strict_types=1);

namespace App\Command;

use App\Clock\BusinessClock;
use App\Console\HiddenInput;
use App\Console\HiddenInputUnavailable;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\LocatorAwareTrait;
use PDOException;
use Throwable;

/**
 * Creates a user with its credentials, mainly to bootstrap the first super administrator.
 *
 * Account creation is a bootstrap, not an application feature: the runtime database role cannot write `users` or
 * `user_credentials` and has no EXECUTE on the definer function `user_create()` (a role that can be tricked into running
 * SQL must not be able to mint a super administrator). The command therefore connects as the migration (owner) login,
 * the `migrations` datasource, which exists only for the CLI. In production that login is NOLOGIN outside migration
 * windows: enable it (`provision_db_roles.php --owner-login`) while creating the first account.
 *
 * The password is always read interactively; it is never accepted as an argument or option, so it
 * cannot leak through the shell history or the process list.
 */
class CreateUserCommand extends Command
{
    use LocatorAwareTrait;

    private const MIN_PASSWORD_LENGTH = 12;

    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'create_user';
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Create a user and its credentials. The password is requested interactively.';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Parser.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription())
            ->addArgument('username', [
                'help' => 'Username of the new user.',
                'required' => true,
            ])
            ->addOption('super-administrator', [
                'help' => 'Also assign the global super_administrator role.',
                'boolean' => true,
            ]);
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $username = (string)$args->getArgument('username');

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $user = $users->newEntity(['username' => $username, 'status' => 'active']);
        if ($user->getErrors()) {
            return $this->fail($io, 'Invalid username: ' . implode(' ', $this->flatten($user->getErrors())));
        }

        try {
            $password = $this->askSecret($io, 'Password');
            if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
                return $this->fail(
                    $io,
                    sprintf('The password must have at least %d characters.', self::MIN_PASSWORD_LENGTH),
                );
            }
            if ($password !== $this->askSecret($io, 'Repeat the password')) {
                return $this->fail($io, 'The passwords do not match.');
            }
        } catch (HiddenInputUnavailable $e) {
            return $this->fail($io, 'This terminal cannot hide the password; nothing was created. ' . $e->getMessage());
        }

        try {
            $hash = (new DefaultPasswordHasher())->hash($password);
            $startsOn = (new BusinessClock())->today()->format('Y-m-d');
            $asSuper = (bool)$args->getOption('super-administrator');

            // The only write path to users and credentials is `user_create()`, executed here by the owner login
            // (RLS-SEC-001). One transaction, so the user, its credentials and its role land together or not at all.
            /** @var \Cake\Database\Connection $owner */
            $owner = ConnectionManager::get('migrations');
            $owner->transactional(function (Connection $owner) use ($username, $hash, $asSuper, $startsOn): void {
                $owner->execute(
                    'SELECT user_create(:username, :hash, :super, CAST(:starts_on AS date))',
                    ['username' => $username, 'hash' => $hash, 'super' => $asSuper, 'starts_on' => $startsOn],
                    ['username' => 'string', 'hash' => 'string', 'super' => 'boolean', 'starts_on' => 'string'],
                );
            });
        } catch (Throwable $e) {
            if ($this->isDuplicateUsername($e)) {
                return $this->fail($io, sprintf('A user named "%s" already exists.', $username));
            }

            return $this->fail($io, 'The user could not be created: ' . $e->getMessage());
        }

        $io->success(sprintf('User "%s" created.', $username));

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Throwable $error Error raised while creating the account.
     * @return bool Whether PostgreSQL rejected the username as a duplicate.
     */
    private function isDuplicateUsername(Throwable $error): bool
    {
        for ($e = $error; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof PDOException && ($e->errorInfo[0] ?? null) === '23505') {
                return true;
            }
        }

        return false;
    }

    /**
     * Read a secret without echoing it (REBUILD-003-F1). On a terminal that cannot hide input this throws
     * HiddenInputUnavailable rather than echoing. `Console.hideSecrets` is false only in the test suite,
     * whose stub input never reaches a screen.
     */
    private function askSecret(ConsoleIo $io, string $prompt): string
    {
        if (Configure::read('Console.hideSecrets', true)) {
            $hidden = new HiddenInput();
            if ($hidden->isTerminal()) {
                $io->out($prompt . ': ', 0);
                $value = (string)$hidden->read();
                $io->out('');

                return $value;
            }
        }

        return (string)$io->ask($prompt);
    }

    /**
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @param string $message Error message.
     * @return int
     */
    private function fail(ConsoleIo $io, string $message): int
    {
        $io->error($message);

        return static::CODE_ERROR;
    }

    /**
     * @param array<string, mixed> $errors Entity errors, possibly nested.
     * @return list<string>
     */
    private function flatten(array $errors): array
    {
        $messages = [];
        array_walk_recursive($errors, function ($message) use (&$messages): void {
            $messages[] = (string)$message;
        });

        return $messages;
    }
}
