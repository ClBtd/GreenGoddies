<?php

namespace App\Factory;

use App\Entity\User;
use App\Utils\Functions;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'firstName' => self::faker()->firstName($gender = 'male'|'female'),
            'lastName' => self::faker()->unique()->lastName(),
            'password' => $this->hasher->hashPassword(new User(), 'password'),
            'roles' => ['ROLE_USER'],
        ];
    }

    /** Création de l'email à partir des noms générés
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
        ->afterInstantiate(function(User $user): void {
            $user->setEmail(Functions::normalizeString($user->getFirstName() . '.' . $user->getLastName()) . '@example.com');
        });
    }
}
