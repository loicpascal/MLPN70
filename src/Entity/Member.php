<?php

namespace App\Entity;

use App\Repository\MemberRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=MemberRepository::class)
 */
class Member implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="boolean", options={"default":"0"})
     */
    private $is_active;

    /**
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthdate;

    /**
     * @ORM\Column(type="boolean", options={"default":"0"})
     */
    private $receive_email_new_comment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password_forgotten_hash;

    /**
     * @ORM\OneToMany(targetEntity=Idea::class, mappedBy="member", orphanRemoval=true)
     */
    private $ideas;

    private $not_archived_ideas;

    /**
     * @ORM\OneToMany(targetEntity=Idea::class, mappedBy="member_taking")
     */
    private $ideas_taking;

    /**
     * @ORM\OneToMany(targetEntity=Idea::class, mappedBy="member_adding", orphanRemoval=true)
     */
    private $ideas_adding;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="member", orphanRemoval=true)
     */
    private $comments_written;

    /**
     * @ORM\ManyToMany(targetEntity=Family::class, inversedBy="members")
     */
    private $families;

    /**
     * @ORM\OneToMany(targetEntity=Family::class, mappedBy="member")
     */
    private $createdFamilies;

    public function __construct()
    {
        $this->ideas = new ArrayCollection();
        $this->not_archived_ideas = new ArrayCollection();
        $this->ideas_taking = new ArrayCollection();
        $this->ideas_adding = new ArrayCollection();
        $this->comments_written = new ArrayCollection();
        $this->family = new ArrayCollection();
        $this->createdFamilies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getReceiveEmailNewComment(): ?bool
    {
        return $this->receive_email_new_comment;
    }

    public function setReceiveEmailNewComment(bool $receive_email_new_comment): self
    {
        $this->receive_email_new_comment = $receive_email_new_comment;

        return $this;
    }

    public function getPasswordForgottenHash(): ?string
    {
        return $this->password_forgotten_hash;
    }

    public function setPasswordForgottenHash(?string $password_forgotten_hash): self
    {
        $this->password_forgotten_hash = $password_forgotten_hash;

        return $this;
    }

    /**
     * @return Collection|Idea[]
     */
    public function getIdeas(): Collection
    {
        return $this->ideas;
    }

    public function addIdea(Idea $idea): self
    {
        if (! $this->ideas->contains($idea)) {
            $this->ideas[] = $idea;
            $idea->setMember($this);
        }

        return $this;
    }

    public function removeIdea(Idea $idea): self
    {
        if ($this->ideas->contains($idea)) {
            $this->ideas->removeElement($idea);
            // set the owning side to null (unless already changed)
            if ($idea->getMember() === $this) {
                $idea->setMember(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Idea[]
     */
    public function getNotArchivedIdeas(): Collection
    {
        $this->not_archived_ideas = new ArrayCollection();
        foreach ($this->ideas as $idea) {
            if (! $idea->isArchived()) {
                $this->addNotArchivedIdea($idea);
            }
        }
        return $this->not_archived_ideas;
    }

    public function addNotArchivedIdea(Idea $idea): self
    {
        if (! $this->not_archived_ideas->contains($idea)) {
            $this->not_archived_ideas[] = $idea;
            $idea->setMember($this);
        }

        return $this;
    }

    /**
     * @return Collection|Idea[]
     */
    public function getIdeasTaking(): Collection
    {
        return $this->ideas_taking;
    }

    public function addIdeasTaking(Idea $ideasTaking): self
    {
        if (! $this->ideas_taking->contains($ideasTaking)) {
            $this->ideas_taking[] = $ideasTaking;
            $ideasTaking->setMemberTaking($this);
        }

        return $this;
    }

    public function removeIdeasTaking(Idea $ideasTaking): self
    {
        if ($this->ideas_taking->contains($ideasTaking)) {
            $this->ideas_taking->removeElement($ideasTaking);
            // set the owning side to null (unless already changed)
            if ($ideasTaking->getMemberTaking() === $this) {
                $ideasTaking->setMemberTaking(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Idea[]
     */
    public function getIdeasAdding(): Collection
    {
        return $this->ideas_adding;
    }

    public function addIdeasAdding(Idea $ideasAdding): self
    {
        if (! $this->ideas_adding->contains($ideasAdding)) {
            $this->ideas_adding[] = $ideasAdding;
            $ideasAdding->setMemberAdding($this);
        }

        return $this;
    }

    public function removeIdeasAdding(Idea $ideasAdding): self
    {
        if ($this->ideas_adding->contains($ideasAdding)) {
            $this->ideas_adding->removeElement($ideasAdding);
            // set the owning side to null (unless already changed)
            if ($ideasAdding->getMemberAdding() === $this) {
                $ideasAdding->setMemberAdding(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getCommentsWritten(): Collection
    {
        return $this->comments_written;
    }

    public function addCommentsWritten(Comment $commentsWritten): self
    {
        if (! $this->comments_written->contains($commentsWritten)) {
            $this->comments_written[] = $commentsWritten;
            $commentsWritten->setMember($this);
        }

        return $this;
    }

    public function removeCommentsWritten(Comment $commentsWritten): self
    {
        if ($this->comments_written->contains($commentsWritten)) {
            $this->comments_written->removeElement($commentsWritten);
            // set the owning side to null (unless already changed)
            if ($commentsWritten->getMember() === $this) {
                $commentsWritten->setMember(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Family[]
     */
    public function getFamilies(): Collection
    {
        return $this->families;
    }

    public function addFamily(Family $family): self
    {
        if (! $this->families->contains($family)) {
            $this->families[] = $family;
        }

        return $this;
    }

    public function removeFamily(Family $family): self
    {
        if ($this->families->contains($family)) {
            $this->families->removeElement($family);
        }

        return $this;
    }

    /**
     * @return Collection|Family[]
     */
    public function getCreatedFamilies(): Collection
    {
        return $this->createdFamilies;
    }

    public function addCreatedFamily(Family $createdFamily): self
    {
        if (! $this->createdFamilies->contains($createdFamily)) {
            $this->createdFamilies[] = $createdFamily;
            $createdFamily->setMember($this);
        }

        return $this;
    }

    public function removeCreatedFamily(Family $createdFamily): self
    {
        if ($this->createdFamilies->contains($createdFamily)) {
            $this->createdFamilies->removeElement($createdFamily);
            // set the owning side to null (unless already changed)
            if ($createdFamily->getMember() === $this) {
                $createdFamily->setMember(null);
            }
        }

        return $this;
    }
}
