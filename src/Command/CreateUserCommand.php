<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user')]
final class CreateUserCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $helper = new QuestionHelper();

        $email = $helper->ask($input, $output, new Question('Enter an email : '));
        $firstname = $helper->ask($input, $output, new Question('Firstname : '));
        $lastname = $helper->ask($input, $output, new Question('Lastname : '));

        $question = new ChoiceQuestion('Choose a role : ', ['ROLE_ADMIN', 'ROLE_USER']);
        $question->setErrorMessage('Role is not in the list of possible choices.');
        $role = $helper->ask($input, $output, $question);

        if (!is_string($email) || trim($email) === ''
            || !is_string($firstname) || trim($firstname) === ''
            || !is_string($lastname) || trim($lastname) === ''
        ) {
            $output->writeln('<error>Email, firstname and lastname are required.</error>');
            return Command::INVALID;
        }

        $user = (new User())
            ->setEmail(trim($email))
            ->setFirstname(trim($firstname))
            ->setLastname(trim($lastname))
            ->setRoles([$role])
            ->setIsActive(true);

        // Les comptes metier ROLE_USER sont lies a Numelib, pas a un login Symfony.
        if ($role === 'ROLE_ADMIN') {
            $question = new Question('Enter an administrator password : ');
            $question->setHidden(true);
            $password = $helper->ask($input, $output, $question);

            $question = new Question('Confirm administrator password : ');
            $question->setHidden(true);
            $confirmation = $helper->ask($input, $output, $question);

            if (!is_string($password) || $password === '' || $password !== $confirmation) {
                $output->writeln('<error>Password is required and both entries must match.</error>');
                return Command::INVALID;
            }
            if (preg_match('/^\S*(?=\S{7,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $password) !== 1) {
                $output->writeln('<error>Password must have at least 7 characters, uppercase, lowercase and digit.</error>');
                return Command::INVALID;
            }

            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
