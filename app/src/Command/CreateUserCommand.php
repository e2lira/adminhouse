<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\RolesTable;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

/**
 * Creates a user with its credentials, mainly to bootstrap the first super administrator.
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
        if ($users->exists(['username' => $username])) {
            return $this->fail($io, sprintf('A user named "%s" already exists.', $username));
        }

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

        try {
            $users->getConnection()->transactional(function () use ($users, $user, $password, $args): void {
                $users->saveOrFail($user, ['associated' => false]);

                $credentials = $this->fetchTable('UserCredentials')->newEntity([
                    'user_id' => $user->id,
                    'password_hash' => (new DefaultPasswordHasher())->hash($password),
                    'password_changed_at' => DateTime::now(),
                ]);
                $this->fetchTable('UserCredentials')->saveOrFail($credentials);

                if ($args->getOption('super-administrator')) {
                    $this->assignSuperAdministrator((int)$user->id);
                }
            });
        } catch (Throwable $e) {
            return $this->fail($io, 'The user could not be created: ' . $e->getMessage());
        }

        $io->success(sprintf('User "%s" created.', $username));

        return static::CODE_SUCCESS;
    }

    /**
     * @param int $userId User receiving the global role.
     * @return void
     */
    private function assignSuperAdministrator(int $userId): void
    {
        $assignments = $this->fetchTable('RoleAssignments');
        $role = $this->fetchTable('Roles')->find()
            ->where(['code' => RolesTable::SUPER_ADMINISTRATOR])
            ->firstOrFail();

        $assignments->saveOrFail($assignments->newEntity([
            'user_id' => $userId,
            'role_id' => $role->id,
            'development_id' => null,
            'starts_on' => Date::today(),
            'read_only' => false,
        ]));
    }

    /**
     * Read a secret without echoing it when the terminal allows it.
     */
    private function askSecret(ConsoleIo $io, string $prompt): string
    {
        $canHideInput = DIRECTORY_SEPARATOR === '/' && function_exists('stream_isatty') && stream_isatty(STDIN);
        if ($canHideInput) {
            shell_exec('stty -echo');
        }

        try {
            $value = (string)$io->ask($prompt);
        } finally {
            if ($canHideInput) {
                shell_exec('stty echo');
                $io->out('');
            }
        }

        return $value;
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
