<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Traits\Entity\HasDateCreated;
use App\Traits\Entity\HasId;
use App\Traits\Entity\HasName;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use HasId;
    use HasName;
    use HasDateCreated;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\NotBlank]
    private ?string $surname;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Image]
    private ?string $profilePicture;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\Email]
    private ?string $username;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    #[Assert\NotCompromisedPassword]
    private string $password;

    public function getUsername(): ?string {
        return $this->username;
    }

    public function setUsername(string $username): void {
        $this->username = $username;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): ?string {
        return $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): void {
        $this->roles = $roles;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string {
        return $this->password;
    }

    public function setPassword(string $password): void {
        $this->password = $password;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials() {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getSurname(): ?string {
        return $this->surname;
    }

    public function setSurname(?string $surname): void {
        $this->surname = $surname;
    }

    public function getProfilePicture(): ?string {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): void {
        $this->profilePicture = $profilePicture;
    }

    public function getFullname(): string {
        if (empty($this->surname)) {
            return $this->name;
        }
        return "{$this->name} {$this->surname}";
    }

    public function getPrimaryRole(): string {
        $roleHierarchy = ['ROLE_ADMIN', 'ROLE_USER'];
        $currentRoles = $this->getRoles();
        foreach ($roleHierarchy as $role) {
            if (in_array($role, $currentRoles, true)) {
                return $role;
            }
        }
        return 'ROLE_USER';
    }

    public function setPrimaryRole($role): void {
        $roleHierarchy = ['ROLE_ADMIN', 'ROLE_USER'];
        if (!in_array($role,$roleHierarchy, true)) {
            throw new \RuntimeException('Invalid primary role');
        }
        $this->setRoles([$role]);
    }
}
