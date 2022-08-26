<?php

namespace App\Commands\Application;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPasswordValidator;
use Symfony\Component\Validator\Constraints\NotCompromisedPasswordValidator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $items = [
            'name' => [],
            'surname' => [],
            'username' => [
            ],
            'password' => [
                'hidden' => true,
                'normalizer' => function ($value) {
                    return $this->passwordHasher->hashPassword(new User(), $value);
                }
            ]
        ];

        $user = new User();
        foreach ($items as $item => $arr) {
            $question = new Question('<fg=green>' . ucfirst($item) . ": </>", '');
            $question->setValidator(function ($answer) use ($item) {
                $internalUser = new User();
                $internalUser->{sp_setter($item)}($answer);
                /** @var ConstraintViolationInterface $error */
                foreach ($this->validator->validateProperty($internalUser, $item) as $error) {
                    throw new \RuntimeException($error->getMessage());
                }
                return $answer;
            });

            if ($arr['hidden'] ?? false) {
                $question->setHidden(true);
            }

            $value = $questionHelper->ask($input, $output, $question);
            if ($arr['normalizer'] ?? false) {
                $value = $arr['normalizer']($value);
            }
            $user->{sp_setter($item)}($value);
        }
        $user->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln("<fg=black; bg=green> User {$user->getFullname()} successfully created </>");
        return Command::SUCCESS;
    }

}
